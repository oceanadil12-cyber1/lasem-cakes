# La Sem Cakes — website (`website/`)

The new La Sem Cakes website and pickup ordering system. **This folder is what gets uploaded to the host.**
Plain PHP, CSS and JavaScript — no framework, no database, no build step. PHP 7.4+ with Apache `mod_rewrite`.

Version 1.0 · Full project documentation: [../README.md](../README.md)

## URLs

| System | Local | Live (after launch) | Status |
| --- | --- | --- | --- |
| **Website** | <http://localhost:8081/> | <https://lasemcakes.ca/> | Built (v1.0) |
| **Online ordering (pickup)** | <http://localhost:8081/order/> | <https://lasemcakes.ca/order/> | Built (v1.0) |
| **Admin panel** | <http://localhost:8081/admin/> | <https://lasemcakes.ca/admin/> | Planned — not built yet |
| **KDS (kitchen display)** | <http://localhost:8081/kds/> | <https://lasemcakes.ca/kds/> | Planned — not built yet |
| **Table ordering (dine-in)** | <http://localhost:8081/dine-in/?table=1> | <https://lasemcakes.ca/dine-in/?table=1> | Planned — not built yet |
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
| `api/order.php` | POST — places a pickup order (re-prices from `menu.json`, checks the slot, saves, emails) |
| `api/contact.php` | POST — cake enquiry form (saves, then emails info@lasemcakes.ca) |
| `data/pages.json` | Text and photos for the 40 occasion, bakery, gallery, award and policy pages |
| `data/menu.json` | Ordering menu (97 products, 11 categories) and ordering settings (hours, lead time, days ahead, HST) |
| `data/testimonials.json` | Customer testimonials |
| `css/style.css` | All styles |
| `js/main.js` | Menus, lightbox, click-to-play YouTube, "open now" badge |
| `js/order.js` | Ordering page: menu, cart, pickup slots, checkout |
| `js/scene3d.js` | 3D engine (lights, camera, timeline, player controls, bakery parts kit) |
| `js/scenes/*.js` | One animation per page, named after the page URL (`signature.js` = home) |
| `js/vendor/` | three.js r128 |
| `images/uploads/` | Page photos as WebP (+ 800 px copies), sizes in `manifest.json` |
| `images/menu/` | Ordering menu photos |
| `images/story/`, `images/logo.png` | Home page story photos, logo |
| `videos/` | Birthday cake, cupcake and keto dessert clips |
| `private/` | Created on the server: `orders/` (`LS-YYMMDD-NNN.json`) and `messages/`. **Never in git, never uploaded from a PC.** |

## Ordering settings (`data/menu.json`)

| Setting | Value |
| --- | --- |
| Ordering hours | 09:00 – 17:10 |
| Ready in | 30 minutes (`leadMinutes`) |
| Order ahead | up to 14 days (`daysAhead`) |
| HST | 13%, only on categories with `"taxable": true` (sandwiches, beverages) |
| Payment | Pay at pickup |

## Deploy

Upload the contents of this folder to `public_html` (or a sub-folder), `.htaccess` included, except
`private/` and this `README.md`. Make sure PHP can write to `private/` and that the host can send email.
