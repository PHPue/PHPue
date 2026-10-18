<div align="center">

<img src="https://phpue.co.uk/assets/img/favicon_io/android-chrome-512x512.png" width="96" height="96" alt="PHPue logo">

# PHPue

**PHP power. Vue-like joy.**

Server-rendered apps from single-file components, with hot reload, file-based routing and AJAX built in.<br>
No bundler. No `node_modules`. Just PHP.

[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4+-777bb4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](LICENSE)
[![Used by 1500+](https://img.shields.io/badge/Users-2500+-orange.svg)](https://phpue.co.uk)
[![Live Demo](https://img.shields.io/badge/Live-Demo-success.svg)](https://phpue.co.uk/)

[Website](https://phpue.co.uk) · [Docs](https://phpue.co.uk/docs) · [Tutorials](https://phpue.co.uk/ue-tutorials) · [Releases](https://phpue.co.uk/ue-releases) · [Insights](https://phpue.co.uk/ue-insights) · [Deployment](https://phpue.co.uk/deployment)

</div>

---

## 🚀 Get started

```bash
git clone https://github.com/PHPue/PHPue.git
cd PHPue/www
php -S localhost:3000
```

Open **http://localhost:3000/?live**, then edit `www/views/index.pvue`. The page reloads as you save. 🔥

<details>
<summary>Prefer Docker?</summary>

```bash
docker compose up
```

PHP-FPM and NGINX are set up for you at **http://localhost:3000**.
</details>

### 📦 Build for production

```bash
cd www
php index.php build
```

Everything compiles into `www/.dist/`. Upload its contents to any PHP host (`public_html/`, `/var/www/html`, …). The `.htaccess` and [`nginx/default.conf`](nginx/default.conf) for routing are included. See the [deployment guide](https://phpue.co.uk/deployment).

> **Heads up:** while `.dist/` exists, the server serves the compiled files. Rebuild or delete it to see your `.pvue` changes.

---

## 🎓 Learn PHPue

Work through the tutorials in order, or jump to the one you need. [Browse all tutorials →](https://phpue.co.uk/ue-tutorials)

| | Tutorial | What you'll learn |
|---|---|---|
| 1 | [**Setting up a server**](https://phpue.co.uk/ue-tutorials?tutorialName=setup-server) | Getting PHPue running with PHP CLI, XAMPP or Docker |
| 2 | [**Your first app**](https://phpue.co.uk/ue-tutorials?tutorialName=first-app) | Views, components, `p-if` and `p-for` |
| 3 | [**Styling your app**](https://phpue.co.uk/ue-tutorials?tutorialName=styling-your-app) | Tailwind and your own CSS |
| 4 | [**AlpineJS foundations**](https://phpue.co.uk/ue-tutorials?tutorialName=alpinejs-foundations) | Client-side interactivity on top of server-rendered pages |

📖 For the full reference, read the **[PHPue documentation](https://phpue.co.uk/docs)**.

---

## 🧩 Ecosystem

| | Project | What it's for |
|---|---|---|
| 🔌 | [**PHPue Extensions**](https://github.com/PHPue/PHPue-Extensions) | Official extensions, one branch per category: **MetaControl** (dynamic SEO/meta), **Matrix CMS** (flat-file CMS), **x-ssr** (AlpineJS + server-rendered lists), the **Elementor bridge** for WordPress, and more |
| 🎨 | [**Syntax Highlighter**](https://marketplace.visualstudio.com/items?itemName=PHPue.pvue-syntax) | `.pvue` highlighting for VS Code. Search *PHPue Syntax Highlighter* in Extensions ([source](https://github.com/ShinkuKira21/PHPue-SyntaxHighlighter)) |
| 🤖 | [**PHPue for AI**](https://ai.phpue.co.uk/) | LLM-optimised docs plus [ready-made prompts](https://github.com/PHPue/PHPue-AI) so your AI assistant writes real PHPue |

---

## ✨ A taste

```html
<!-- www/views/tasks.pvue  →  served at /tasks -->
<script>
    $tasks = ['Ship it', 'Celebrate'];

    @AJAX('POST')
    function addTask($input) {
        echo json_encode(['added' => $input['task']]);
    }
</script>

<header>
    <title>Tasks</title>
</header>

<template>
    <ul>
        <li p-for="$task in $tasks">{{ $task }}</li>
    </ul>
</template>

<cscript>
    console.log({{ $tasks }}); // PHP data, straight into JS
</cscript>
```

One file holds the server logic, head tags, markup and browser code. Register it in `App.pvue` with `#require 'views/tasks.pvue';` and it's live.

---

## 🗂️ What's in the box

```text
www/
├── App.pvue          # app shell: head tags, Navbar, <View/>, Footer
├── views/            # one file per route (index.pvue → /)
├── components/       # reusable .pvue pieces, used as <Navbar/>
├── assets/           # images, CSS, JS
├── conversion.php    # the compiler: .pvue → PHP
└── index.php         # router, dev server and `build` command
```

Need shared PHP classes? Create `www/backend/` and they're autoloaded.

---

## 💡 Why PHPue?

- **Server-side rendering.** Finished HTML on first paint, which helps SEO and speed.
- **Single-file components** with a familiar, Vue-inspired syntax.
- **File-based routing:** `views/about.pvue` is `/about`.
- **`@AJAX` endpoints:** call a PHP function straight from `fetch()`.
- **Plain PHP, always.** Drop to raw PHP anywhere. No lock-in.
- **Runs anywhere PHP runs:** PHP CLI, XAMPP, Docker, shared hosting or FTP.

---

## ❤️ Support & contribute

PHPue is open source under the [Apache 2.0 licence](LICENSE). Issues, ideas and pull requests are welcome, and extensions are especially welcome on [PHPue Extensions](https://github.com/PHPue/PHPue-Extensions).

If PHPue makes your day better, you can support it on [GitHub Sponsors](https://github.com/sponsors/ShinkuKira21) or [Patreon](https://www.patreon.com/phpue).

<div align="center">

**Built with ❤️ by [Edward Patch](https://github.com/ShinkuKira21) and 2500+ developers who believe in less complexity.**

</div>
