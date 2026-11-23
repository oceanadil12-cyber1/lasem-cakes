# La Sem Cakes — Website & Ordering System

The new website and online ordering system for **La Sem Cakes / La Sem Patisserie**, an award-winning
Italian bakery at 1275 Eglinton Ave. East, Mississauga, ON L4W 2Z2 (phone (905) 624-8888,
info@lasemcakes.ca, open every day 8:30 am – 6:00 pm).

It is a hand-built site: plain PHP, CSS and JavaScript, with no framework, no database and no build step.
It runs on any cPanel host with PHP 7.4+.

**Version:** 1.0 (tag `v1.0`, October 2026)

> **Public preview links and sign-ins** are kept in `ACCESS.md` next to this file, on the developer's PC
> only. That file is git-ignored on purpose, because this repository is public: never put links with
> sign-ins, or any password, in this README. The current version and v1.0 each have their own
> temporary Cloudflare link (v1.0 runs from a separate checkout, `../Lasem Cakes - v1.0`).

---

## Table of contents

1. [URLs](#urls)
2. [What is in version 1.0](#what-is-in-version-10)
3. [How to run it on your computer](#how-to-run-it-on-your-computer)
4. [Website pages](#website-pages)
5. [3D cake animations](#3d-cake-animations)
6. [Online ordering (`/order/`)](#online-ordering-order)
7. [Cake enquiry form](#cake-enquiry-form)
8. [Admin panel (`/admin/`)](#admin-panel-admin)
9. [Kitchen display (`/kds/`)](#kitchen-display-kds)
10. [Table ordering (`/dine-in/`)](#table-ordering-dine-in)
11. [Project structure](#project-structure)
12. [Deploying to the live host](#deploying-to-the-live-host)
13. [Security and privacy](#security-and-privacy)
14. [The earlier WordPress rebuild (`site/`)](#the-earlier-wordpress-rebuild-site)
15. [Before launch](#before-launch)
16. [Version history](#version-history)

---

## URLs

### Systems

| System | Local (on this computer) | Live (after launch) | Status |
| --- | --- | --- | --- |
| **Website** | <http://localhost:8081/> | <https://lasemcakes.ca/> | Built (v1.0) |
| **Online ordering (pickup)** | <http://localhost:8081/order/> | <https://lasemcakes.ca/order/> | Built (v1.0) |
| **Admin panel** | <http://localhost:8081/admin/> | <https://lasemcakes.ca/admin/> | Built (not yet in a release) |
| **KDS (kitchen display)** | <http://localhost:8081/kds/> | <https://lasemcakes.ca/kds/> | Built (not yet in a release) |
| **Table ordering (dine-in, QR code per table)** | <http://localhost:8081/dine-in/?t=TABLECODE> | <https://lasemcakes.ca/dine-in/?t=TABLECODE> | Built (not yet in a release); each table's link is in Admin → Tables & QR codes |
| **"Now serving" board** | <http://localhost:8081/dine-in/board/> | <https://lasemcakes.ca/dine-in/board/> | Built (not yet in a release) |
| Old WordPress rebuild (superseded) | <http://localhost:8080/> | — | Kept for reference |
| Old WordPress admin (local only) | <http://localhost:8080/wp-admin/> | — | Kept for reference |

Table links contain each table's secret code, so they are listed in the admin rather than here.

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
`website/private/messages/` and then emails the bakery (info@lasemcakes.ca, or the address set in the admin's
Store settings). Enquiries are saved even if the email fails, and are listed in the admin under **Cake enquiries**.

---

## Admin panel (`/admin/`)

The team's back office, built like the Alibi Monkey Bar admin (same layout, dark theme in La Sem's cocoa and
orange). No database: it reads and writes the same JSON files the website uses, so a change is live as soon
as it is saved.

| Section | What the team can do |
| --- | --- |
| **Dashboard** | Today's pickups with one-tap status buttons, orders to confirm, new enquiries, sales for the last 7 days, a 14-day sales chart, what's coming up, recent changes |
| **Pickup orders** | Tabs for today, upcoming, to confirm, ready, picked up, cancelled, all; search; spreadsheet download. Each order: items, customer's note, phone/email links, status **New → Confirmed → Preparing → Ready → Picked up** (or Cancelled) with a timeline, **mark paid**, a private team note, other orders from the same customer, print |
| **Prep list** | Everything to make for a day's pickups added up across orders (with writing-on-the-cake notes), then each order by pickup time. Printable |
| **Cake enquiries** | Inbox from the contact form: New, Read, Replied, Booked, Archived; reply by email (pre-filled) or call; team note; delete; spreadsheet download |
| **Menu & prices** | Every item on the order page: switch on/off, change prices, sold out (for today), "Most ordered". Per item: name, description, category, sizes and prices, a required choice (e.g. flavour), photo. Add and delete items. Categories: rename, reorder, HST on/off, hide |
| **Page content** | Title, menu name, headline, page text (paragraphs and bullet points), Google description, photo captions, photo order, remove and upload photos, for all 40 cake, bakery, gallery, award and policy pages. Testimonials: edit, reorder, add, remove |
| **Store settings** | Online ordering **on/off** with its message, first/last pickup time, notice needed, days ahead, **closed days**, HST rate; business details (name, phone, email, address, hours, Maps and Facebook links); where order/enquiry emails go; an **announcement bar** across the top of the site; the kitchen display passcode; **Google reviews** (API key, Place ID); change password |
| **Activity log** | Sign-ins and every change, with time and IP |

Live badges in the sidebar and the bell in the top bar refresh every 30 seconds (new orders, new enquiries,
orders not confirmed after 10 minutes, pickups due within the hour that aren't ready, ready orders not
collected).

**First sign-in.** The first visit to `/admin/` shows a setup page that asks for a one-time code. The panel
writes it to `website/private/setup-code.txt` on the server (read it over FTP or the host's file manager);
then you choose the user name and password (10+ characters). The code file is deleted afterwards. To reset a
forgotten password, delete `website/private/admin.json` on the server and set it up again.

| File | What the admin changes in it |
| --- | --- |
| `private/orders/*.json` | Status, history, paid, team note (the items and prices are never changed) |
| `private/messages/*.json` | Status, history, team note |
| `data/menu.json` | Menu, prices, `hidden`, `soldOut`, `popular`, `choice`; `ordering`, `closedMessage`, `closedDates`, hours, lead time, days ahead, HST |
| `data/pages.json`, `data/testimonials.json` | Page text and photos, testimonials |
| `data/settings.json` | Business details, notification email, announcement (created on first save; overrides the defaults in `inc/site.php`) |
| `images/menu/`, `images/uploads/admin/` | Uploaded photos (converted to WebP when the host's PHP has GD, otherwise kept as uploaded) |

Every save keeps the previous copy of the data file in `private/backups/` (the newest 30 of each), and the
files are written in the same layout as in git, so a change shows as a small diff.

### Google reviews

The home and testimonials pages show the bakery's **5-star Google reviews** in a wall that scrolls upward
(two columns on computers, one on phones, pauses on hover), next to the overall rating and buttons to read
all reviews or write one on the [Google Business Profile](https://maps.app.goo.gl/VnNGhAXHpFVgKws17).

They come from Google's official **Places API (New)**, the way Google allows reviews to be shown on a website
(reviewer name and photo, Google credited); reviews are never copied by hand. Setup, once:

1. In [Google Cloud](https://console.cloud.google.com/), create a project, enable **Places API (New)** and
   billing (the free monthly allowance easily covers two requests a day), then create an **API key**
   restricted to that API.
2. Admin → Store settings → **Google reviews**: paste the key and press **Save and refresh now**. The Place
   ID is found automatically from the business name and address.

The site asks Google again at most every 12 hours. Google returns up to 5 reviews per request, so each
refresh adds any new 5-star review to the saved ones (newest 30 kept). Until a key is added, the section
simply doesn't show. Files: `inc/reviews.php`, `private/google.json` (key, never committed),
`private/google-reviews.json` (saved reviews).

## Kitchen display (`/kds/`)

A live screen for the bakery and the counter, like the Alibi KDS but made for a bakery's pickups, which are
often booked hours or days ahead. Open it on a tablet or a TV browser at `/kds/`.

- **Cards in pickup-time order**, each with the pickup time in large type, "in 25 min" / "12 min late", the
  customer, every item with its size or flavour, writing-on-the-cake notes, the customer's note, the team
  note, and what to collect at the counter (or "Paid").
- **Colour tells what to do:** grey = later today, amber **Start now** (pickup is within the "start orders"
  time, 30 minutes by default), orange = preparing (with a timer), red = late, green = ready.
- **One tap moves an order:** Start → Ready → Picked up (picking up also marks it paid). "Ready" can be
  tapped straight away for items that only need packing; Back/Undo fixes a mistake.
- **New orders chime** (three-note bell) and glow blue, with a pop-up message. Tap the screen once after
  opening it so the browser allows sound.
- Tabs: **Today** (including late orders), **Tomorrow**, **Ready for pickup**, **Picked up today**. Counters
  at the top: to start, preparing, ready, late.
- Refreshes every 5 seconds, shows "Reconnecting…" if the connection drops, keeps the screen awake, and has a
  full-screen button. Times are always Mississauga time.

**Sign-in:** the kitchen passcode set in the admin (Store settings → Kitchen display) — it opens only the
kitchen display, not the admin — or the admin user name and password. Changing the passcode or the admin
password signs open screens out. Settings (start time, chime) can be changed on the screen or in the admin.

The KDS and the admin use the same order files, so a tap on the KDS shows in the admin and the other way
round. The order statuses are **New → Confirmed → Preparing → Ready → Picked up** (or Cancelled).

| File | What it is |
| --- | --- |
| `kds/index.php` | Sign-in and the screen |
| `kds/api.php` | JSON for the screen: orders, moves, settings (kitchen session + CSRF token) |
| `kds/inc/kds.php` | Shared: session, passcode check (blocked from the web) |
| `kds/assets/kds.js`, `kds.css` | The screen's script and styles |
| `private/kds.json` | Passcode hash, start-orders minutes, chime on/off |

## Table ordering (`/dine-in/`)

Guests order from their table, on their own phone or on a tablet at the table, and get a **priority
number** they can follow until the order is ready. Orders go straight to the kitchen display.

**How guests use it**

1. **Scan** the QR code on the table with the phone camera (no app), or tap the table's tablet.
2. Enter a first name (optional), browse the menu (search, categories, photos), choose size/flavour,
   add a special request, and review the order with HST.
3. **Place order** → a big **priority number** (1, 2, 3… starting again each day) and a live status:
   Received → Preparing → Ready → Enjoy. The page updates by itself and the phone buzzes and chimes
   when it's ready. A phone remembers the day's orders, so the guest can order more and still see them.
4. On a **table tablet** the confirmation also shows a QR code to follow the order on a phone, then the
   tablet resets for the next guest after 60 seconds (or "Done"); an abandoned order resets after 3 minutes.
5. **Call staff** button: shows as a red banner on the kitchen display until someone taps "On my way".

**For the team**

- **Kitchen display:** table orders appear as cards with the number (#12), the table and "ordered 3 min
  ago", with a chime; Start → Ready → Served (or Collected). They turn red after the "late" time
  (15 minutes by default). Tab: **Table orders**.
- **"Now serving" board** at `/dine-in/board/` for a TV at the counter: numbers being prepared and numbers
  ready (no names), with a chime when a number turns ready.
- **Admin → Tables & QR codes:** add tables (Table 1, Table 2… or named, e.g. Patio), switch a table off,
  rename, **replace a table's QR code**, remove; printable QR cards (two per page, with the logo); each
  table's phone and tablet links; table-ordering settings: on/off, "we bring it to the table" or "collect
  at the counter", HST on every table order (food eaten in is normally taxable in Ontario: confirm with
  the accountant), the payment note, the "late" time.
- **Admin → Orders:** table orders are listed with their table and number (tab "Table orders"), marked
  Served instead of Picked up, and included in sales and the spreadsheet. **Menu & prices → Categories:**
  untick "Table ordering" for categories that can't be ordered at a table.
- Payment stays **at the counter**; nothing is charged online.

**Security:** each table's QR link carries a random 8-character code (`/dine-in/?t=CODE`), so nobody can
order for a table by guessing; a replaced code stops working at once. Table ordering only works during
store hours, not on closed days, and only when switched on. Prices are recalculated on the server, sold-out
and hidden items are refused, and there are limits on orders and staff calls per device. Tracking links
carry a random key, so one guest can't see another's order. The board shows numbers only.

| File | What it is |
| --- | --- |
| `dine-in/index.php` | The table page, `/dine-in/track/`, `/dine-in/board/`, `/dine-in/qr/` (tracking QR codes) |
| `dine-in/assets/dine-in.js`, `dine-in.css` | The guest app and the board |
| `api/dine-in.php` | Place an order, track orders, call staff, board numbers |
| `inc/dinein.php` | Shared: tables, settings, opening hours, pricing, calls |
| `inc/qr.php` | QR code generator (SVG), written for this project, no outside library or service |
| `admin/inc/pages/tables.php` | Admin: Tables & QR codes |
| `private/tables.json` | The tables and their codes |
| `private/calls/` | Staff calls waiting for an answer |

**Before launch:** print the QR cards from the **live** site's admin (codes printed from a local copy point
to your computer). Scan one with a phone to check it opens the table's page.

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
│   ├── .htaccess                 Apache rules: HTTPS, trailing slash, block inc/data/private, admin/inc and kds/inc
│   ├── robots.txt
│   ├── admin/                    Admin panel: index.php, inc/ (auth, storage, layout, pages/), assets/
│   ├── kds/                      Kitchen display: index.php, api.php, inc/, assets/
│   ├── dine-in/                  Table ordering: index.php (table page, tracking, board, QR), assets/
│   ├── api/
│   │   ├── order.php             Places a pickup order
│   │   ├── dine-in.php           Table orders: place, track, call staff, board
│   │   └── contact.php           Cake enquiry form
│   ├── inc/
│   │   ├── site.php              Business details + helpers
│   │   ├── ordering.php          Ordering on/off, closed days, hidden items, order numbers (shared)
│   │   ├── dinein.php            Table ordering: tables, settings, pricing, staff calls
│   │   ├── qr.php                QR code generator (SVG)
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
│   ├── fonts/                    Inter (admin panel)
│   ├── videos/                   Short product videos (mp4)
│   └── private/                  Orders, enquiries, admin sign-in, backups — created on the server, NOT in git
├── site/                         Earlier WordPress rebuild (only the theme is in git)
│   └── wp-content/themes/lasemcakes/   — see its README.md
└── tools/                        WordPress build tools (seed script, router, WP-CLI)
```

---

## Deploying to the live host

1. Upload the **contents** of `website/` to `public_html` (or a sub-folder — both work), including `.htaccess`.
2. Do **not** upload a local `private/` folder or `website/README.md`; the server creates its own. Make sure PHP can write to it.
3. Set up a mailbox or SMTP on the host so order and enquiry emails reach info@lasemcakes.ca.
4. Open <https://lasemcakes.ca/admin/> and create the admin sign-in with the code in `private/setup-code.txt`.
5. Check <https://lasemcakes.ca/>, place a test order at <https://lasemcakes.ca/order/>, and submit
   <https://lasemcakes.ca/contact-us/>.
6. Submit <https://lasemcakes.ca/sitemap.xml> in Google Search Console.

**After launch the live server owns the content.** The bakery edits `data/*.json` and uploads photos through
the admin, so when you upload a new version of the site, do **not** overwrite `data/`, `images/menu/` or
`images/uploads/` (including `manifest.json`) with your local copies. Download them from the server first and
commit them, or upload only the code files that changed. PHP must be able to write to `private/`, `data/` and `images/`.

Requirements: PHP 7.4+, Apache with `mod_rewrite` (cPanel default). No database.

---

## Security and privacy

- `website/data/`, `website/inc/` and `website/private/` are blocked from the web (`.htaccess`, and
  `router.php` locally).
- Order prices are always recalculated on the server from `menu.json`; hidden or sold-out items, closed days
  and "ordering off" are enforced there too, not just on the page.
- Admin panel: user name + password (stored only as a hash), a one-time setup code for the first sign-in,
  8 wrong passwords = 15-minute lock, sessions kept in `private/sessions` and ended after 12 idle hours,
  changing the password signs out other devices, CSRF token on every form, strict security headers
  (no outside scripts, no framing, not indexed), activity log. `admin/inc/` is blocked from the web.
- Kitchen display: its own passcode (hash only) that opens nothing else, the same guess limit, its own
  session cookie (path `/kds/`), a CSRF token on every request, and `kds/inc/` blocked from the web.
  `robots.txt` asks search engines to stay out of `/admin/` and `/kds/`.
- Table ordering: secret code per table in its QR link, store hours only, server-side prices, per-device
  limits, random tracking keys; see [Table ordering](#table-ordering-dine-in).
- Customer orders and enquiries, the admin sign-in and backups (`website/private/`) are **never committed** (`.gitignore`).
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
| *next* | *not released* | Admin panel at `/admin/` (orders, prep list, enquiries, menu & prices, page content, store settings, activity log); ordering on/off, closed days, sold-out and hidden items on the order page; announcement bar; notification email setting. Kitchen display at `/kds/` (pickup-time cards, start/ready/picked-up, new-order chime, kitchen passcode); new "Preparing" order status. Table ordering at `/dine-in/` (QR code per table, phones and table tablets, priority numbers with live tracking, call staff, "Now serving" board, Tables & QR codes in the admin, QR codes generated in-house). |
