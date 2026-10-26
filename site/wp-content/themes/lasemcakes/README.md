# Lasem Cakes — WordPress theme (superseded)

Custom WordPress theme from the first rebuild of lasemcakes.ca (WordPress 7.1.3, PHP + jQuery).
**It has been replaced by the hand-built site in [`website/`](../../../../website/README.md)** and is kept
for reference only.

Full project documentation: [main README](../../../../README.md)

## URLs

| System | Local | Live | Status |
| --- | --- | --- | --- |
| **Website (new, `website/`)** | <http://localhost:8081/> | <https://lasemcakes.ca/> (after launch) | Built (v1.0) |
| **Online ordering** | <http://localhost:8081/order/> | <https://lasemcakes.ca/order/> | Built (v1.0) |
| **Admin panel** | <http://localhost:8081/admin/> | <https://lasemcakes.ca/admin/> | Planned — not built yet |
| **KDS (kitchen display)** | <http://localhost:8081/kds/> | <https://lasemcakes.ca/kds/> | Planned — not built yet |
| **Table ordering (dine-in)** | <http://localhost:8081/dine-in/?table=1> | <https://lasemcakes.ca/dine-in/?table=1> | Planned — not built yet |
| This WordPress theme | <http://localhost:8080/> | — | Superseded |
| WordPress admin (local) | <http://localhost:8080/wp-admin/> | — | Superseded |
| GitHub repository | <https://github.com/oceanadil12-cyber1/lasem-cakes> | | |

## Files

| File | Used for |
| --- | --- |
| `header.php` / `footer.php` | Awning header, logo, main menu; footer menu columns, contact, bottom bar |
| `front-page.php` | Home: page content edge to edge (the slider) |
| `page.php` | Default page (About Us, Contact Us, policies…) |
| `templates/template-section.php` | Bakery and Awards pages: section title + sub-page menu |
| `templates/template-gallery.php` | One gallery category: centred title over the photos |
| `index.php`, `single.php`, `sidebar.php`, `template-parts/` | Articles & Videos list, single post, sidebar |
| `404.php` | Not found page |
| `functions.php`, `inc/template-tags.php` | Theme setup, menus, helpers |
| `inc/customizer.php` | Appearance → Customize → **La Sem options**: announcement pop-up, Order Online link, social links |
| `assets/js/main.js` | Mobile menu, home slider, announcement pop-up (jQuery) |
| `style.css` | Theme header and all styles |

## Run it

From the project root, with WordPress 7.1.3 installed in `site/` and MySQL running (XAMPP):

```bash
php -S localhost:8080 -t site tools/router.php
```

Rebuild pages, menus and posts from the live site (safe to run again):

```bash
php tools/wp-cli.phar --path=site --user=admin eval-file tools/seed-site.php
```

## Still placeholder (never finished, since the theme was superseded)

- Logo, header/background images and the Black Jack font (`assets/images/logo.png`, `assets/images/bg.gif`,
  `assets/fonts/blackjack-webfont.woff2`) — the theme falls back to a text logo and Kaushan Script.
- Page, gallery and slider images load from lasemcakes.ca.
