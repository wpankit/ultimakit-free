# WordPress.org plugin directory assets

These files are **not** shipped in the plugin zip — they live in the WordPress.org SVN
repository's `assets/` directory, alongside `trunk/` and `tags/`:

```
https://plugins.svn.wordpress.org/ultimakit-for-wp/
├── assets/      <- the PNGs in this folder go here
├── tags/
└── trunk/       <- the plugin itself
```

If you deploy with the `10up/action-wordpress-plugin-deploy` GitHub Action, it reads this
`.wordpress-org/` folder by default and syncs it to `assets/` for you.

## Files

| File | Size | Used for |
| --- | --- | --- |
| `banner-772x250.png` | 772×250 | Standard banner |
| `banner-1544x500.png` | 1544×500 | High-DPI (retina) banner |
| `banner-772x250-rtl.png` | 772×250 | RTL locales (Arabic, Hebrew, …) |
| `banner-1544x500-rtl.png` | 1544×500 | RTL, retina |
| `banner-772x250-es.png` | 772×250 | Spanish |
| `banner-1544x500-es.png` | 1544×500 | Spanish, retina |
| `banner-772x250-es_ES.png` | 772×250 | Spanish (Spain) |
| `banner-1544x500-es_ES.png` | 1544×500 | Spanish (Spain), retina |
| `icon-256x256.png` | 256×256 | Plugin icon |
| `icon-128x128.png` | 128×128 | Plugin icon, standard DPI |

`-es` and `-es_ES` are byte-identical; WordPress.org matches the most specific locale it
finds, so shipping both covers `es_ES`, `es_MX`, `es_AR` and friends.

## Regenerating

The PNGs are rendered from the SVG sources in `src/`. One SVG is authored per
locale/direction at 772×250 and rasterised at both sizes, so the standard and retina
banners are identical in composition rather than one being an upscale of the other.

```bash
cd src
python3 build-assets.py   # writes the SVGs
./render.sh               # rasterises them to PNG
```

`render.sh` uses headless Google Chrome, because no SVG rasteriser (librsvg, ImageMagick,
Inkscape) is installed on this machine. Any of those would work as a drop-in replacement,
e.g. `rsvg-convert -w 1544 -h 500 banner.svg -o banner-1544x500.png`.

## Design notes

- The gradient and palette come from the plugin's own admin UI design tokens in
  `admin/css/main.css` (`--uk-brand` `#6366f1`, `--uk-accent` `#8b5cf6`).
- The shield mark is the existing brand mark, lifted from `admin/img/wp-ultimakit-logo.svg`
  so the directory listing matches the plugin itself.
- The decorative cards echo the module cards and toggles on the plugin dashboard. In the
  RTL banner the whole composition — including the toggles inside those cards — is mirrored.
- Text is live SVG text set in a Helvetica/Arial stack. If you regenerate on a machine with
  different fonts installed, re-check the taglines for overrun against the cards; the
  Spanish string in particular was shortened to fit.
