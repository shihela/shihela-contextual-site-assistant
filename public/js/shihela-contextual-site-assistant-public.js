/**
 * Frontend scripts for Shihela Contextual Site Assistant Widget
 */
(function($) {
	'use strict';

	$(function() {
		const $root = $('#shihela-contextual-site-assistant-widget-root');
		const $launcher = $('#shihela-contextual-site-assistant-launcher');
		const $panel = $('#shihela-contextual-site-assistant-panel');
		const $closeBtn = $('#shihela-contextual-site-assistant-close-panel');
		const $resetBtn = $('#shihela-contextual-site-assistant-reset-chat');
		const $messagesBody = $('#shihela-contextual-site-assistant-messages-body');
		const $form = $('#shihela-contextual-site-assistant-chat-form');
		const $input = $('#shihela-contextual-site-assistant-chat-input');
		const $submitBtn = $('#shihela-contextual-site-assistant-chat-submit');

		const chatHistoryKey = 'shihela_contextual_site_assistant_chat_history_v1';
		const chatSessionKey = 'shihela_contextual_site_assistant_session_id_v1';
		let chatHistory = [];
		let chatSessionId = sessionStorage.getItem(chatSessionKey);
		if (!chatSessionId) {
			chatSessionId = 'sess_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
			sessionStorage.setItem(chatSessionKey, chatSessionId);
		}

		// Load history from session storage if exists
		initChatHistory();

		// Enforce max character limit on input
		if (shihelaContextualSiteAssistantPublic.max_length) {
			$input.attr('maxlength', shihelaContextualSiteAssistantPublic.max_length);
		}

		// Handle daily limit block
		if (shihelaContextualSiteAssistantPublic.daily_limit_reached) {
			$input.prop('disabled', true);
			$submitBtn.prop('disabled', true);
			$input.attr('placeholder', shihelaContextualSiteAssistantPublic.error_daily_limit);

			// Show daily limit notification in chat body if history is empty
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

		// Launcher click toggle
		$launcher.on('click', function() {
			const isHidden = $panel.hasClass('hidden');
			if (isHidden) {
				openPanel();
			} else {
				closePanel();
			}
		});

		// Close button click
		$closeBtn.on('click', function() {
			closePanel();
		});

		// Reset chat button click
		$resetBtn.on('click', function(e) {
			e.preventDefault();
			if (confirm(shihelaContextualSiteAssistantPublic.reset_confirm)) {
				chatHistory = [];
				sessionStorage.removeItem(chatHistoryKey);
				sessionStorage.removeItem(chatSessionKey);

				// Generate new session ID for next conversation
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
			}
		});

		// Submit form handler
		$form.on('submit', function(e) {
			e.preventDefault();
			const message = $input.val().trim();
			if (!message) return;

			// Append user message
			appendMessage('user', message);
			$input.val('');

			// Disable inputs while processing
			toggleLoading(true);

			// Send to REST API
			sendMessageToAI(message);
		});

		function openPanel() {
			$panel.removeClass('hidden');
			$launcher.find('.shihela-contextual-site-assistant-icon-chat').hide();
			$launcher.find('.shihela-contextual-site-assistant-icon-close').show();
			scrollToBottom();
			$input.trigger('focus');
		}

		function closePanel() {
			$panel.addClass('hidden');
			$launcher.find('.shihela-contextual-site-assistant-icon-chat').show();
			$launcher.find('.shihela-contextual-site-assistant-icon-close').hide();
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
				<div id="shihela-contextual-site-assistant-typing-indicator" class="shihela-contextual-site-assistant-typing-bubble">
					<span class="shihela-contextual-site-assistant-typing-dot"></span>
					<span class="shihela-contextual-site-assistant-typing-dot"></span>
					<span class="shihela-contextual-site-assistant-typing-dot"></span>
				</div>
			`;
			$messagesBody.append(indicatorHtml);
			scrollToBottom();
		}

		function hideTypingIndicator() {
			$('#shihela-contextual-site-assistant-typing-indicator').remove();
		}

		function scrollToBottom() {
			$messagesBody.scrollTop($messagesBody[0].scrollHeight);
		}

		function getCurrentTime() {
			const now = new Date();
			return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
		}

		// Simple Markdown Formatter to keep plugin lightweight
		// Bold: **text**
		// Italics: *text*
		// Bullet lists: Lines starting with "- " or "* "
		// Paragraphs: Split double newlines
		function formatMarkdown(text) {
			// Escape HTML
			let html = text
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');

			// Bold
			html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

			// Italics
			html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

			// Bullet lists
			html = html.replace(/(?:^|\n)[-*]\s+(.+)/g, function(match, p1) {
				return '<br>• ' + p1;
			});

			// Paragraphs
			const paragraphs = html.split('\n\n');
			return paragraphs.map(p => {
				const inner = p.replace(/\n/g, '<br>');
				return `<p>${inner}</p>`;
			}).join('');
		}

		function sendMessageToAI(userMessage) {
			const honeypotVal = $('#shihela-hp').val() || '';

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
				toggleLoading(false);
				if (data.success && data.response) {
					const reply = data.response;
					appendMessage('assistant', reply);

					// Save to history list
					chatHistory.push({ role: 'user', content: userMessage });
					chatHistory.push({ role: 'assistant', content: reply });
					saveChatHistory();
				} else {
					appendMessage('assistant', shihelaContextualSiteAssistantPublic.error_response);
				}
			})
			.catch(error => {
				toggleLoading(false);
				const errorMsg = error.message || shihelaContextualSiteAssistantPublic.error_connection;
				appendMessage('assistant', errorMsg);
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
					// Re-populate messages into body (skip welcome message since we render it statically)
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
