# La Sem Cakes — website (`website/`)

The new La Sem Cakes website and pickup / delivery ordering system. **This folder is what gets uploaded to the host.**
Plain PHP, CSS and JavaScript — no framework, no database, no build step. PHP 7.4+ with Apache `mod_rewrite`.

Version 1.0 · Full project documentation: [../README.md](../README.md)

## URLs

| System | Local | Live (after launch) | Status |
| --- | --- | --- | --- |
| **Website** | <http://localhost:8081/> | <https://lasemcakes.ca/> | Built (v1.0) |
| **Online ordering (pickup or delivery)** | <http://localhost:8081/order/> | <https://lasemcakes.ca/order/> | Built (v1.0) |
| **Admin panel** | <http://localhost:8081/admin/> | <https://lasemcakes.ca/admin/> | Built (not yet in a release) — see [Admin panel](../README.md#admin-panel-admin) |
| **KDS (kitchen display)** | <http://localhost:8081/kds/> | <https://lasemcakes.ca/kds/> | Built (not yet in a release) — see [Kitchen display](../README.md#kitchen-display-kds) |
| **Table ordering (dine-in)** | `/dine-in/?t=TABLECODE` (links in Admin → Tables & QR codes) | | Built (not yet in a release) — see [Table ordering](../README.md#table-ordering-dine-in) |
| **"Now serving" board** | <http://localhost:8081/dine-in/board/> | <https://lasemcakes.ca/dine-in/board/> | Built (not yet in a release) |
| Contact / cake enquiry | <http://localhost:8081/contact-us/> | <https://lasemcakes.ca/contact-us/> | Built (v1.0) |
| Sitemap | <http://localhost:8081/sitemap.xml> | <https://lasemcakes.ca/sitemap.xml> | Built (v1.0) |
| GitHub repository | <https://github.com/oceanadil12-cyber1/lasem-cakes> | | |

The full list of page URLs and old-URL redirects is in the [main README](../README.md#website-page-urls).

## Run locally

From the project root (one level up):

```bash
php -S localhost:8081 -t website website/router.php
```

or double-click `Start Website.bat` there. Then open <http://localhost:8081>.

## How a request is handled

1. `.htaccess` (on the host) or `router.php` (locally) serves real files directly, blocks `inc/`, `data/`
   and `private/`, and sends every other URL to `index.php`.
2. `index.php` redirects old lasemcakes.ca URLs (301), builds `sitemap.xml`, or picks the template in
   `inc/pages/` for the URL. Unknown URLs get `inc/pages/404.php`.
3. Templates use `inc/layout.php` (header, mega menu, footer) and `inc/site.php` (business details, helpers).

## Files

| Path | What it is |
| --- | --- |
| `index.php` | Front controller: URL → template, redirects, sitemap |
| `router.php` | Local preview only (PHP built-in server) |
| `.htaccess` | HTTPS + trailing-slash redirects, blocks code/data folders, caching, compression |
| `robots.txt` | Blocks `/api/`, points to the sitemap |
| `inc/site.php` | Business name, phone, address, hours, links; `e()`, `url()`, `asset()`, `img()` helpers; `ASSET_VERSION` (bump after CSS/JS changes) |
| `inc/layout.php` | Header with the awning and mega menu, footer, banners, shared sections |
| `inc/scene-hero.php` | 3D hero on cake/bakery pages and each page's colour theme (`scene_themes()`) |
| `inc/pages/*.php` | Page templates: home, product, gallery, award, policy, section, about, contact, testimonials, articles, fillings, order, 404 |
| `api/order.php` | POST — places a pickup or delivery order (re-prices from `menu.json`, adds the delivery fee, checks the slot and address, saves, emails) |
| `api/contact.php` | POST — cake enquiry form (saves, then emails the bakery) |
| `admin/` | Admin panel: `index.php` (router), `inc/auth.php` (sign-in, security headers), `inc/store.php` (JSON files, backups, activity log), `inc/layout.php`, `inc/ui.php`, `inc/pages/*.php` (one per section; `deliveries.php` is the day’s delivery run with riders, route and the three-copy invoice), `assets/` |
| `kds/` | Kitchen display: `index.php` (sign-in + screen), `api.php` (JSON), `inc/kds.php` (session, passcode), `assets/` |
| `dine-in/` | Table ordering: `index.php` (table page, `/track/`, `/board/`, `/qr/`), `assets/` |
| `api/dine-in.php` | POST — table orders, tracking, staff calls; GET `?action=board` |
| `inc/dinein.php` | Table ordering: tables, settings, store-hours check, pricing, staff calls |
| `inc/qr.php` | QR codes as SVG (no outside library) |
| `inc/ordering.php` | Ordering on/off, closed days, hidden items: shared by the order page, `api/order.php` and the admin |
| `data/settings.json` | Business details saved in the admin (only exists after the first save) |
| `data/pages.json` | Text and photos for the 40 occasion, bakery, gallery, award and policy pages |
| `data/menu.json` | Ordering menu (97 products, 11 categories) and ordering settings (hours, lead time, days ahead, HST) |
| `data/testimonials.json` | Customer testimonials |
| `css/style.css` | All styles |
| `js/main.js` | Menus, lightbox, click-to-play YouTube, "open now" badge |
| `js/order.js` | Ordering page: menu, cart, pickup or delivery, time slots, address, checkout |
| `js/scene3d.js` | 3D engine (lights, camera, timeline, player controls, bakery parts kit) |
| `js/scenes/*.js` | One animation per page, named after the page URL (`signature.js` = home) |
| `js/vendor/` | three.js r128 |
| `images/uploads/` | Page photos as WebP (+ 800 px copies), sizes in `manifest.json` |
| `images/menu/` | Ordering menu photos |
| `images/story/`, `images/logo.png` | Home page story photos, logo |
| `videos/` | Birthday cake, cupcake and keto dessert clips |
| `fonts/` | Inter, for the admin panel |
| `private/` | Created on the server: `orders/` (`LS-YYMMDD-NNN.json`), `messages/`, `admin.json` (sign-in hash), `kds.json` (kitchen passcode hash, settings), `tables.json` (tables + QR codes), `calls/`, `sessions/`, `backups/`, `activity.jsonl`. **Never in git, never uploaded from a PC.** |

## Ordering settings (`data/menu.json`)

| Setting | Value |
| --- | --- |
| Ordering hours | 09:00 – 17:10 |
| Ready in | 30 minutes (`leadMinutes`) |
| Order ahead | up to 14 days (`daysAhead`) |
| HST | 13%, only on categories with `"taxable": true` (sandwiches, beverages) |
| Payment | Pay at pickup, or on delivery (cash or card at the door) |

All of these, plus ordering on/off and closed days, are edited in the admin (Store settings).

## Deploy

Upload the contents of this folder to `public_html` (or a sub-folder), `.htaccess` included, except
`private/` and this `README.md`. Make sure PHP can write to `private/`, `data/` and `images/` (the admin saves
there) and that the host can send email. After launch, don't overwrite the server's `data/` and `images/` with
local copies: the bakery edits them in the admin.
