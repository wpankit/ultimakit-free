# WordPress.org listing assets

The banners and icons for the plugin's WordPress.org page are the PNGs in
[`.wordpress-org/`](../../.wordpress-org) at the repository root. They are **not** part
of the plugin zip: they live in the SVN repository's `assets/` directory, next to
`trunk/` and `tags/`.

```
https://plugins.svn.wordpress.org/ultimakit-for-wp/
├── assets/      <- the PNGs in .wordpress-org/ go here
├── tags/
└── trunk/       <- the plugin itself
```

The release deploy and the **Update readme and assets on WordPress.org** workflow copy
`.wordpress-org/` to `assets/`, and remove anything in `assets/` that isn't in the
folder. Keep only the listing images there; this folder holds their sources.

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

The PNGs are rendered from the SVG sources in this folder. One SVG is authored per
locale/direction at 772×250 and rasterised at both sizes, so the standard and retina
banners are identical in composition rather than one being an upscale of the other.

```bash
cd tools/wporg-assets
python3 build-assets.py   # writes the SVGs in this folder
./render.sh               # rasterises them to PNGs in .wordpress-org/
```

`render.sh` uses headless Google Chrome at its macOS path. Any SVG rasteriser works
instead, for example `rsvg-convert -w 1544 -h 500 banner.svg -o banner-1544x500.png`.

## Design notes

- The gradient and palette come from the plugin's own admin UI design tokens in
  `admin/css/main.css` (`--uk-brand` `#6366f1`, `--uk-accent` `#8b5cf6`).
- The shield mark is the existing brand mark, lifted from `admin/img/wp-ultimakit-logo.svg`
  (`mark-path.txt`), so the directory listing matches the plugin itself.
- The decorative cards echo the module cards and toggles on the plugin dashboard. In the
  RTL banner the whole composition — including the toggles inside those cards — is mirrored.
- Text is live SVG text set in a Helvetica/Arial stack. If you regenerate on a machine with
  different fonts installed, re-check the taglines for overrun against the cards; the
  Spanish string in particular was shortened to fit.
