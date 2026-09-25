![UltimaKit for WP](.wordpress-org/banner-1544x500.png)

# UltimaKit for WP

[![WordPress.org](https://img.shields.io/wordpress/plugin/v/ultimakit-for-wp?label=WordPress.org)](https://wordpress.org/plugins/ultimakit-for-wp/)
[![Active installs](https://img.shields.io/wordpress/plugin/installs/ultimakit-for-wp)](https://wordpress.org/plugins/ultimakit-for-wp/)
[![Rating](https://img.shields.io/wordpress/plugin/rating/ultimakit-for-wp)](https://wordpress.org/support/plugin/ultimakit-for-wp/reviews/)
[![Tested up to](https://img.shields.io/wordpress/plugin/tested/ultimakit-for-wp)](https://wordpress.org/plugins/ultimakit-for-wp/)
[![PHP Lint](https://github.com/wpankit/ultimakit-for-wp/actions/workflows/php-lint.yml/badge.svg)](https://github.com/wpankit/ultimakit-for-wp/actions/workflows/php-lint.yml)
[![Build plugin zip](https://github.com/wpankit/ultimakit-for-wp/actions/workflows/build-plugin-zip.yml/badge.svg)](https://github.com/wpankit/ultimakit-for-wp/actions/workflows/build-plugin-zip.yml)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)

180 admin, security and optimization tools in one WordPress plugin. Every tool is a module you switch on or off, and a module that is off loads nothing.

UltimaKit is free and GPL-licensed. Since version 3.0.0 there is no Pro version: the modules that used to be sold as UltimaKit Pro, including the WooCommerce and Gravity Forms modules, are part of the free plugin.

- **WordPress.org:** [wordpress.org/plugins/ultimakit-for-wp](https://wordpress.org/plugins/ultimakit-for-wp/)
- **Support forum:** [wordpress.org/support/plugin/ultimakit-for-wp](https://wordpress.org/support/plugin/ultimakit-for-wp/)
- **Made by:** [WPAnkit](https://wpankit.com/)

## What's inside

| Area | Examples |
| --- | --- |
| Admin | Hide admin notices, clean up the admin bar, ID and featured-image columns, quick admin search |
| Security | Change the login URL, limit login attempts, force strong passwords, disable XML-RPC, lock the site URL |
| Performance | Disable emojis, embeds and jQuery Migrate, remove the block library CSS, limit post revisions |
| Content | Duplicate posts and pages, drag-and-drop post order, media replacement, SVG upload |
| Power tools | Activity log, redirect manager, custom post types and taxonomies, maintenance mode, schema markup |
| WooCommerce (18) | Product timer, sale badge text, hide out-of-stock products, disable guest checkout |
| Gravity Forms (12) | Smart phone field, date-time field, email blacklist, pre-submission preview |

The full list is in [README.txt](README.txt).

## Requirements

- WordPress 5.6 or later
- PHP 7.4 or later

WooCommerce and Gravity Forms modules appear only when those plugins are active.

## Installing

Install UltimaKit from WordPress.org (**Plugins → Add New**, search for "UltimaKit"), then open **UltimaKit** in the admin menu and switch on the modules you want.

## Moving from UltimaKit Pro

Install UltimaKit for WP from WordPress.org and activate it. That switches UltimaKit Pro off and keeps your settings; then delete Pro. A few Pro modules were retired rather than carried over. The FAQ in [README.txt](README.txt) lists them and explains what to check before you switch.

## Project status

UltimaKit is in maintenance mode: it gets security fixes and stays compatible with new WordPress versions. New modules are unlikely. Bug reports and pull requests are welcome.

## Development

Clone the repository into a WordPress site's plugins folder as `ultimakit-for-wp`, and install the coding standards tools:

```bash
cd wp-content/plugins
git clone https://github.com/wpankit/ultimakit-for-wp.git ultimakit-for-wp
cd ultimakit-for-wp
composer install
```

The repository is the plugin, so there is no build step: activate it and open **UltimaKit** in the admin menu.

| Command | What it does |
|---|---|
| `composer lint:changed` | Checks the lines your commits change, compared with `origin/main`, against the WordPress Coding Standards and PHP 7.4+ compatibility. The pull request check does the same. |
| `composer lint` | Checks the whole plugin, using `phpcs.xml.dist`. The older code still has issues that are being fixed, so expect a long list. |
| `composer format` | Fixes the issues that can be fixed automatically. |

### Project layout

| Path | Contents |
|---|---|
| `wp-ultimakit.php` | Plugin header, constants and bootstrap |
| `includes/` | Module loader and manager, settings storage, activation, and the `.htaccess` and `wp-config.php` helpers |
| `modules/` | One folder per module, with its `metadata.json` and class; the former Pro modules are in `modules/pro-modules/` |
| `admin/` | The UltimaKit dashboard and its assets |
| `public/` | Front-end class and assets |
| `src/freemius/` | The Freemius SDK, used only for opt-in usage data; not checked against the coding standards |
| `languages/` | Translations |
| `.wordpress-org/` | Icon and banners for the WordPress.org listing |
| `tools/wporg-assets/` | The sources and scripts that build those images |

`.distignore` lists the development files that stay out of the plugin zip.

### Checks on every pull request

- **Coding Standards:** PHPCS with the WordPress Coding Standards and PHPCompatibilityWP, on the lines the pull request changes.
- **PHP Lint:** every PHP file, including the Freemius SDK, must parse on PHP 7.4 through 8.5.
- **Build plugin zip:** builds the plugin zip from `.distignore` and checks nothing is missing or left over; the zip is kept as a workflow artifact for testing.
- **Plugin Check:** the official WordPress.org Plugin Check, run on the plugin as it ships. For now it only reports, while the existing issues are fixed.

## Releasing

For maintainers:

1. In a pull request, set the new version in the plugin header, in `ULTIMAKIT_FOR_WP_VERSION` and in the `Stable tag` of `README.txt`, and add the changelog entry.
2. Merge it, then [publish a release](https://github.com/wpankit/ultimakit-for-wp/releases/new) from `main` with the tag `vX.Y.Z`.
3. The **Build plugin zip** workflow checks the three version numbers match the tag, builds the zip and attaches it to the release, then commits the zip's contents to SVN as `trunk` and `tags/X.Y.Z` and updates the listing assets.

Running **Build plugin zip** by hand does the same as a dry run, without committing to SVN. To publish readme or listing-asset changes without a release, run the **Update readme and assets on WordPress.org** workflow by hand. Both need the repository secrets `SVN_USERNAME` and `SVN_PASSWORD`.

## Contributing

Bug reports, fixes and pull requests are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) first; everyone taking part follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## Reporting a security issue

Please don't open a public issue. Report it privately, as described in [SECURITY.md](SECURITY.md), so the report stays private until a fix is released.

## License

[GPL-2.0-or-later](LICENSE). Made by [WPAnkit](https://wpankit.com/).
