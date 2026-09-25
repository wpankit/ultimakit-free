=== UltimaKit – Admin Enhancements, Security, Optimization & Site Tweaks ===
Contributors: ankitmaru, siapanchal
Tags: admin, enhancements, optimization, security, tweaks
Requires at least: 5.6
Tested up to: 7.1.2
Stable tag: 3.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

180 admin, security and optimization tools in one plugin, all free. Enable only what you need; disabled modules never load.

== Description ==

**UltimaKit** consolidates the admin enhancements, site optimizations, security hardening and everyday tweaks you would normally install a dozen separate plugins for — into one modular toolkit.

Every capability is a **module** you switch on or off individually. Nothing is forced on you, and a module you have not enabled contributes no code, no hooks and no queries to your site.

= Everything is free =

Since version 3.0.0 there is no Pro version. The modules that used to be sold as UltimaKit Pro, including the WooCommerce and Gravity Forms modules, are included in this plugin. A few Pro modules that were unreliable or unsafe were retired instead (see the FAQ).

= Why a modular toolkit? =

Every single-purpose plugin you add brings its own update cycle, its own settings page, its own autoloaded options and its own attack surface. Ten small plugins is ten things to keep patched.

UltimaKit gives you one plugin to update, one place to configure, and one codebase to trust — with **180 modules, all free**.

= Built to stay out of the way =

Bundling a lot of features usually means bundling a lot of overhead. UltimaKit is engineered specifically to avoid that:

* **Disabled modules never load.** A module that is off is not instantiated, its class file is never read from disk, and none of its hooks are registered.
* **Settings load in a single query.** All module configuration is read once per request and cached, rather than queried module by module.
* **Module discovery is cached.** The plugin does not scan its own directories on every page load.
* **Admin assets only load on UltimaKit screens.** Nothing is enqueued on the rest of your dashboard, and nothing at all is enqueued on the front end unless a module you enabled needs it.

= Content Management =

* **Duplicate Pages & Posts** — clone any post, page or custom post type in one click, including post meta, terms and taxonomies
* **Post & Page Order** — drag-and-drop ordering for posts, pages and custom post types
* **Media Replacement** — swap the file behind an existing attachment without breaking its URL
* **SVG Upload** — allow SVG logos and icons in the Media Library, sanitized on upload
* **Auto-Generate Page Slugs** — clean, readable slugs created automatically
* **Featured Image Column** and **ID Column** — see thumbnails and post IDs directly in admin list tables
* **Change Excerpt Length** and **Read More Text**
* **Limit Post Revisions** — stop revision tables growing without bound
* **Content Expiry Reminder**, **Dynamic Year shortcode**, **Lowercase Filenames for Uploads**

= Admin Interface =

* **Hide Admin Notices** — move nagging plugin banners into a dedicated panel instead of your workflow
* **Hide Admin Bar** and **Cleanup Admin Bar** — remove "Howdy", the WordPress logo and other clutter
* **Empty Admin Dashboard** — hand clients a dashboard without widget noise
* **Enhance List Tables**, **Add ID Column**, **Hide Screen Options Tab**
* **Hide WordPress Version Number**
* **Remove Dashboard Welcome Panel**, **Remove Login Shake Animation**
* **Clean User Profiles** — strip the fields your site does not use

= Log In / Out & Register =

* **Change Login URL** — move wp-login.php to a custom slug
* **Login Attempts** — limit failed logins and lock out repeat offenders
* **Auto-Logout Inactive Users** — close idle admin sessions
* **Extend Login Expiration Time**
* **Hide Login Errors** — stop the login form confirming which half of the credentials was wrong
* **Disable Login by Email**, **Disallow User Registration**, **Disable New User Notifications**
* **User Last Login Timestamp**

= Security =

* **Block "Admin" Username** — refuse the single most brute-forced username
* **Force Strong Password** and **Force SSL**
* **Disallow WP File Edit** — remove the theme and plugin editors
* **Disallow Bad Requests** — block common malicious query strings
* **Disallow Directory Listing**
* **Password Protection** — put the whole site behind a password
* **Allow Only Logged-In Users**
* **Lock Site URL** and **Lock Admin Email** — prevent silent takeover via changed URLs or email
* **Obfuscate Email Addresses** — stop scrapers harvesting mailto: links
* **Disallow Plugin Upload** / **Disallow Theme Upload**

