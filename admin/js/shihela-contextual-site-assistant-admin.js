/**
 * Admin JavaScript for Shihela Contextual Site Assistant
 * Modern SaaS Tabbed Interface & Interactive Controls
 */
(function($) {
	'use strict';

	$(function() {
		const $tabButtons = $('.shihela-admin-tab-btn');
		const $tabPanels  = $('.shihela-admin-tab-panel');
		const storageKey  = 'shihela_admin_active_tab';

		function activateTab(tabId) {
			if (!tabId || !$(`.shihela-admin-tab-panel#${tabId}`).length) {
				tabId = 'tab-general';
			}

			// Update buttons
			$tabButtons.removeClass('active').attr('aria-selected', 'false');
			$(`.shihela-admin-tab-btn[data-tab="${tabId}"]`).addClass('active').attr('aria-selected', 'true');

			// Update panels
			$tabPanels.removeClass('active').hide();
			$(`#${tabId}`).addClass('active').fadeIn(200);

			// Save to local storage & URL hash
			try {
				localStorage.setItem(storageKey, tabId);
			} catch (e) {
				// Fallback if localStorage disabled
			}
		}

		// Initial tab activation from URL hash, localStorage, or default
		let initialTab = window.location.hash ? window.location.hash.substring(1) : '';
		if (!initialTab) {
			try {
				initialTab = localStorage.getItem(storageKey);
			} catch (e) {
				initialTab = '';
			}
		}

		activateTab(initialTab || 'tab-general');

		// Click event on tabs
		$tabButtons.on('click', function(e) {
			e.preventDefault();
			const targetTab = $(this).data('tab');
			activateTab(targetTab);
		});
	});
})(jQuery);
