#!/bin/bash
# Render the authored SVGs to the exact PNG sizes the WordPress.org plugin directory expects.
# Headless Chrome is used because no SVG rasteriser (rsvg/ImageMagick/Inkscape) is installed.
set -euo pipefail

CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
# SVG sources sit next to this script; the PNGs go to .wordpress-org/ at the repository root.
SRC="$(cd "$(dirname "$0")" && pwd)"
OUT="$(cd "$SRC/../../.wordpress-org" && pwd)"
TMP="$SRC/.render"
mkdir -p "$TMP"

render() {  # render <svg> <width> <height> <outfile>
  local svg="$1" w="$2" h="$3" out="$4"
  local html="$TMP/$(basename "$out" .png).html"

  # Wrapper guarantees no margin/scrollbars so the raster is exactly w x h.
  cat > "$html" <<HTML
<!doctype html><meta charset="utf-8">
<style>
  html,body{margin:0;padding:0;overflow:hidden;background:transparent}
  svg{display:block;width:${w}px;height:${h}px}
</style>
$(cat "$SRC/$svg")
HTML

  "$CHROME" --headless --disable-gpu --hide-scrollbars \
    --force-device-scale-factor=1 --window-size="${w},${h}" \
    --default-background-color=00000000 \
    --screenshot="$OUT/$out" "file://$html" >/dev/null 2>&1

  printf '  %-34s %s\n' "$out" "$(sips -g pixelWidth -g pixelHeight "$OUT/$out" 2>/dev/null | awk '/pixel/{printf "%s ", $2}')"
}

echo "Rendering banners..."
render banner.svg      772  250 banner-772x250.png
render banner.svg     1544  500 banner-1544x500.png

render banner-rtl.svg  772  250 banner-772x250-rtl.png
render banner-rtl.svg 1544  500 banner-1544x500-rtl.png

render banner-es.svg   772  250 banner-772x250-es.png
render banner-es.svg  1544  500 banner-1544x500-es.png
render banner-es.svg   772  250 banner-772x250-es_ES.png
render banner-es.svg  1544  500 banner-1544x500-es_ES.png

echo "Rendering icons..."
render icon.svg        256  256 icon-256x256.png
render icon.svg        128  128 icon-128x128.png

rm -rf "$TMP"
echo "Done."
