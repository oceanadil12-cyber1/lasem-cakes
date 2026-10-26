# La Sem Cakes — Website & Ordering System

The new website and online ordering system for **La Sem Cakes / La Sem Patisserie**, an award-winning
Italian bakery at 1275 Eglinton Ave. East, Mississauga, ON L4W 2Z2 (phone (905) 624-8888,
info@lasemcakes.ca, open every day 8:30 am – 6:00 pm).

It is a hand-built site: plain PHP, CSS and JavaScript, with no framework, no database and no build step.
It runs on any cPanel host with PHP 7.4+.

**Version:** 1.0 (tag `v1.0`, October 2026)

---

## Table of contents

1. [URLs](#urls)
2. [What is in version 1.0](#what-is-in-version-10)
3. [How to run it on your computer](#how-to-run-it-on-your-computer)
4. [Website pages](#website-pages)
5. [3D cake animations](#3d-cake-animations)
6. [Online ordering (`/order/`)](#online-ordering-order)
7. [Cake enquiry form](#cake-enquiry-form)
8. [Admin panel, KDS and table ordering (planned)](#admin-panel-kds-and-table-ordering-planned)
9. [Project structure](#project-structure)
10. [Deploying to the live host](#deploying-to-the-live-host)
11. [Security and privacy](#security-and-privacy)
12. [The earlier WordPress rebuild (`site/`)](#the-earlier-wordpress-rebuild-site)
13. [Before launch](#before-launch)
14. [Version history](#version-history)

---

## URLs

### Systems

| System | Local (on this computer) | Live (after launch) | Status |
| --- | --- | --- | --- |
| **Website** | <http://localhost:8081/> | <https://lasemcakes.ca/> | Built (v1.0) |
| **Online ordering (pickup)** | <http://localhost:8081/order/> | <https://lasemcakes.ca/order/> | Built (v1.0) |
| **Admin panel** | <http://localhost:8081/admin/> | <https://lasemcakes.ca/admin/> | Planned — next phase, not built yet |
| **KDS (kitchen display)** | <http://localhost:8081/kds/> | <https://lasemcakes.ca/kds/> | Planned — not built yet |
| **Table ordering (dine-in, QR code per table)** | <http://localhost:8081/dine-in/?table=1> | <https://lasemcakes.ca/dine-in/?table=1> | Planned — not built yet |
| Old WordPress rebuild (superseded) | <http://localhost:8080/> | — | Kept for reference |
| Old WordPress admin (local only) | <http://localhost:8080/wp-admin/> | — | Kept for reference |

The admin panel, KDS and table-ordering addresses are the ones reserved for them (the same layout as the
Alibi Monkey Bar project). Until they are built, those addresses show the site's 404 page.

`lasemcakes.ca` still serves the **old** WordPress site. The live column applies once this site is uploaded
there (see [Deploying](#deploying-to-the-live-host)).

### Other useful addresses

| What | URL |
| --- | --- |
| GitHub repository | <https://github.com/oceanadil12-cyber1/lasem-cakes> |
| Version 1.0 release | <https://github.com/oceanadil12-cyber1/lasem-cakes/releases/tag/v1.0> |
| Sitemap | <http://localhost:8081/sitemap.xml> (live: <https://lasemcakes.ca/sitemap.xml>) |
| Staging site the content came from | <https://aiseoprohub.com/lasemcakes/> |
| Old DoorDash storefront (replaced by `/order/`) | <https://order.online/business/la-sem-patisserie-88690> |
| Google Maps | <https://maps.app.goo.gl/ZXmpbuNYha5AK2APA> |
| Facebook | <https://www.facebook.com/lasembakery> |

### Website page URLs

All page addresses (local: put `http://localhost:8081` in front; live: `https://lasemcakes.ca`):

| Section | URLs |
| --- | --- |
| Main | `/` · `/about-us/` · `/bakery/` · `/cake-type-fillings/` · `/gallery/` · `/awards/` · `/testimonials/` · `/articles-videos/` · `/contact-us/` · `/order/` |
| Cake occasions | `/wedding-cakes/` · `/engagement-cakes/` · `/anniversary-cakes/` · `/bridal-shower-cakes/` · `/baby-shower-cakes/` · `/baptism-cakes/` · `/communion-cakes/` · `/confirmation-cakes/` · `/graduation-cakes/` · `/bachelor-party-cakes/` · `/novelty-cakes/` |
| Bakery items | `/cheese-cake-mississauga/` · `/cookies-mississauga/` · `/croissants-mississauga/` · `/cupcakes-mississauga/` · `/focaccia-sandwiches/` · `/gelato/` · `/keto-cookies/` · `/pastries-mississauga/` · `/zeppole-mississauga/` |
| Galleries | `/wedding-shower/` · `/engagement-anniversary/` · `/baby-shower/` · `/bachelor-bachelorette/` · `/birthday-novelty/` · `/religious-graduation/` · `/christmas/` · `/specialties/` · `/retail-cakes/` · `/retail-pastry-other/` · `/cookies-other/` · `/foccacia-pizza-lasagna-other-buns/` |
| Awards | `/awards/top-choice-award/` · `/awards/readers-choice-award/` · `/awards/peoples-choice/` |
| Policies | `/cancellation-policy/` · `/privacy-policy/` · `/returns-exchanges/` · `/shipping/` · `/terms-conditions/` |
| API (used by the forms) | `/api/order.php` (POST) · `/api/contact.php` (POST) |

Old lasemcakes.ca addresses redirect (301) to their new pages, so search rankings carry over:

| Old URL | Goes to |
| --- | --- |
| `/home/` | `/` |
| `/bakery/cake-category/` | `/bakery/` |
| `/bakery/cake-type-fillings/` | `/cake-type-fillings/` |
| `/bakery/pastries/`, `/bakery/cookies/`, `/bakery/gelato/`, `/bakery/zeppole/` | the matching bakery page |
| `/bakery/bread/` | `/foccacia-pizza-lasagna-other-buns/` |
| `/gallery/<name>/` | `/<name>/` |
| `/blog/` and `/YYYY/MM/DD/<post>/` | `/articles-videos/` |

---

## What is in version 1.0

- **Full website** rebuilt from the staging site's content: 40 content pages plus home, about, contact,
  testimonials, articles & videos, cake types & fillings, and the Bakery / Gallery / Awards landing pages.
- **3D animated hero** on the home page and on all 20 cake and bakery pages, each one different
  (three.js, built live in the browser — not video files).
- **Online pickup ordering** at `/order/`, replacing the DoorDash storefront: 97 products in 11 categories,
  cart, pickup time slots, server-side pricing, email confirmations. Pay at pickup.
- **Cake enquiry form** on the contact page, saved on the server and emailed to the bakery.
- SEO: same URLs as staging, 301 redirects from the old site, generated `sitemap.xml`, `robots.txt`,
  HTTPS and trailing-slash redirects.
- Photos converted to WebP with 800 px copies; lightbox galleries; click-to-play YouTube videos;
  "open now" badge from the bakery hours.
- Works at the domain root or in a sub-folder.

Not in 1.0 (next phases): admin panel, KDS, table ordering, online card payment.

---

## How to run it on your computer

You need PHP 7.4 or newer (on Windows: `winget install PHP.PHP.8.4`, or XAMPP).

**Easiest (Windows):** double-click **`Start Website.bat`**. It finds PHP (PATH, WinGet or XAMPP), starts
the site and opens <http://localhost:8081>. Keep its window open while you browse; close it to stop the site.

**Any system:**

```bash
php -S localhost:8081 -t website website/router.php
```

Then open <http://localhost:8081>. (`website/router.php` makes PHP's built-in server behave like the
`.htaccess` rules on the real host.) In Claude Code the same command is the `lasem-website` entry in
`.claude/launch.json`.

Orders and enquiries you place locally are saved in `website/private/` (created on first use). Emails are only
sent if PHP's `mail()` is set up, which it usually isn't on a PC — the order is saved either way.

---

## Website pages

| Page | Template |
| --- | --- |
| Home (3D cake hero, cake occasions, bakery, favourites, build-your-cake, story, awards) | `website/inc/pages/home.php` |
| 11 cake occasions + 9 bakery items | `website/inc/pages/product.php` |
| 12 photo galleries (lightbox) | `website/inc/pages/gallery.php` |
| 3 awards | `website/inc/pages/award.php` |
| 5 policies | `website/inc/pages/policy.php` |
| `/bakery/`, `/gallery/`, `/awards/` landing pages | `website/inc/pages/section.php` |
| About us | `website/inc/pages/about.php` |
| Contact us (enquiry form, map) | `website/inc/pages/contact.php` |
| Testimonials | `website/inc/pages/testimonials.php` |
| Articles & videos | `website/inc/pages/articles.php` |
| Cake types & fillings | `website/inc/pages/fillings.php` |
| Order online | `website/inc/pages/order.php` |
| Not found | `website/inc/pages/404.php` |

The text and photos for the 40 occasion, bakery, gallery, award and policy pages are in
`website/data/pages.json` (imported from the staging site's WordPress REST API). Business details
(phone, hours, address, links) are at the top of `website/inc/site.php`.

---

## 3D cake animations

Every page under Bakery opens with its own animation, built live in 3D like the home page, with its own
colour mood. Examples: wedding tiers stacking with a rose cascade, a ring landing on the engagement cake,
an open-book communion cake, a dove landing on the confirmation cake, a champagne cork popping on the
bachelor cake, zeppole being piped and filled, gelato scoops stacking on a cone, and a focaccia sandwich
built layer by layer.

- Engine and parts kit: `website/js/scene3d.js` (three.js r128 in `website/js/vendor/`).
- One file per page: `website/js/scenes/<page-slug>.js` (`signature.js` is the home page).
- Colour themes per page: `scene_themes()` in `website/inc/scene-hero.php`.
- The wedding, engagement, anniversary, bridal shower, baby shower, confirmation, graduation and novelty
  cakes are modelled on the reference photos in `Bakery Section On Website/`.
- Add one for a new page: create `js/scenes/<page-slug>.js` calling `LaSem3D.start(function (k) { … })`
  and add a theme line in `scene_themes()`. Pages without a scene file keep the plain banner.
- In the browser console, `LaSemCake.seek(5)` jumps to 5 s of the current page's animation.
- Visitors who prefer reduced motion see the finished scene; without WebGL the page's first photo shows.

---

## Online ordering (`/order/`)

Pickup ordering on La Sem's own site, replacing the DoorDash storefront. Every "Order Online" button links here.

| Part | Where | What it does |
| --- | --- | --- |
| Menu | `website/data/menu.json` | 97 products in 11 categories, prices in cents, descriptions, photos (`images/menu/`). Imported from the DoorDash store. |
| Order page | `website/inc/pages/order.php` + `website/js/order.js` | Search, category bar, item pop-up (size, flavour, writing on the cake, quantity), cart kept in the browser, pickup day and 15-minute slot, checkout |
| Place order | `website/api/order.php` | Re-prices every line from `menu.json` (browser prices are ignored), checks the pickup slot, saves `private/orders/LS-YYMMDD-NNN.json`, emails the bakery and the customer. A repeated submit returns the same order. |

Menu categories: Focaccia Sandwiches (5), Cakes (24), Pastries (37), Large Pastries (10), Zeppole (2),
Macarons (1), Gourmet Cookies (3), Keto Cookies (3), Almond & Shortbread Cookies (3), Croissants (4),
Beverages (5).

Settings at the top of `menu.json`:

| Setting | Value | Meaning |
| --- | --- | --- |
| `hours.opens` / `hours.closes` | 09:00 / 17:10 | Ordering hours (pickup slots) |
| `leadMinutes` | 30 | Earliest pickup is 30 minutes from now |
| `daysAhead` | 14 | Orders up to 14 days ahead |
| `taxRate` | 0.13 | Ontario HST |
| `taxable` (per category) | sandwiches, beverages | HST applies only to these; other bakery items are treated as zero-rated basic groceries — **the bakery should confirm with their accountant** |

**Payment:** pay at pickup (cash, debit or card at the counter). There is no online payment in 1.0.

---

## Cake enquiry form

The contact page form posts to `website/api/contact.php`, which saves each enquiry to
`website/private/messages/` and then emails info@lasemcakes.ca. Enquiries are saved even if the email fails.

---

## Admin panel, KDS and table ordering (planned)

These are **not built yet**. They follow the Alibi Monkey Bar system and will use these addresses:

| System | URL | What it will do |
| --- | --- | --- |
| Admin panel | `/admin/` | Password-protected. Orders, cake enquiries (`private/messages/`), menu and prices (`data/menu.json`), ordering hours and lead time, page text and photos (`data/*.json`), business details. |
| KDS (kitchen display) | `/kds/` | Live screen for the kitchen/counter: new orders appear with a sound, staff mark them in progress → ready → picked up. |
| Table ordering | `/dine-in/?table=N` | Guests scan the QR code on their table, order from the menu and send it straight to the KDS; can call a server. |

The website was built so these can be added without changing it: business details are in one place
(`inc/site.php`), all content and menu data are JSON files in `data/`, and orders are JSON files in
`private/orders/`.

---

## Project structure

```text
.
├── README.md                     This file
├── Start Website.bat             Windows launcher (http://localhost:8081)
├── .gitignore
├── .claude/launch.json           Dev-server entries for Claude Code
├── Bakery Section On Website/    Reference cake photos the 3D animations are modelled on
├── website/                      THE NEW WEBSITE (this is what gets deployed) — see website/README.md
│   ├── index.php                 Front controller: URL → template, old-URL redirects, sitemap
│   ├── router.php                Local preview only (PHP built-in server)
│   ├── .htaccess                 Apache rules: HTTPS, trailing slash, block inc/data/private
│   ├── robots.txt
│   ├── api/
│   │   ├── order.php             Places a pickup order
│   │   └── contact.php           Cake enquiry form
│   ├── inc/
│   │   ├── site.php              Business details + helpers
│   │   ├── layout.php            Header (awning, mega menu), footer, shared sections
│   │   ├── scene-hero.php        3D hero + per-page colour themes
│   │   └── pages/*.php           Page templates
│   ├── data/
│   │   ├── pages.json            Content of 40 pages
│   │   ├── menu.json             Ordering menu + ordering settings
│   │   └── testimonials.json
│   ├── css/style.css
│   ├── js/
│   │   ├── main.js               Menus, lightbox, YouTube, "open now" badge
│   │   ├── order.js              Ordering page
│   │   ├── scene3d.js            3D engine
│   │   ├── scenes/*.js           One animation per page
│   │   └── vendor/               three.js r128
│   ├── images/                   logo, uploads/ (WebP + manifest.json), menu/, story/
│   ├── videos/                   Short product videos (mp4)
│   └── private/                  Orders and enquiries — created on the server, NOT in git
├── site/                         Earlier WordPress rebuild (only the theme is in git)
│   └── wp-content/themes/lasemcakes/   — see its README.md
└── tools/                        WordPress build tools (seed script, router, WP-CLI)
```

---

## Deploying to the live host

1. Upload the **contents** of `website/` to `public_html` (or a sub-folder — both work), including `.htaccess`.
2. Do **not** upload a local `private/` folder or `website/README.md`; the server creates its own. Make sure PHP can write to it.
3. Set up a mailbox or SMTP on the host so order and enquiry emails reach info@lasemcakes.ca.
4. Check <https://lasemcakes.ca/>, place a test order at <https://lasemcakes.ca/order/>, and submit
   <https://lasemcakes.ca/contact-us/>.
5. Submit <https://lasemcakes.ca/sitemap.xml> in Google Search Console.

Requirements: PHP 7.4+, Apache with `mod_rewrite` (cPanel default). No database.

---

## Security and privacy

- `website/data/`, `website/inc/` and `website/private/` are blocked from the web (`.htaccess`, and
  `router.php` locally).
- Order prices are always recalculated on the server from `menu.json`.
- Customer orders and enquiries (`website/private/`) are **never committed** (`.gitignore`).
- No passwords, API keys or payment keys are in this repository. WordPress core, `wp-config.php`,
  uploads and logs are excluded.

---

## The earlier WordPress rebuild (`site/`)

Before the hand-built site, the bakery was rebuilt on WordPress 7.1.3 (same as the live site) with a custom
theme. It is **superseded by `website/`** and kept for reference. Only the theme
(`site/wp-content/themes/lasemcakes/`) and the tools are in git; WordPress core is not.

| Path | What it is |
| --- | --- |
| `site/wp-content/themes/lasemcakes/` | The custom theme — see its [README](site/wp-content/themes/lasemcakes/README.md) |
| `tools/seed-site.php` | Builds the local site: pages, menus, posts, home slider, announcement pop-up |
| `tools/router.php` | Lets PHP's built-in server handle WordPress URLs |
| `tools/wp-cli.phar` | WP-CLI 2.12 |

To run it: install WordPress 7.1.3 into `site/`, start MySQL (XAMPP), then

```bash
php -S localhost:8080 -t site tools/router.php
```

Site: <http://localhost:8080/> · Admin: <http://localhost:8080/wp-admin/> (local test login only).
Rebuild pages and menus:

```bash
php tools/wp-cli.phar --path=site --user=admin eval-file tools/seed-site.php
```

---

## Before launch

- Most gallery photos are old 600 px shots with date stamps; a photo shoot would lift the whole site.
- Set up email (mailbox or SMTP) on the host.
- Confirm with the bakery's accountant which items carry HST.
- About page: staging said "1000+ team members"; it's left out until the bakery confirms the real number.
- 12 Bakery pages (baptism, communion, bachelor and the 9 bakery items) still use the first, non-photo
  animations; the other 8 cake pages are modelled on reference photos.

---

## Version history

| Version | Date | What changed |
| --- | --- | --- |
| **1.0** | 2026-10-26 | First release: full website from the staging content, 3D animated heroes on the home and all 20 cake/bakery pages (8 modelled on reference photos), online pickup ordering at `/order/`, cake enquiry form, old-URL redirects, sitemap. |

Next: admin panel (`/admin/`), then KDS (`/kds/`) and table ordering (`/dine-in/`).
