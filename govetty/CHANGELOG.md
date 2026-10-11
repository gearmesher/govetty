# Go Vetty theme — changelog

The theme version lives in the `Version:` header of `style.css`. `functions.php`
reads it into `GO_VETTY_VERSION` and appends it (plus each file's mtime) to every
stylesheet, logo and favicon URL, so bumping that one line busts browser caches
on release.

## 1.1.0 — 2026-10-11 — Vetty rebrand

Applies the "Vetty — Branding" guidelines (arnold street.) across the theme.

### Brand system
- New `brand.css` (+ `brand.min.css`): all 12 brand colours as CSS variables
  (maroon-950/800/600/400, rose-300/100, cream-50, sand-100, ink-900/500/200,
  white), semantic roles, and the legacy `--x-1/4/6` aliases mapped to the new palette.
- Fonts self-hosted in `assets/fonts/`: Archivo variable (300 body, 600 UI and
  kickers, 800 headlines) and Instrument Serif. Replaces Inter, whose
  `@font-face` in `style.css` pointed at the staging domain.
- Instrument Serif is used only on standalone phrases: the homepage CTA
  "We're here for you and your pet." and About "Our Mission" / "Our Vision".
- Kickers are Archivo 600, uppercase, +0.14em tracking.

### Logo & favicons
- Vetty wordmark and favicons extracted from the brand PDF as vector SVGs into
  `assets/brand/`. The wordmark comes in maroon, black, white and white-on-maroon,
  each with PNG/WebP exports. The favicons come in red and dark, as SVG, ICO and
  16–512px PNGs.
- Header and footer load the SVG wordmark via `go_vetty_brand_logo()`. This
  removes two `attachment_url_to_postid()` database lookups per page.
- Favicons are output only when no WordPress Site Icon is set.

### Layout fixes
- Homepage: header and footer CSS are now inlined along with brand and home CSS.
  Previously only `home.min.css` was inlined.
- `home.css` rules are scoped to `.site-main.home`. They previously also matched
  the header and footer through `body.home`.
- `header.css` and `footer.css` carry their own `box-sizing` reset. This fixes the
  oversized Log In / Talk to a Vet Now buttons on the homepage, which doesn't load
  `style.css`.
- Navigation and footer menu `<li>` bullets removed on every page. The mobile
  menu rows are full width and left-aligned.
- Inner-page heroes sit below the fixed header using `--header-offset`. On
  768–1200px screens the header used to cover the top ~90px of the banner.
- Page stylesheets (about, prices, how-it-works, our-vets, faq) are enqueued
  from `functions.php` with versioning, instead of hard-coded `<link>` tags in
  `header.php`.
- How It Works: fixed two `<img>` tags with broken quotes.
- `coming-soon.php`: new wordmark, Archivo and brand colours.

### Build notes
- The front page reads only the minified files: `brand.min.css`,
  `header.min.css`, `footer.min.css` and `home.min.css`. Re-minify after editing
  the matching `.css` source, for example with
  `npx lightningcss-cli --minify home.css -o home.min.css`.

## 1.0.0
- Initial theme.
