# Contributing to UltimaKit for WP

Thanks for helping improve UltimaKit. This guide explains how to report problems, suggest improvements and send code.

UltimaKit is in maintenance mode: it gets bug and security fixes and stays compatible with new WordPress versions. New modules are unlikely, but fixes and improvements to the existing ones are welcome.

## Questions and support

Please ask on the [WordPress.org support forum](https://wordpress.org/support/plugin/ultimakit-for-wp/). GitHub issues are for bugs and improvement ideas.

## Reporting a bug

Search the [open issues](https://github.com/wpankit/ultimakit-for-wp/issues) first. If the bug is new, open a [bug report](https://github.com/wpankit/ultimakit-for-wp/issues/new?template=bug_report.yml) with the module involved, its settings, the steps to reproduce it, and your plugin, WordPress and PHP versions.

Found a security issue? Please don't open an issue; follow [SECURITY.md](SECURITY.md) instead.

## Suggesting an improvement

Open a [feature request](https://github.com/wpankit/ultimakit-for-wp/issues/new?template=feature_request.yml) and describe the problem it would solve.

## Translating

Translations are managed on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/ultimakit-for-wp/).

## Contributing code

### Set up

1. Run WordPress locally, for example with [Local](https://localwp.com/), [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) or [DDEV](https://ddev.com/).
2. Fork this repository and clone your fork into `wp-content/plugins/ultimakit-for-wp`.
3. Run `composer install` to get the coding standards tools.
4. Activate the plugin and open **UltimaKit** in the admin menu to switch modules on and off.

To work on the WooCommerce or Gravity Forms modules, activate that plugin too; their modules only appear when it is active.

### How modules work

Each module is a folder under `modules/`. The modules that used to be in UltimaKit Pro, including all the Gravity Forms ones, are under `modules/pro-modules/`. A module folder holds:

- `metadata.json`: the module's ID, name, description, category, type (for example `WordPress`, `WooCommerce` or `Gravity Forms`) and whether it has settings;
- `class-wpultimakit-module-<folder>.php`: the class `UltimaKit_Module_<Folder>` (the folder name with `-` replaced by `_`), which extends `UltimaKit_Module_Manager`.

The loader finds modules by these file and class names, so keep them. A module adds its hooks in `initializeModule()`, only when it is switched on. A module that is off must not run any code or add anything to the site.

### Make your change

- Create a branch from `main`. `main` is protected, so every change arrives through a pull request.
- Keep each pull request to one topic.
- Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/). The plugin is being brought up to them gradually, so the check on pull requests only looks at the lines you add or change. Commit your work, then run `composer lint:changed` to check those lines the same way. `composer lint -- path/to/file.php` lists every issue in a file.
- The code must keep working on PHP 7.4 and WordPress 5.6.
- Prefix new functions, hooks and options with `ultimakit_` and new classes with `UltimaKit_`, and use the `ultimakit-for-wp` text domain. Keep existing names, module IDs and stored settings: sites and other code rely on them.
- Escape output, sanitize input, and check capabilities and nonces.

### Test it

Before opening the pull request, with `WP_DEBUG` and `WP_DEBUG_LOG` on:

- switch on the modules your change touches, and check the pages they affect, on the front end and in the admin;
- check `debug.log` for new PHP errors, warnings or notices;
- switch the modules off again and check the site is back to normal;
- for modules that control access (Change Login URL, Maintenance Mode, Password Protection, Allow Only Logged-In Users, Force SSL), check that you can still log in.

### Open the pull request

Fill in the template: what changed, why, and how you tested it. The checks must pass before the pull request is merged: Coding Standards, PHP Lint and Build plugin zip. Plugin Check reports on the whole plugin; it doesn't fail yet, while the existing issues are being fixed.

## Code of Conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md). By taking part, you agree to follow it.

## License

By contributing, you agree that your contributions are licensed under the [GPL-2.0-or-later](LICENSE) license.