= Disable Components =

Thirty-nine one-click switches for the parts of WordPress you do not use — each one removing queries, requests or output:

* **Disable Comments**, **Disable XML-RPC**, **Disable REST API**
* **Disable Gutenberg / Full Site Editing / Widget Blocks**
* **Disable Emojis**, **Disable Embeds**, **Disable RSS Feeds**
* **Disable Auto Updates** and **Automatic Update Emails**
* **Disable Application Passwords**, **Disable Attachment Pages**, **Disable Author Archives**
* **Disable Block Directory**, **Disable jQuery Migrate**, **Disable Sitemaps**
* …and many more

= Optimizations =

* **Remove Query Strings From Static Files**
* **Remove WP Block Library CSS** — drop the block stylesheet on sites that do not need it
* **Broken Link Checker**
* **Meta Tag Editor** — titles and meta descriptions without a full SEO suite
* **Redirect 404 to Homepage**
* **Featured Image Checker**, **Navigation Menu Visibility**, **Set oEmbed Max Width**

= Custom Code =

* **Custom CSS** and **Custom JS** — site tweaks without touching your theme
* **Custom Admin Footer**
* **Pixel Tag Manager** — Google Analytics, Meta Pixel and Pinterest tags without another plugin

= Utilities =

* **Sitemap Generator**
* **Top Notification Bar** — announcement bar with full styling control
* **Change Outgoing Email Sender** and **Force Send All Email To** (useful on staging)
* **GDPR Compliance Tool** — cookie consent notice
* **Add Page Slug to Body Class**

= Power tools =

Modules for agencies and power users that used to be sold as Pro:

* **Admin Activity Logger** — a full audit trail of who changed what
* **Custom Post Type / Taxonomy** builder — no code required
* **Redirect Manager** — 301/302 redirects with a proper UI
* **Maintenance Mode**, **Schema Markup Generator**, **Robots.txt Editor**
* **Multiple User Roles**, **Admin UI** customizer, **Quick Admin Search**
* SEO helpers: **SEO Title & Meta Description Editor**, **Readability Score**, **Keyword Density Checker**, **Keyword Suggestion**, **Image Alt Text Checker**, **Duplicate Content Checker**, **Post Publish Checklist**

= WooCommerce =

Eighteen store-specific modules:

* **Disable Guest Checkout**, **Auto-Redirect to Checkout on Add to Cart**, **Auto Apply Coupons**
* **Hide Out Of Stock Products**, **Show Free Shipping Only**, **Show Stock Level Display**
* **Product Timer**
* **Custom Add to Cart Button Text** and **Custom Sale Badge Text**
* **Remove Related Products**, **Remove Product Permalink on Shop**, **Disable Product Sorting Dropdown**, **Disable Breadcrumbs**
* **Change "$0.00" to "Free"**, **Show SKU on Archive Pages**, **Hide Quantity Selector**, **Disable Reviews**, **Disable WooCommerce Widgets**

= Gravity Forms =

Twelve modules for Gravity Forms, including **Smart Phone Field**, **Date-Time Field**, **Simple Range Slider**, **Email Blacklist**, **Pre-Submission Preview**, **Pre-Filled Form Fields via URL**, **Copy Form Data to Clipboard** and **Lock Submissions After a Deadline**.

== Installation ==

1. Install through the WordPress plugin installer, or upload the zip via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin through the **Plugins** menu.
3. Open **UltimaKit** in the admin sidebar.
4. Switch on only the modules you need. Anything left off stays completely inactive.

== Frequently Asked Questions ==

= Will enabling a lot of modules slow my site down? =

No. Modules are only loaded when enabled — a module that is off is never instantiated and registers no hooks, so it costs nothing. All module settings are read in a single cached query per request rather than one query per module, and admin assets load only on UltimaKit's own screens.

= Why use one toolkit instead of separate plugins? =

Fewer moving parts. Each additional plugin is another update to track, another settings page to learn, another set of autoloaded options, and another potential vulnerability. One well-maintained plugin covering the same ground is easier to keep patched and easier to hand to a client.

= Is there a Pro version? =

No. Since 3.0.0 every module is free, including the ones that used to be sold as UltimaKit Pro.

