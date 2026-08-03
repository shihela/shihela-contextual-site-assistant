/**
 * Frontend scripts for Shihela Contextual Site Assistant Widget
 * Enterprise Edition: Shadow DOM Encapsulation, WCAG 2.1 AA Accessibility, & Performance Hardened
 */
(function($) {
    'use strict';

    $(function() {
        // 1. CEK RENDER DARI PHP
        // Kita mengambil elemen root yang sudah di-render oleh PHP (biasanya di wp_footer)
        const existingRootDom = document.getElementById('shihela-contextual-site-assistant-widget-root');
        if (!existingRootDom) return;

        // 2. SETUP SHADOW DOM HOST & ENKAPSULASI
        // Kita buat container host terisolasi di body, lalu tempelkan Shadow Root
        let hostElement = document.getElementById('shihela-contextual-site-assistant-host');
        if (!hostElement) {
            hostElement = document.createElement('div');
            hostElement.id = 'shihela-contextual-site-assistant-host';
            document.body.appendChild(hostElement);
        }
        const shadowRoot = hostElement.attachShadow({ mode: 'open' });

        // 3. INJECT STYLESHEET KE DALAM SHADOW DOM
        // Karena CSS global tidak bisa masuk ke Shadow DOM, kita muat file CSS plugin langsung di sini
        if (typeof shihelaContextualSiteAssistantPublic !== 'undefined' && shihelaContextualSiteAssistantPublic.css_url) {
            const styleLink = document.createElement('link');
            styleLink.rel = 'stylesheet';
            styleLink.href = shihelaContextualSiteAssistantPublic.css_url;
            shadowRoot.appendChild(styleLink);
        }

        // 4. PINDAHKAN WIDGET KE DALAM SHADOW ROOT
        // Elemen DOM asli dipindahkan ke dalam "tembok pelindung" Shadow DOM
        shadowRoot.appendChild(existingRootDom);

        // 5. HELPER JQUERY SCOPED (Kunci untuk Shadow DOM)
        // Memastikan jQuery hanya mencari elemen di dalam batas Shadow Root kita
        const $find = (selector) => $(shadowRoot.querySelector(selector));

        // Inisialisasi variabel DOM menggunakan Scoped Finder
        const $root = $find('#shihela-contextual-site-assistant-widget-root');
        const $launcher = $find('#shihela-contextual-site-assistant-launcher');
        const $panel = $find('#shihela-contextual-site-assistant-panel');
        const $closeBtn = $find('#shihela-contextual-site-assistant-close-panel');
        const $resetBtn = $find('#shihela-contextual-site-assistant-reset-chat');
        const $headerWaBtn = $find('#shihela-contextual-site-assistant-header-wa-btn');
        const $messagesBody = $find('#shihela-contextual-site-assistant-messages-body');
        const $chipsContainer = $find('#shihela-contextual-site-assistant-chips-container');
        const $form = $find('#shihela-contextual-site-assistant-chat-form');
        const $input = $find('#shihela-contextual-site-assistant-chat-input');
        const $submitBtn = $find('#shihela-contextual-site-assistant-chat-submit');

        // Click handler for Header WA button
        if ($headerWaBtn && $headerWaBtn.length) {
            $headerWaBtn.on('click', function(e) {
                e.preventDefault();
                const url = buildWhatsAppUrl();
                if (url && url !== '#') {
                    window.open(url, '_blank', 'noopener,noreferrer');
                }
            });
        }

        // State Manajemen
        const chatHistoryKey = 'shihela_contextual_site_assistant_chat_history_v1';
        const chatSessionKey = 'shihela_contextual_site_assistant_session_id_v1';
        let chatHistory = [];
        let isProcessing = false; // [PERFORMA] State guard untuk mencegah Double-Submit Spam
        
        let chatSessionId = sessionStorage.getItem(chatSessionKey);
        if (!chatSessionId) {
            chatSessionId = 'sess_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
            sessionStorage.setItem(chatSessionKey, chatSessionId);
        }

        // --- ENTERPRISE ACCESSIBILITY (A11Y - WCAG 2.1 AA) SETUP ---
        $launcher.attr({
            'role': 'button',
            'aria-expanded': 'false',
            'aria-label': 'Buka asisten obrolan situs',
            'aria-controls': 'shihela-contextual-site-assistant-panel',
            'tabindex': '0'
        });

        $panel.attr({
            'role': 'dialog',
            'aria-modal': 'false',
            'aria-label': 'Shihela Contextual Site Assistant',
            'aria-hidden': 'true'
        });

        // [A11Y] Live Region agar Screen Reader membacakan pesan baru secara otomatis
        $messagesBody.attr({
            'role': 'log',
            'aria-live': 'polite',
            'aria-relevant': 'additions',
            'aria-atomic': 'false'
        });

        // Load history from session storage if exists
        initChatHistory();
        renderSuggestionChips();

        function renderSuggestionChips() {
            if (!$chipsContainer || !$chipsContainer.length) return;
            const chips = (typeof shihelaContextualSiteAssistantPublic !== 'undefined' && shihelaContextualSiteAssistantPublic.suggestion_chips) ? shihelaContextualSiteAssistantPublic.suggestion_chips : [];
            
            if (!Array.isArray(chips) || chips.length === 0) {
                $chipsContainer.hide();
                return;
            }

            let chipsHtml = '';
            chips.forEach(function(chipText) {
                if (chipText && chipText.trim()) {
                    const escText = $('<div>').text(chipText.trim()).html();
                    chipsHtml += `<button type="button" class="shihela-contextual-site-assistant-chip" data-prompt="${escText}">${escText}</button>`;
                }
            });

            if (chipsHtml) {
                $chipsContainer.html(chipsHtml).show();
            } else {
                $chipsContainer.hide();
            }
        }

        // Delegated click handler for suggestion chips
        $chipsContainer.on('click', '.shihela-contextual-site-assistant-chip', function(e) {
            e.preventDefault();
            if (isProcessing) return;

            const promptText = $(this).attr('data-prompt') || $(this).text();
            if (!promptText) return;

            $input.val(promptText);
            $form.trigger('submit');
        });

        // Enforce max character limit on input
        if (shihelaContextualSiteAssistantPublic.max_length) {
            $input.attr('maxlength', shihelaContextualSiteAssistantPublic.max_length);
        }

        // Handle daily limit block
        if (shihelaContextualSiteAssistantPublic.daily_limit_reached) {
            $input.prop('disabled', true);
            $submitBtn.prop('disabled', true);
            $input.attr('placeholder', shihelaContextualSiteAssistantPublic.error_daily_limit);

            if (chatHistory.length === 0) {
                $messagesBody.html(`
                    <div class="shihela-contextual-site-assistant-message assistant">
                        <div class="shihela-contextual-site-assistant-message-bubble">
                            ${shihelaContextualSiteAssistantPublic.error_daily_limit}
                        </div>
                        <span class="shihela-contextual-site-assistant-message-time">${getCurrentTime()}</span>
                    </div>
                `);
            }
        }

        // Launcher click & keyboard (Enter/Space) toggle
        $launcher.on('click keypress', function(e) {
            if (e.type === 'click' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const isHidden = $panel.hasClass('hidden');
                if (isHidden) {
                    openPanel();
                } else {
                    closePanel();
                }
            }
        });

        // Close button click
        $closeBtn.on('click', function() {
            closePanel();
        });

        // [A11Y] Escape key listener untuk menutup panel secara cepat (Keyboard Navigation)
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && !$panel.hasClass('hidden')) {
                closePanel();
            }
        });

        // Reset chat button click
        $resetBtn.on('click', function(e) {
            e.preventDefault();
            if (confirm(shihelaContextualSiteAssistantPublic.reset_confirm)) {
                chatHistory = [];
                sessionStorage.removeItem(chatHistoryKey);
                sessionStorage.removeItem(chatSessionKey);

                chatSessionId = 'sess_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
                sessionStorage.setItem(chatSessionKey, chatSessionId);

                $messagesBody.html(`
                    <div class="shihela-contextual-site-assistant-message assistant">
                        <div class="shihela-contextual-site-assistant-message-bubble">
                            ${shihelaContextualSiteAssistantPublic.welcome_message}
                        </div>
                        <span class="shihela-contextual-site-assistant-message-time">${getCurrentTime()}</span>
                    </div>
                `);
                scrollToBottom();
                $input.trigger('focus');
            }
        });

        // Submit form handler dengan Double-Submit Protection
        $form.on('submit', function(e) {
            e.preventDefault();
            
            // [PERFORMA] Mencegah pengiriman ganda jika request sebelumnya sedang diproses
            if (isProcessing) return;

            const message = $input.val().trim();
            if (!message) return;

            isProcessing = true; // Kunci eksekusi
            appendMessage('user', message);
            $input.val('');

            toggleLoading(true);
            sendMessageToAI(message);
        });

        function openPanel() {
            $panel.removeClass('hidden').attr('aria-hidden', 'false');
            $launcher.attr('aria-expanded', 'true');
            $launcher.find('.shihela-contextual-site-assistant-icon-chat').hide();
            $launcher.find('.shihela-contextual-site-assistant-icon-close').show();
            scrollToBottom();
            // [A11Y] Pindahkan fokus langsung ke input chat agar siap diketik
            setTimeout(() => $input.trigger('focus'), 100);
        }

        function closePanel() {
            $panel.addClass('hidden').attr('aria-hidden', 'true');
            $launcher.attr('aria-expanded', 'false');
            $launcher.find('.shihela-contextual-site-assistant-icon-chat').show();
            $launcher.find('.shihela-contextual-site-assistant-icon-close').hide();
            // [A11Y] Kembalikan fokus ke tombol launcher demi kenyamanan navigasi keyboard
            $launcher.trigger('focus');
        }

        function toggleLoading(isLoading) {
            if (isLoading) {
                $input.prop('disabled', true);
                $submitBtn.prop('disabled', true);
                showTypingIndicator();
            } else {
                $input.prop('disabled', false);
                $submitBtn.prop('disabled', false);
                hideTypingIndicator();
                $input.trigger('focus');
            }
        }

        function appendMessage(role, text, timestamp = '') {
            const timeStr = timestamp || getCurrentTime();
            const formattedText = formatMarkdown(text);
            const alignmentClass = role === 'user' ? 'user' : 'assistant';

            const messageHtml = `
                <div class="shihela-contextual-site-assistant-message ${alignmentClass}">
                    <div class="shihela-contextual-site-assistant-message-bubble">
                        ${formattedText}
                    </div>
                    <span class="shihela-contextual-site-assistant-message-time">${timeStr}</span>
                </div>
            `;

            $messagesBody.append(messageHtml);
            scrollToBottom();
        }

        function showTypingIndicator() {
            const indicatorHtml = `
                <div id="shihela-contextual-site-assistant-typing-indicator" class="shihela-contextual-site-assistant-typing-bubble" aria-label="Asisten sedang mengetik...">
                    <span class="shihela-contextual-site-assistant-typing-dot"></span>
                    <span class="shihela-contextual-site-assistant-typing-dot"></span>
                    <span class="shihela-contextual-site-assistant-typing-dot"></span>
                </div>
            `;
            $messagesBody.append(indicatorHtml);
            scrollToBottom();
        }

        function hideTypingIndicator() {
            $find('#shihela-contextual-site-assistant-typing-indicator').remove();
        }

        function scrollToBottom() {
            $messagesBody.scrollTop($messagesBody[0].scrollHeight);
        }

        function getCurrentTime() {
            const now = new Date();
            return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        function buildWhatsAppUrl() {
            if (typeof shihelaContextualSiteAssistantPublic === 'undefined' || !shihelaContextualSiteAssistantPublic.whatsapp_number) {
                return '#';
            }
            const cleanNumber = shihelaContextualSiteAssistantPublic.whatsapp_number.replace(/[^0-9]/g, '');
            if (!cleanNumber) return '#';

            let tpl = shihelaContextualSiteAssistantPublic.whatsapp_message || 'Halo CS/Admin, saya sedang melihat halaman {page_title} ({page_url}) dan membutuhkan bantuan.';
            const title = shihelaContextualSiteAssistantPublic.page_title || document.title || 'Website';
            const url = shihelaContextualSiteAssistantPublic.page_url || window.location.href;

            tpl = tpl.replace('{page_title}', title).replace('{page_url}', url);
            return 'https://wa.me/' + cleanNumber + '?text=' + encodeURIComponent(tpl);
        }

        function formatMarkdown(text) {
            if (!text) return '';

            // Clean LEAD JSON tags if present
            let cleanedText = text.replace(/\[LEAD:\s*{.*?}\s*\]/gi, '').trim();

            const waUrl = buildWhatsAppUrl();
            const defaultLabel = (typeof shihelaContextualSiteAssistantPublic !== 'undefined' && shihelaContextualSiteAssistantPublic.whatsapp_default_label) ? shihelaContextualSiteAssistantPublic.whatsapp_default_label : 'Chat via WhatsApp';
            const waSvgIcon = '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12.012 2C6.5 2 2.012 6.48 2.012 12c0 2.07.63 3.99 1.71 5.58L2 22l4.56-1.68c1.53.97 3.35 1.54 5.44 1.54 5.51 0 10-4.48 10-10S17.522 2 12.012 2zm.01 17.86c-1.8 0-3.47-.51-4.9-1.39l-.35-.21-2.71.99.88-2.61-.23-.37a7.84 7.84 0 0 1-1.22-4.27c0-4.34 3.53-7.86 7.87-7.86 4.34 0 7.86 3.52 7.86 7.86 0 4.34-3.52 7.86-7.86 7.86zm4.31-5.88c-.24-.12-1.41-.7-1.63-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.41-1.33-1.65-.14-.24-.01-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.31-.74-1.79-.2-.47-.4-.41-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.03 0 1.2.87 2.36 1 2.52.12.16 1.72 2.63 4.17 3.69.58.25 1.04.4 1.39.51.59.19 1.12.16 1.54.1.47-.07 1.41-.58 1.61-1.14.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28z"/></svg>';

            // Convert [WHATSAPP_BTN] or [WHATSAPP_BTN: custom text] tag into CTA button
            cleanedText = cleanedText.replace(/\[WHATSAPP_BTN(?::\s*([^\]]+))?\]/gi, function(match, customLabel) {
                if (waUrl === '#') return '';
                const btnLabel = customLabel ? customLabel.trim() : defaultLabel;
                return `<div class="shihela-wa-btn-container"><a href="${waUrl}" target="_blank" rel="noopener noreferrer" class="shihela-contextual-site-assistant-whatsapp-btn">${waSvgIcon} <span>${btnLabel}</span></a></div>`;
            });

            let html = cleanedText
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            // Re-allow WhatsApp CTA markup generated by tag parser
            html = html.replace(/&lt;div class=&quot;shihela-wa-btn-container&quot;&gt;&lt;a href=&quot;(.*?)&quot; target=&quot;_blank&quot; rel=&quot;noopener noreferrer&quot; class=&quot;shihela-contextual-site-assistant-whatsapp-btn&quot;&gt;(.*?)&lt;\/a&gt;&lt;\/div&gt;/gi, function(match, href, inner) {
                const unescapedHref = href.replace(/&amp;/g, '&');
                const unescapedInner = inner.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&amp;/g, '&');
                return `<div class="shihela-wa-btn-container"><a href="${unescapedHref}" target="_blank" rel="noopener noreferrer" class="shihela-contextual-site-assistant-whatsapp-btn">${unescapedInner}</a></div>`;
            });

            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
            html = html.replace(/(?:^|\n)[-*]\s+(.+)/g, function(match, p1) {
                return '<br>• ' + p1;
            });

            const paragraphs = html.split('\n\n');
            return paragraphs.map(p => {
                const inner = p.replace(/\n/g, '<br>');
                return `<p>${inner}</p>`;
            }).join('');
        }

        function sendMessageToAI(userMessage) {
            const honeypotVal = $find('#shihela-hp').val() || '';

            const payload = {
                message: userMessage,
                post_id: shihelaContextualSiteAssistantPublic.post_id,
                history: chatHistory,
                session_id: chatSessionId,
                hp_value: honeypotVal
            };

            fetch(shihelaContextualSiteAssistantPublic.rest_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': shihelaContextualSiteAssistantPublic.nonce
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.response) {
                    const reply = data.response;
                    appendMessage('assistant', reply);

                    chatHistory.push({ role: 'user', content: userMessage });
                    chatHistory.push({ role: 'assistant', content: reply });
                    saveChatHistory();
                } else {
                    appendMessage('assistant', shihelaContextualSiteAssistantPublic.error_response);
                }
            })
            .catch(error => {
                const errorMsg = error.message || shihelaContextualSiteAssistantPublic.error_connection;
                appendMessage('assistant', errorMsg);
            })
            .finally(() => {
                // [PERFORMA] Buka kembali kunci proses baik saat sukses maupun gagal
                isProcessing = false;
                toggleLoading(false);
            });
        }

        function saveChatHistory() {
            try {
                sessionStorage.setItem(chatHistoryKey, JSON.stringify(chatHistory));
            } catch (e) {
                console.error('Failed to save chat history to sessionStorage', e);
            }
        }

        function initChatHistory() {
            try {
                const saved = sessionStorage.getItem(chatHistoryKey);
                if (saved) {
                    chatHistory = JSON.parse(saved);
                    chatHistory.forEach(msg => {
                        appendMessage(msg.role, msg.content);
                    });
                }
            } catch (e) {
                console.error('Failed to initialize chat history from sessionStorage', e);
            }
        }
    });
})(jQuery);