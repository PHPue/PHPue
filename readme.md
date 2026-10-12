<div align="center">

<img src="https://phpue.co.uk/assets/img/favicon_io/android-chrome-512x512.png" width="96" height="96" alt="PHPue logo">

# PHPue Example · v0.0.3

**The official PHPue example app, rebuilt for the 0.0.3 bug-fix release.**

Sessions, `@AJAX`, `p-if`, `p-for`, components and a bilingual UI, all rendered on the server.<br>
Every card in the app is a small, working demo you can read in a single `.pvue` file.

[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0+-777bb4.svg)](https://php.net)
[![PHPue 0.0.3](https://img.shields.io/badge/PHPue-0.0.3-1e5c9a.svg)](https://phpue.co.uk/ue-releases)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](LICENSE)

[Website](https://phpue.co.uk) · [Docs](https://phpue.co.uk/docs) · [Tutorials](https://phpue.co.uk/ue-tutorials) · [Releases](https://phpue.co.uk/ue-releases) · [Main repo](https://github.com/PHPue/PHPue)

</div>

---

## 🚀 Run it

```bash
git clone -b example-v0.0.3 https://github.com/PHPue/PHPue.git phpue-example
cd phpue-example/www
php -S localhost:3000
```

Open **http://localhost:3000/?live** for hot reload, or run `docker compose up` from the repo root for PHP-FPM + NGINX on the same port.

> PHPue 0.0.3 needs **PHP 8.0 or newer**.

---

## 🧭 What's inside

### `/` — the example, refreshed

| Demo | Shows |
|---|---|
| **Server snapshot** | `p-for="$label => $value in $serverFacts"` with keys |
| **Session counter** | `@AJAX("POST")` handlers with typed parameters and return types, state kept in `$_SESSION` |
| **Ask the server** | An `@AJAX('GET')` handler called with `fetch('?action=serverClock')` |
| **Same maths, two runtimes** | The same value from PHP (`{{ $age }}`) and from JavaScript |
| **PHP → JavaScript bridge** | `{{ $fruitNames }}` inside `<cscript>` becomes real JSON |
| **Fruit basket** | `<FruitCard p-for="$fruit in $fruits" />`, `{{ count($fruits) }}`, and `p-for` + `p-if` on one element |
| **A form, the PHPue way** | Explicit submit to `@AJAX`, server-side validation, and `<UserForm p-if="$bFormOpen" />` |

### `/whats-new` — 0.0.3, demonstrated live

Ten cards, each showing the `.pvue` source and rendering it underneath: `p-if` on any element, `p-for` over `range()` and generators, nested same-tag loops, `p-for`/`p-if` on component tags, `{{ }}` with function and static calls, nested `<template>` elements, enforced `@AJAX` methods (try the GET button and get a 405), array values in `<cscript>`, and per-request `<html lang>`.

### 🌍 English / Cymraeg

Click **EN / CY** in the navbar. `backend/lang/autoload.php` picks the language (`?lang=` → cookie → English), and `Lang::boot()` in `App.pvue` calls the new `phpue_set_lang()` so `<html lang>` follows. Missing keys in a pack fall back to English.

---

## 🗂️ Project layout

```text
www/
├── App.pvue                     # shell: head tags, theme, Navbar, <View/>, Footer, Lang::boot()
├── views/
│   ├── index.pvue               # /           the example demos
│   └── whats-new.pvue           # /whats-new  0.0.3 showcase
├── components/
│   ├── layout/                  # Navbar (with language switcher), Footer
│   ├── cards/                   # FruitCard, StatPill, DemoHeader (read parent variables, no props)
│   ├── dashboards/              # submitted-details card
│   └── forms/                   # the @AJAX form
├── backend/                     # autoloaded
│   ├── lang/autoload.php        # Lang class (only this file autoloads; packs load on demand)
│   ├── lang/en.php, cy.php      # language packs
│   └── Showcase.php             # snippets for /whats-new, kept out of the compiler's way
├── httpReqs/                    # custom 404 page (rendered inside the App layout)
├── conversion.php               # PHPue 0.0.3 compiler
└── index.php                    # PHPue 0.0.3 router, dev server and `build` command
```

---

## 📦 Build for production

```bash
cd www
php index.php build
```

Upload the contents of `www/.dist/` to any PHP host. `.htaccess` (Apache) and [`nginx/default.conf`](nginx/default.conf) handle routing. While `.dist/` exists the server serves it, so delete it (or rebuild) to see `.pvue` edits again. In a built site the dev endpoints (`?compile=`, `?hot-reload`, `?build`) are switched off.

---

## ⬆️ Coming from the old example?

- `index.php` and `conversion.php` are **0.0.3**: always upgrade them together.
- `components/Navbar.pvue` moved to `components/layout/Navbar.pvue`, and there's a new `Footer.pvue`.
- `load_lang('english')` became `Lang::pack()` / `Lang::t('key')`.
- jQuery and `assets/css/styles.css` are gone. Brand colours (`ue-blue`, `ue-green`, …) live in the Tailwind `@theme` block in `App.pvue`.
- The form now validates on the server, keeps your answers when you edit them, and reads `favourite_fruit` correctly.

<div align="center">

**Built with ❤️ by [Edward Patch](https://github.com/ShinkuKira21) and the PHPue community.**

</div>
