=== OW Google Reviews ===
Contributors: orangewidow
Tags: google reviews, testimonials, gutenberg, business profile, oauth
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Sync Google Business reviews to your WordPress database and display them with Gutenberg blocks.

== Description ==

OW Google Reviews connects each website to its own Google Cloud project via OAuth 2.0, pulls every historical Google Business review into a local database table, and keeps them synchronized daily. Reviews are rendered server-side from the database, so page loads never hit Google's API.

**Features**

* Per-site OAuth 2.0 connection (no shared global API project required).
* Automatic token refresh for hands-off daily syncing.
* Two Gutenberg blocks:
  * Recent Google Reviews — configurable number of reviews with optional "View all" button.
  * All Google Reviews — displays every stored review.
* Google-style review cards with avatar, name, star rating, date, and review text.
* Duplicate prevention using INSERT ... ON DUPLICATE KEY UPDATE.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/ow-google-reviews/`.
2. Activate the plugin through the Plugins menu.
3. Go to **Tools > Google Reviews**.
4. Follow the setup guide to create a Google Cloud project, enable the required APIs, and paste your OAuth credentials.
5. Connect your Google account, select your Business Profile account/location, and sync.

Note: This plugin is designed to work with a Google Cloud project in OAuth Testing mode. Google issues refresh tokens that expire after 7 days while in Testing mode, so you will need to reconnect your Google account roughly once a week. For a permanent solution, move the project to Production and complete Google's verification process.

== Frequently Asked Questions ==

= Why do I need my own Google Cloud project? =

Google requires app verification for shared/global API projects. By using your own private project and adding yourself as a test user, you avoid the verification bottleneck while retaining full access to the Business Profile API.

= Does this plugin work without Gutenberg? =

The included display blocks are Gutenberg blocks. The sync engine and database work independently and can be used with custom templates or shortcodes if needed.

== Changelog ==

= 1.0.0 =
* Initial release.