= I bought UltimaKit Pro. What should I do? =

Install UltimaKit for WP from WordPress.org and activate it. Activating it switches UltimaKit Pro off automatically, and your module settings stay as they are. Then delete UltimaKit Pro. From then on, updates come from WordPress.org.

Before you switch, check the list of retired modules below. If you rely on one of them, keep UltimaKit Pro, or set up a replacement first. If you use Debug Mode or Media Trash, turn them off in Pro before switching: they edit wp-config.php, and the free plugin cannot undo that for you.

= Which Pro modules were retired? =

These Pro modules were unreliable or unsafe, so they are not in the free plugin: AI-Powered Response Analysis, Address Autocomplete, Advanced Custom JS & CSS, Ban Emails, Blacklisted Usernames, Code Snippet, Content Backup, Debug Mode, Export Posts & Pages, Export Users, Form Analytics, Front-End Login Form, Limit Uploaded Image Size, Media Trash, Obfuscate Author Slugs, Post Expiry Manager, Restrict WP-Admin Access for Non-Administrators, Set a Minimum Word Count for Posts, Set Custom Upload Folder, SMTP Email, Unique ID For Entries, Woo - Disable Coupons and Woo - Limit Purchase Quantity.

= Does it work with WooCommerce? =

Yes. There are eighteen WooCommerce-specific modules covering checkout behaviour, catalogue display, stock visibility and store cleanup. They only load when WooCommerce is active.

= Does it work with Gravity Forms? =

Yes. There are twelve Gravity Forms modules. They appear in the dashboard only when Gravity Forms is installed and active.

= Can I move my configuration between sites? =

Yes. Settings can be exported to a JSON file and imported on another site.

= Is it translation ready? =

Yes. UltimaKit ships with translations for a number of languages and is fully translatable.

== External services ==

UltimaKit does not contact any outside service unless you switch on a module that needs one. These modules connect to third-party services:

