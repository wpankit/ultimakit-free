#!/usr/bin/env python3
"""
Generate the WordPress.org plugin directory assets for UltimaKit for WP.

One SVG per locale/direction is authored at 772x250 and rendered at both 772x250 and
1544x500, so the standard and retina banners are pixel-identical in composition.

Palette matches the plugin's admin UI (admin/css/main.css design tokens).
"""

import io
import os

OUT = os.path.dirname(os.path.abspath(__file__)) + '/wporg-assets'
os.makedirs(OUT, exist_ok=True)

MARK_PATH = io.open(os.path.dirname(os.path.abspath(__file__)) + '/mark-path.txt', encoding='utf-8').read().strip()

# Brand tokens, aligned with :root in admin/css/main.css
BRAND_DEEP = '#3b2fb8'
BRAND = '#6366f1'
ACCENT = '#8b5cf6'
ACCENT_HI = '#a78bfa'

FONT = "'Helvetica Neue', Helvetica, Arial, sans-serif"


def defs(uid):
    return f'''
  <defs>
    <linearGradient id="bg{uid}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{BRAND_DEEP}"/>
      <stop offset="52%" stop-color="{BRAND}"/>
      <stop offset="100%" stop-color="{ACCENT}"/>
    </linearGradient>
    <radialGradient id="glow{uid}" cx="0.5" cy="0.5" r="0.5">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.30"/>
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="sheen{uid}" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.16"/>
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0.04"/>
    </linearGradient>
    <symbol id="mark{uid}" viewBox="9.9 12.5 38.5 38.5">
      <path fill="#ffffff" fill-rule="nonzero" d="{MARK_PATH}"/>
    </symbol>
  </defs>'''


def module_card(x, y, w, h, on, uid, rtl=False):
    """A translucent module card with a toggle, echoing the plugin dashboard."""
    track_op = '0.55' if on else '0.22'

    # Mirror the internal layout for RTL so the toggle sits on the reading-end side.
    if rtl:
        line_x = x + w - 18
        line1_w = int(w * 0.44)
        line2_w = int(w * 0.66)
        line1_x = line_x - line1_w
        line2_x = line_x - line2_w
        track_x = x + 18
        knob_x = track_x + 8 if on else track_x + 26
    else:
        line1_x = line2_x = x + 18
        line1_w = int(w * 0.44)
        line2_w = int(w * 0.66)
        track_x = x + w - 52
        knob_x = track_x + 26 if on else track_x + 8

    return f'''
    <g>
      <rect x="{x}" y="{y}" width="{w}" height="{h}" rx="14"
            fill="url(#sheen{uid})" stroke="#ffffff" stroke-opacity="0.22" stroke-width="1.2"/>
      <rect x="{line1_x}" y="{y + 18}" width="{line1_w}" height="7" rx="3.5" fill="#ffffff" fill-opacity="0.72"/>
      <rect x="{line2_x}" y="{y + 34}" width="{line2_w}" height="5" rx="2.5" fill="#ffffff" fill-opacity="0.34"/>
      <rect x="{track_x}" y="{y + 15}" width="34" height="18" rx="9" fill="#ffffff" fill-opacity="{track_op}"/>
      <circle cx="{knob_x}" cy="{y + 24}" r="6.5" fill="#ffffff" fill-opacity="{'1' if on else '0.75'}"/>
    </g>'''


def chip(x, y, w, label, uid):
    return f'''
    <g>
      <rect x="{x}" y="{y}" width="{w}" height="27" rx="13.5" fill="#ffffff" fill-opacity="0.15"/>
      <text x="{x + w / 2}" y="{y + 18.5}" font-family="{FONT}" font-size="13" font-weight="600"
            fill="#ffffff" fill-opacity="0.95" text-anchor="middle">{label}</text>
    </g>'''


def banner(title, tagline, chips, rtl=False, uid='a'):
    W, H = 772, 250
    anchor = 'end' if rtl else 'start'
    # Content block sits on the left for LTR, mirrored to the right for RTL.
    pad = 46
    mark_size = 58
    if rtl:
        mark_x = W - pad - mark_size
        text_x = W - pad - mark_size - 20
    else:
        mark_x = pad
        text_x = pad + mark_size + 20

    # decorative cards occupy the opposite side
    cards = ''
    card_w, card_h = 196, 62
    cx = pad if rtl else W - pad - card_w
    for i, on in enumerate((True, True, False)):
        cards += module_card(cx, 30 + i * 72, card_w, card_h, on, uid, rtl)

    chip_svg = ''
    chip_y = 176
    if rtl:
        run_x = W - pad
        for label, w in chips:
            run_x -= w
            chip_svg += chip(run_x, chip_y, w, label, uid)
            run_x -= 10
    else:
        run_x = pad
        for label, w in chips:
            chip_svg += chip(run_x, chip_y, w, label, uid)
            run_x += w + 10

    return f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" width="{W}" height="{H}" role="img" aria-label="{title}">{defs(uid)}
  <rect width="{W}" height="{H}" fill="url(#bg{uid})"/>
  <ellipse cx="{W - 120 if not rtl else 120}" cy="20" rx="330" ry="200" fill="url(#glow{uid})"/>
  <g opacity="0.9">{cards}
  </g>
  <use href="#mark{uid}" x="{mark_x}" y="46" width="{mark_size}" height="{mark_size}"/>
  <text x="{text_x}" y="86" font-family="{FONT}" font-size="40" font-weight="700"
        fill="#ffffff" text-anchor="{anchor}" letter-spacing="-0.6">{title}</text>
  <text x="{text_x if not rtl else W - pad}" y="127" font-family="{FONT}" font-size="16" font-weight="500"
        fill="#ffffff" fill-opacity="0.86" text-anchor="{anchor}">{tagline}</text>
{chip_svg}
</svg>
'''


def icon():
    S = 256
    uid = 'i'
    return f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {S} {S}" width="{S}" height="{S}" role="img" aria-label="UltimaKit for WP">{defs(uid)}
  <rect width="{S}" height="{S}" fill="url(#bg{uid})"/>
  <ellipse cx="196" cy="34" rx="150" ry="110" fill="url(#glow{uid})"/>
  <!-- No decorative ring: the mark's motion streaks crossed it, and a plainer icon holds
       up better at the 128px (and smaller) sizes the plugin directory renders. -->
  <use href="#mark{uid}" x="62" y="62" width="132" height="132"/>
</svg>
'''


VARIANTS = {
    'banner': dict(
        title='UltimaKit for WP',
        tagline='Admin, speed &amp; security toolkit for WordPress.',
        chips=[('180 modules', 112), ('Admin', 78), ('Speed', 78), ('Security', 92)],
        rtl=False, uid='a',
    ),
    'banner-rtl': dict(
        title='UltimaKit for WP',
        tagline='Admin, speed &amp; security toolkit for WordPress.',
        chips=[('180 modules', 112), ('Admin', 78), ('Speed', 78), ('Security', 92)],
        rtl=True, uid='r',
    ),
    'banner-es': dict(
        title='UltimaKit for WP',
        tagline='Administración, velocidad y seguridad.',
        chips=[('180 módulos', 116), ('Admin', 78), ('Velocidad', 100), ('Seguridad', 104)],
        rtl=False, uid='e',
    ),
}

for name, kw in VARIANTS.items():
    io.open(f'{OUT}/{name}.svg', 'w', encoding='utf-8').write(banner(**kw))
    print('wrote', name + '.svg')

io.open(f'{OUT}/icon.svg', 'w', encoding='utf-8').write(icon())
print('wrote icon.svg')
