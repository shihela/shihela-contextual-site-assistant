/**
 * Frontend scripts for Shihela Contextual Site Assistant Widget.
 * Enterprise Edition: Zero-jQuery Vanilla JS, Shadow DOM Encapsulation, WCAG 2.1 AA, & XSS Hardened.
 */
(function () {
    'use strict';

    function initWidget() {
        const existingRootDom = document.getElementById('shihela-contextual-site-assistant-widget-root');
        if (!existingRootDom) {
            return;
        }

        const cfg = (typeof shihelaContextualSiteAssistantPublic !== 'undefined')
            ? shihelaContextualSiteAssistantPublic
            : {};

        // 1. Setup isolated Shadow DOM host
        let hostElement = document.getElementById('shihela-contextual-site-assistant-host');
        if (!hostElement) {
            hostElement = document.createElement('div');
            hostElement.id = 'shihela-contextual-site-assistant-host';
            document.body.appendChild(hostElement);
        }

        const shadowRoot = hostElement.shadowRoot || hostElement.attachShadow({ mode: 'open' });

        // 2. Reveal widget cleanly once stylesheet loads inside Shadow DOM (prevents FOUC)
        let revealed = false;
        const revealWidget = () => {
            if (!revealed) {
                revealed = true;
                existingRootDom.removeAttribute('hidden');
            }
        };

        if (cfg.css_url) {
            const styleLink = document.createElement('link');
            styleLink.rel = 'stylesheet';
            styleLink.href = cfg.css_url;
            styleLink.addEventListener('load', revealWidget, { once: true });
            styleLink.addEventListener('error', revealWidget, { once: true });
            shadowRoot.appendChild(styleLink);
            // Safety fallback in case stylesheet is served from cache without firing load
            window.setTimeout(revealWidget, 300);
        } else {
            revealWidget();
        }

        // 3. Move PHP-rendered widget into Shadow DOM
        shadowRoot.appendChild(existingRootDom);

        // 4. Scoped DOM helpers
        const find = (selector) => shadowRoot.querySelector(selector);

        const launcher = find('#shihela-contextual-site-assistant-launcher');
        const panel = find('#shihela-contextual-site-assistant-panel');
        const closeBtn = find('#shihela-contextual-site-assistant-close-panel');
        const resetBtn = find('#shihela-contextual-site-assistant-reset-chat');
        const headerWaBtn = find('#shihela-contextual-site-assistant-header-wa-btn');
        const messagesBody = find('#shihela-contextual-site-assistant-messages-body');
        const chipsContainer = find('#shihela-contextual-site-assistant-chips-container');
        const form = find('#shihela-contextual-site-assistant-chat-form');
        const input = find('#shihela-contextual-site-assistant-chat-input');
        const submitBtn = find('#shihela-contextual-site-assistant-chat-submit');
        const greetingBubble = find('#shihela-contextual-site-assistant-greeting');
        const greetingOpenBtn = find('#shihela-contextual-site-assistant-greeting-open');
        const greetingDismissBtn = find('#shihela-contextual-site-assistant-greeting-dismiss');

        if (!launcher || !panel || !messagesBody || !form || !input || !submitBtn) {
            return;
        }

        const iconChat = launcher.querySelector('.shihela-contextual-site-assistant-icon-chat');
        const iconClose = launcher.querySelector('.shihela-contextual-site-assistant-icon-close');

        // 5. State management
        const chatHistoryKey = 'shihela_contextual_site_assistant_chat_history_v1';
        const chatSessionKey = 'shihela_contextual_site_assistant_session_id_v1';
        let chatHistory = [];
        let isProcessing = false;

        let chatSessionId = '';
        try {
            chatSessionId = sessionStorage.getItem(chatSessionKey) || '';
            if (!chatSessionId) {
                chatSessionId = generateSessionId();
                sessionStorage.setItem(chatSessionKey, chatSessionId);
            }
        } catch (e) {
            chatSessionId = generateSessionId();
        }

        // 6. WCAG 2.1 AA Accessibility attributes
        launcher.setAttribute('aria-expanded', 'false');
        launcher.setAttribute('aria-label', cfg.aria_open_chat || 'Open Chat Assistant');
        launcher.setAttribute('aria-controls', 'shihela-contextual-site-assistant-panel');

        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'false');
        panel.setAttribute('aria-label', cfg.aria_panel_label || cfg.bot_name || 'Site Chat Assistant');
        panel.setAttribute('aria-hidden', 'true');

        messagesBody.setAttribute('role', 'log');
        messagesBody.setAttribute('aria-live', 'polite');
        messagesBody.setAttribute('aria-relevant', 'additions');
        messagesBody.setAttribute('aria-atomic', 'false');

        // Populate initial welcome message timestamp in visitor's local timezone (full-page cache safe)
        const initialTime = find('.shihela-contextual-site-assistant-message-time');
        if (initialTime && !initialTime.textContent.trim()) {
            initialTime.textContent = getCurrentTime();
        }

        // Header WhatsApp quick button
        if (headerWaBtn) {
            headerWaBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const url = buildWhatsAppUrl();
                if (url && url !== '#') {
                    window.open(url, '_blank', 'noopener,noreferrer');
                }
            });
        }

        // Initialize history & quick suggestion chips
        initChatHistory();
        renderSuggestionChips();
        initProactiveGreeting();

        function initProactiveGreeting() {
            if (!greetingBubble || !cfg.greeting_enable) {
                return;
            }
            // Yield to PRO proactive triggers when PRO triggers are enabled.
            if (typeof shihelaProPublic !== 'undefined' && Number(shihelaProPublic.enable_triggers)) {
                return;
            }

            const dismissedKey = 'shihela_csa_greeting_dismissed_v1';
            let wasDismissed = false;
            try {
                wasDismissed = sessionStorage.getItem(dismissedKey) === '1';
            } catch (e) {
                wasDismissed = false;
            }

            const dismissGreeting = () => {
                greetingBubble.setAttribute('hidden', '');
                try {
                    sessionStorage.setItem(dismissedKey, '1');
                } catch (e) {
                    // Ignore storage errors
                }
            };

            if (greetingOpenBtn) {
                greetingOpenBtn.addEventListener('click', () => {
                    dismissGreeting();
                    openPanel();
                });
            }

            if (greetingDismissBtn) {
                greetingDismissBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dismissGreeting();
                });
            }

            if (wasDismissed) {
                return;
            }

            const delayMs = Math.max(1, Number(cfg.greeting_delay) || 5) * 1000;
            window.setTimeout(() => {
                if (panel.classList.contains('hidden')) {
                    greetingBubble.removeAttribute('hidden');
                }
            }, delayMs);
        }

        // Enforce max character limit
        if (cfg.max_length) {
            input.setAttribute('maxlength', String(cfg.max_length));
        }

        // Handle daily limit block
        if (cfg.daily_limit_reached) {
            input.disabled = true;
            submitBtn.disabled = true;
            input.setAttribute('placeholder', cfg.error_daily_limit || 'Daily query limit reached.');

            if (chatHistory.length === 0) {
                messagesBody.innerHTML = `
                    <div class="shihela-contextual-site-assistant-message assistant">
                        <div class="shihela-contextual-site-assistant-message-bubble">
                            ${escapeHtml(cfg.error_daily_limit || '')}
                        </div>
                        <span class="shihela-contextual-site-assistant-message-time">${escapeHtml(getCurrentTime())}</span>
                    </div>
                `;
            }
        }

        // Launcher toggle (native <button> handles click, Enter, and Space)
        launcher.addEventListener('click', (e) => {
            e.preventDefault();
            if (panel.classList.contains('hidden')) {
                openPanel();
            } else {
                closePanel();
            }
        });

        // Close button
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                closePanel();
            });
        }

        // Escape key listener to close panel
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !panel.classList.contains('hidden')) {
                closePanel();
            }
        });

        // Reset chat button
        if (resetBtn) {
            resetBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const confirmMsg = cfg.reset_confirm || 'Are you sure you want to reset this conversation?';
                if (window.confirm(confirmMsg)) {
                    chatHistory = [];
                    chatSessionId = generateSessionId();
                    try {
                        sessionStorage.removeItem(chatHistoryKey);
                        sessionStorage.setItem(chatSessionKey, chatSessionId);
                    } catch (err) {
                        // Ignore storage quota / privacy mode errors
                    }

                    messagesBody.innerHTML = `
                        <div class="shihela-contextual-site-assistant-message assistant">
                            <div class="shihela-contextual-site-assistant-message-bubble">
                                ${escapeHtml(cfg.welcome_message || '')}
                            </div>
                            <span class="shihela-contextual-site-assistant-message-time">${escapeHtml(getCurrentTime())}</span>
                        </div>
                    `;
                    scrollToBottom();
                    input.focus();
                }
            });
        }

        // Delegated click handler for suggestion chips
        if (chipsContainer) {
            chipsContainer.addEventListener('click', (e) => {
                const chipBtn = e.target.closest('.shihela-contextual-site-assistant-chip');
                if (!chipBtn) {
                    return;
                }
                e.preventDefault();
                if (isProcessing || input.disabled) {
                    return;
                }

                const promptText = chipBtn.getAttribute('data-prompt') || chipBtn.textContent || '';
                if (!promptText.trim()) {
                    return;
                }

                input.value = promptText.trim();
                submitMessage();
            });
        }

        // Form submit handler with double-submit guard
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            submitMessage();
        });

        function submitMessage() {
            if (isProcessing || input.disabled) {
                return;
            }

            const message = input.value.trim();
            if (!message) {
                return;
            }

            isProcessing = true;
            appendMessage('user', message);
            input.value = '';

            toggleLoading(true);
            sendMessageToAI(message);
        }

        function generateSessionId() {
            return 'sess_' + Math.random().toString(36).slice(2, 11) + '_' + Date.now();
        }

        function renderSuggestionChips() {
            if (!chipsContainer) {
                return;
            }
            const chips = Array.isArray(cfg.suggestion_chips) ? cfg.suggestion_chips : [];
            if (chips.length === 0) {
                chipsContainer.style.display = 'none';
                return;
            }

            chipsContainer.innerHTML = '';
            let added = 0;

            chips.forEach((chipText) => {
                if (typeof chipText === 'string' && chipText.trim()) {
                    const clean = chipText.trim();
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'shihela-contextual-site-assistant-chip';
                    btn.setAttribute('data-prompt', clean);
                    btn.textContent = clean;
                    chipsContainer.appendChild(btn);
                    added += 1;
                }
            });

            chipsContainer.style.display = added > 0 ? '' : 'none';
        }

        function openPanel() {
            if (greetingBubble) {
                greetingBubble.setAttribute('hidden', '');
            }
            panel.classList.remove('hidden');
            panel.setAttribute('aria-hidden', 'false');
            launcher.setAttribute('aria-expanded', 'true');
            if (iconChat) iconChat.style.display = 'none';
            if (iconClose) iconClose.style.display = '';
            scrollToBottom();
            window.setTimeout(() => input.focus(), 100);
        }

        function closePanel() {
            panel.classList.add('hidden');
            panel.setAttribute('aria-hidden', 'true');
            launcher.setAttribute('aria-expanded', 'false');
            if (iconChat) iconChat.style.display = '';
            if (iconClose) iconClose.style.display = 'none';
            launcher.focus();
        }

        function toggleLoading(isLoading) {
            if (isLoading) {
                input.disabled = true;
                submitBtn.disabled = true;
                showTypingIndicator();
            } else {
                if (!cfg.daily_limit_reached) {
                    input.disabled = false;
                    submitBtn.disabled = false;
                }
                hideTypingIndicator();
                if (!input.disabled) {
                    input.focus();
                }
            }
        }

        function escapeHtml(str) {
            if (typeof str !== 'string') {
                return '';
            }
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function appendMessage(role, text, timestamp = '') {
            const timeStr = escapeHtml(timestamp || getCurrentTime());
            const isUser = role === 'user';
            const alignmentClass = isUser ? 'user' : 'assistant';
            // Never parse Markdown or AI action tags on user-supplied text.
            const formattedText = isUser
                ? `<p>${escapeHtml(text).replace(/\n/g, '<br>')}</p>`
                : formatMarkdown(text);

            const wrapper = document.createElement('div');
            wrapper.className = `shihela-contextual-site-assistant-message ${alignmentClass}`;
            wrapper.innerHTML = `
                <div class="shihela-contextual-site-assistant-message-bubble">
                    ${formattedText}
                </div>
                <span class="shihela-contextual-site-assistant-message-time">${timeStr}</span>
            `;

            messagesBody.appendChild(wrapper);
            scrollToBottom();
        }

        function showTypingIndicator() {
            hideTypingIndicator();
            const indicator = document.createElement('div');
            indicator.id = 'shihela-contextual-site-assistant-typing-indicator';
            indicator.className = 'shihela-contextual-site-assistant-typing-bubble';
            indicator.setAttribute('aria-label', cfg.aria_typing || 'Assistant is typing...');
            indicator.innerHTML = `
                <span class="shihela-contextual-site-assistant-typing-dot"></span>
                <span class="shihela-contextual-site-assistant-typing-dot"></span>
                <span class="shihela-contextual-site-assistant-typing-dot"></span>
            `;
            messagesBody.appendChild(indicator);
            scrollToBottom();
        }

        function hideTypingIndicator() {
            const existing = find('#shihela-contextual-site-assistant-typing-indicator');
            if (existing) {
                existing.remove();
            }
        }

        function scrollToBottom() {
            messagesBody.scrollTop = messagesBody.scrollHeight;
        }

        function getCurrentTime() {
            const now = new Date();
            return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        function buildWhatsAppUrl() {
            if (!cfg.whatsapp_number) {
                return '#';
            }
            const cleanNumber = String(cfg.whatsapp_number).replace(/[^0-9]/g, '');
            if (!cleanNumber) {
                return '#';
            }

            let tpl = cfg.whatsapp_message || 'Hello Support, I am viewing the page {page_title} ({page_url}) and need assistance.';
            const title = cfg.page_title || document.title || 'Website';
            const url = cfg.page_url || window.location.href;

            tpl = tpl.replaceAll('{page_title}', title).replaceAll('{page_url}', url);
            return 'https://wa.me/' + cleanNumber + '?text=' + encodeURIComponent(tpl);
        }

        function formatMarkdown(text) {
            if (!text) {
                return '';
            }

            // Clean LEAD JSON tags if present
            const cleanedText = String(text).replace(/\[LEAD:\s*{.*?}\s*\]/gis, '').trim();

            // Escape ALL HTML first to prevent DOM XSS.
            let html = escapeHtml(cleanedText);

            // Apply safe Markdown formatting on already-escaped text.
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
            html = html.replace(/(?:^|\n)[-*]\s+(.+)/g, (match, p1) => '<br>• ' + p1);

            // Convert [WHATSAPP_BTN] or [WHATSAPP_BTN: custom text] AFTER escaping so customLabel stays safely escaped.
            const waUrl = buildWhatsAppUrl();
            const defaultLabel = cfg.whatsapp_default_label || 'Chat via WhatsApp';
            const waSvgIcon = '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true"><path d="M12.012 2C6.5 2 2.012 6.48 2.012 12c0 2.07.63 3.99 1.71 5.58L2 22l4.56-1.68c1.53.97 3.35 1.54 5.44 1.54 5.51 0 10-4.48 10-10S17.522 2 12.012 2zm.01 17.86c-1.8 0-3.47-.51-4.9-1.39l-.35-.21-2.71.99.88-2.61-.23-.37a7.84 7.84 0 0 1-1.22-4.27c0-4.34 3.53-7.86 7.87-7.86 4.34 0 7.86 3.52 7.86 7.86 0 4.34-3.52 7.86-7.86 7.86zm4.31-5.88c-.24-.12-1.41-.7-1.63-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.41-1.33-1.65-.14-.24-.01-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.31-.74-1.79-.2-.47-.4-.41-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.03 0 1.2.87 2.36 1 2.52.12.16 1.72 2.63 4.17 3.69.58.25 1.04.4 1.39.51.59.19 1.12.16 1.54.1.47-.07 1.41-.58 1.61-1.14.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28z"/></svg>';

            html = html.replace(/\[WHATSAPP_BTN(?::\s*([^\]]+))?\]/gi, (match, escapedCustomLabel) => {
                if (waUrl === '#') {
                    return '';
                }
                const safeHref = escapeHtml(waUrl);
                const safeLabel = (escapedCustomLabel && escapedCustomLabel.trim())
                    ? escapedCustomLabel.trim()
                    : escapeHtml(defaultLabel);
                return `<div class="shihela-wa-btn-container"><a href="${safeHref}" target="_blank" rel="noopener noreferrer" class="shihela-contextual-site-assistant-whatsapp-btn">${waSvgIcon} <span>${safeLabel}</span></a></div>`;
            });

            const paragraphs = html.split('\n\n');
            return paragraphs.map((p) => `<p>${p.replace(/\n/g, '<br>')}</p>`).join('');
        }

        function sendMessageToAI(userMessage) {
            const hpInput = find('#shihela-hp');
            const honeypotVal = hpInput ? hpInput.value : '';

            const payload = {
                message: userMessage,
                post_id: cfg.post_id || 0,
                history: chatHistory,
                session_id: chatSessionId,
                hp_value: honeypotVal
            };

            const headers = {
                'Content-Type': 'application/json'
            };
            if (cfg.nonce) {
                headers['X-WP-Nonce'] = cfg.nonce;
            }

            fetch(cfg.rest_url, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(payload)
            })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then((err) => { throw err; });
                }
                return response.json();
            })
            .then((data) => {
                if (data && data.success && data.response) {
                    const reply = data.response;
                    appendMessage('assistant', reply);

                    chatHistory.push({ role: 'user', content: userMessage });
                    chatHistory.push({ role: 'assistant', content: reply });
                    saveChatHistory();
                } else {
                    appendMessage('assistant', cfg.error_response || 'Sorry, I encountered an issue processing that response.');
                }
            })
            .catch((error) => {
                const errorMsg = (error && error.message)
                    ? error.message
                    : (cfg.error_connection || 'Sorry, I am unable to connect to the assistant right now.');
                appendMessage('assistant', errorMsg);
            })
            .finally(() => {
                isProcessing = false;
                toggleLoading(false);
            });
        }

        function saveChatHistory() {
            try {
                sessionStorage.setItem(chatHistoryKey, JSON.stringify(chatHistory));
            } catch (e) {
                // Ignore storage errors
            }
        }

        function initChatHistory() {
            try {
                const saved = sessionStorage.getItem(chatHistoryKey);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    if (Array.isArray(parsed)) {
                        chatHistory = parsed;
                        chatHistory.forEach((msg) => {
                            if (msg && typeof msg.role === 'string' && typeof msg.content === 'string') {
                                appendMessage(msg.role, msg.content);
                            }
                        });
                    }
                }
            } catch (e) {
                chatHistory = [];
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWidget);
    } else {
        initWidget();
    }
})();
