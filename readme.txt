=== Shihela Contextual Site Assistant ===
Contributors: shihela
Tags: ai-client, customer-support, lead-generation, ai-assistant, wordpress-ai
Requires at least: 7.0
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://gnu.org

A floating AI assistant that answers visitors' questions using current page context, powered by the native WordPress AI Client.

== Description ==

Shihela Contextual Site Assistant is a highly performant and context-aware chat assistant designed for WordPress sites. It embeds a sleek, floating chat bubble on your website. When a user asks a question, Shihela Contextual Site Assistant extracts context from the current page (e.g., page title, content snippets) and uses the native WordPress 7.0+ AI Client to provide accurate, contextually relevant answers.

Because it utilizes the native WordPress AI Client, credentials and model configurations are managed globally at the site level under Settings → Connectors. This ensures maximum security and allows site administrators to change providers seamlessly without editing plugin options.

The plugin also automatically captures leads when visitors leave their contact information (such as name, email, or requirements), saves them securely in your database, and sends email notifications.

== Features ==

* **Context-Aware Responses:** Automatically reads page contents to answer questions in context.
* **WordPress Native AI Client:** Fully integrated with the native WordPress 7.0+ AI Client API.
* **Provider Agnostic:** Out-of-the-box support for any LLM provider configured in your WordPress dashboard (Gemini, OpenAI, Anthropic, etc.).
* **Lead Capture & Management:** Automatically parses and saves customer inquiries in the WordPress database and notifies administrators.
* **Custom Positioning & Styling:** Set widget position (bottom-right, bottom-left, top-right, top-left) and customize branding colors.
* **Access Control & Permissions:** Restrict widget visibility (Public, Logged-in Users Only, or Administrators Only) to control assistant access.
* **API Quota & Token Budgeting:** Configure global daily query limits and per-IP daily limits to prevent unexpected token consumption bills.
* **Spam & Bot Protection:** Integrated accessible hidden honeypot fields to block script spammers from draining your API key.
* **History Depth Slicing:** Automatically limits the size of conversation history passed to the API to save precious tokens.
* **WordPress Reviewer Compliant REST Routing:** Security and rate checks are validated in the REST API routing layer (`permission_callback`) for maximum compliance.

== Third-Party Services & Data Privacy Notice ==

This plugin utilizes the native WordPress AI Client to communicate with generative AI models. Depending on your WordPress Core Connectors configuration, user queries and current page text context are transmitted via secure HTTPS API requests to the configured AI provider. No data is stored or logged on external servers managed by this plugin's author; all captured customer leads are saved locally in your WordPress database.

== Installation ==

1. Upload the `shihela-contextual-site-assistant` folder to the `/wp-content/plugins/` directory, or search for "Shihela Contextual Site Assistant" via the WordPress plugin dashboard and click Install.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your preferred AI Provider globally in the WordPress admin panel under **Settings > Connectors**.
4. Navigate to **Shihela Site Assistant** in the WordPress admin panel sidebar to customize your chatbot branding and greeting instructions.
5. Save changes and the widget will appear on your front-end pages.

== Frequently Asked Questions ==

= How does the AI know the page context? =
When a chat is initiated, the plugin retrieves the current page ID and queries WordPress for its title and plain text body. The first 500 words are sent along with the prompt to provide the context.

= Can I choose which AI provider to use? =
Yes. You can manage and switch your AI providers globally inside WordPress under **Settings > Connectors**. This plugin automatically uses whichever model and credentials are set as default by the administrator.

== Screenshots ==

1. screenshot-1.png - The floating Shihela Contextual Site Assistant chatbot interface on the front-end.
2. screenshot-2.png - Main settings panel in the WordPress dashboard for assistant configuration.
3. screenshot-3.png - Lead management system showing captured contact data and metrics.

== Changelog ==

= 1.0.0 =
* Initial release of Shihela Contextual Site Assistant widget.
* Integrated native WordPress 7.0+ AI Client API.
* Added Role-based Access Control (Public, Logged-in, Admin Only).
* Implemented Per-IP Daily Limit to prevent quota leakage by single visitors.
* Implemented Global Daily API request budget limits.
* Added Spam Honeypot protection.
* Added History Depth control to optimize API token utilization.
* Refactored REST API permissions callback for compliance with WordPress reviewer guidelines.
* Added IP rate limiting and session-based lead capturing.