* **Keyword Suggestion** sends the keyword you type in the post editor to Google's autocomplete service (suggestqueries.google.com) when you ask for suggestions. Google [terms](https://policies.google.com/terms) and [privacy policy](https://policies.google.com/privacy).
* **Pixel Tag Manager** adds the tracking tags you configure to your site: the Google tag (googletagmanager.com), the Meta Pixel (connect.facebook.net, facebook.com) and the Pinterest Tag (s.pinimg.com, ct.pinterest.com). Only tags whose IDs you enter are added. Visitors' browsers then send page-view data to those services. Google [terms](https://policies.google.com/terms) and [privacy policy](https://policies.google.com/privacy); Meta [terms](https://www.facebook.com/legal/terms) and [privacy policy](https://www.facebook.com/privacy/policy/); Pinterest [terms](https://policy.pinterest.com/en/terms-of-service) and [privacy policy](https://policy.pinterest.com/en/privacy-policy).
* **Broken Link Checker** sends a request to each link in your content to check that it still works. Only the link itself is requested; no site data is sent.
* **Usage data (Freemius)**: when you activate UltimaKit you can choose to opt in to sharing usage data. Only if you opt in, your profile (name and email), basic site details (URL, WordPress and PHP versions) and plugin and theme events are sent to Freemius (freemius.com). Freemius [terms](https://freemius.com/terms/) and [privacy policy](https://freemius.com/privacy/).

== Screenshots ==

1. The UltimaKit module dashboard — browse by category and enable only what you need.
2. Per-module settings, opened inline from the module card.

== Changelog ==

= 3.0.1 =

* CHANGED: Confirmed compatibility with WordPress 7.1.2.

= 3.0.0 =

**Pro modules are now free**

* NEW: 47 former Pro modules now ship in the free plugin, including 8 WooCommerce and 12 Gravity Forms modules. UltimaKit Pro is no longer sold.
* REMOVED: 24 Pro modules were retired because they were unreliable or unsafe. The FAQ lists them.
* UltimaKit Pro users: activate this version from WordPress.org. Pro is switched off automatically and your settings stay. You can then delete Pro. Check the FAQ first if you use a retired module.
* CHANGED: Freemius now only handles the optional usage-data opt-in. Licensing, upgrade prompts and the add-ons store are gone.
* CHANGED: Plugin links now point to WordPress.org and wpankit.com.
* NEW: Declared compatibility with WooCommerce High-Performance Order Storage.
* FIXED: Disallow WP File Edit could write an empty wp-config.php, taking the site down, on sites that keep wp-config.php one directory above WordPress.
* FIXED: Change Login URL showed a 404 page instead of the login screen whenever the URL had a query string, which broke logging out, password resets, lost-password requests, password-protected posts and the redirect from /wp-admin/. It also failed on sites installed in a subdirectory.
* FIXED: Keyword Suggestion now requests suggestions over HTTPS.
* FIXED: Smart Phone Field no longer loads its stylesheet from a CDN; the intl-tel-input files now ship with the plugin.
* FIXED: Strings in several modules used the wrong text domain and could not be translated.
* FIXED: Activity Logs, Redirect Manager, Custom Post Type & Taxonomy and Login Attempts rebuilt their database tables on every page load.
* FIXED: Allow Only Logged-In Users could cause a fatal error on WordPress versions before 5.9.

**Security**

* SECURITY: Imported settings files are now validated and sanitized the same way as settings saved from the dashboard.
* SECURITY: Pre-Submission Preview no longer renders form values as HTML, which allowed script injection through pre-filled or resumed forms.
* SECURITY: Pre-Filled Form Fields no longer lets URL values run Gravity Forms merge tags, which could reveal post data to visitors.
* SECURITY: Post Type Switcher now checks a nonce and only allows the post types it offers.
* SECURITY: Auto Tagging and Readability Score now check that the user can edit the specific post.
* SECURITY: Keyword Suggestion requires the edit_posts capability and inserts suggestions as text.
* SECURITY: The Custom Post Type & Taxonomy builder sanitizes keys and labels, rejects names reserved by WordPress, and ignores callback arguments in stored settings.
* SECURITY: Activity Logs no longer records the values of options that look like passwords or keys, and its CSV export is protected against formula injection.
* SECURITY: Maintenance Mode, Scroll To Top, Custom Sale Badge Text, Custom Add to Cart Button Text and Product Timer now escape or validate their settings before output.
* SECURITY: On multisite, only super admins can overwrite the shared robots.txt or wp-config.php.
* SECURITY: Removed an unused debug logger from Redirect Manager that could write a public log file.
* SECURITY: Settings export is no longer written to a publicly readable file in the uploads directory; it is streamed through an authenticated, nonce-protected request instead.
* SECURITY: Hardened role assignment so that granting additional roles now requires the promote_users capability and is validated against the roles the current user may actually assign.
* SECURITY: SVG uploads are now sanitized before being stored, and the module no longer disables WordPress's own file-type verification for other uploads.
* SECURITY: Brute-force protection now uses the connection's real IP rather than trusting client-supplied headers, which could previously be varied to avoid lockout.
* SECURITY: Added capability checks to several module AJAX endpoints that previously relied on a nonce alone.
* SECURITY: Fixed a case where clearing the Password Protection exclusion list silently disabled protection for the entire site.
* SECURITY: Per-post permission checks added to the duplicate feature.

**Performance**

* IMPROVED: Module settings are now loaded once per request and cached, replacing hundreds of individual queries on every page load.
* IMPROVED: Module discovery is cached rather than re-scanning the plugin directory on every request.
* IMPROVED: Removed a redundant full re-initialisation of the module manager when rendering the dashboard.
* IMPROVED: Admin CSS payload reduced by roughly 90% by replacing the bundled Bootstrap builds with a purpose-built stylesheet.
* FIXED: Both the LTR and RTL Bootstrap stylesheets were being loaded together, so RTL rules were overriding layout on left-to-right admins.
* IMPROVED: The dashboard renders each module once instead of twice, halving the page markup and removing duplicate element IDs.

**Admin interface**

* NEW: Redesigned admin interface — new module cards, category sidebar, search and compact view.
* IMPROVED: Category filtering and search now run as a single pass over the module list.
* REMOVED: Promotional admin notices.

**Fixes**

* FIXED: Saving a module with a multi-select setting caused a fatal error on PHP 8.
* FIXED: Settings built from custom markup rendered as bare text with dead buttons: the Hide Admin Notices role grid, and the image pickers in Scroll To Top, Login Logo Customizer and Maintenance Mode.
* FIXED: Maintenance Mode locked administrators out when file editing was disabled.
* FIXED: Posts Per Page changed every front-end query, including widgets and blocks, instead of only the main query.
* FIXED: Auto Link Keywords inserted links inside existing links and shortcode output.
* FIXED: Schema Markup Generator lost the whole schema when a value contained an apostrophe, and mangled accented characters.
* FIXED: Duplicate Content Checker's monthly schedule never ran, and saving with no post type selected caused a fatal error.
* FIXED: Secure Registration blocked editing existing users, and missed disposable domains written in capitals or as subdomains.
* FIXED: Robots.txt Editor wrote a backup file to the site root on every save.
* FIXED: Email Blacklist could be bypassed with a leading space or a subdomain.
* FIXED: Custom Add to Cart Button Text showed a blank button when no text was set, and its block-editor script relabelled every Button block.
* FIXED: Custom Sale Badge Text showed empty badges when no text was set.
* FIXED: Product Timer printed an invalid width when none was set.
* FIXED: Date-Time Field caused a fatal error on every request if the Gravity Forms plugin folder was missing.
* FIXED: Custom Post Type & Taxonomy caused a database error the first time it loaded.
* NEW: Simple Range Slider and Smart Phone Field now validate submitted values on the server.
* FIXED: Lock Form Submissions After a Deadline only hid the form; submissions made after the deadline are now rejected server-side.
* FIXED: Copy Form Data to Clipboard caused a fatal error on forms using a redirect confirmation.
* FIXED: The Simple Range Slider field lost its stored value when an entry was edited in the admin.
* FIXED: Auto-Clear Form Field on Focus cleared checkboxes, radio buttons and file inputs, and applied to every form on the page.
* FIXED: Hide Form Field Labels also removed choice and sub-field labels.
* FIXED: Auto-Submit on Last Field could not fire when the honeypot was enabled.
* FIXED: Various PHP 8 warnings and undefined-variable notices across Gravity Forms modules.

= 2.3.1 =

* Tested with WordPress 7.0.

= 2.3.0 =

* FIXED: Change Login URL — login form was still posting to /wp-login.php instead of the custom slug, causing login to fail.
* FIXED: Change Login URL — removed an early redirect that ran before WordPress was fully initialised, which could cause headers-already-sent errors.
* FIXED: Admin UI — sidebar icon colour live preview targeted the wrong control.
* FIXED: Admin UI — colour pickers were initialised twice, causing UI conflicts.
* FIXED: Admin UI — sidebar width is now sanitised before being written into CSS.
* IMPROVED: Admin colour scheme aligned to the brand palette and several low-contrast combinations corrected.
* NEW: Code Snippet module — PHP, JavaScript and CSS snippets with per-snippet placement, priority and active toggle.
* FIXED: Form Analytics — corrected average completion time, submission rate and CSV export; removed double-counted submissions.
* FIXED: Simple Notification Bar — corrected admin-bar offset, escaping of the position value, and a fatal error when saving settings.
* IMPROVED: Simple Notification Bar settings moved to a full admin screen with a rich text editor.

= 2.2.0 =

* NEW: Additional Gravity Forms modules added to Pro.
* IMPROVED: Module manager performance.
* FIXED: Various UI and compatibility fixes.

= 2.1.0 =

* NEW: Gravity Forms modules added to Pro.
* IMPROVED: UI, performance and security hardening.

= 2.0.1 =

* IMPROVED: Duplicate Post now supports custom post types (thanks @jasD).
* FIXED: Minor CSS conflict with the admin bar on mobile.
* SECURITY: Hardened SVG upload sanitization.
* UPDATED: Freemius SDK.

= 1.8.5 =

* NEW: Add Featured Image Column, Change Login URL, Disallow Directory Listing, Force SSL, Force Strong Passwords.
* NEW (Pro): Ban Emails, Blacklisted Usernames, Multiple User Roles.
* IMPROVED: Complete redesign of the admin interface.

= 1.8.0 =

* NEW: Disallow Bad Requests, Lock Site URL, Password Protection.
* NEW: Translations for 8 additional languages.

== Upgrade Notice ==

= 3.0.0 =
Former Pro modules, including the WooCommerce and Gravity Forms ones, are now free; 24 unreliable Pro modules were retired. Also fixes security issues and a bug that could empty wp-config.php. Updating is strongly recommended.
