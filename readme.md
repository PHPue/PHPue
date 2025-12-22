# ❤️ PHP Power + Vue Syntax = PHPue | <img src="https://phpue.co.uk/assets/img/favicon_io/android-chrome-512x512.png" width="45" height="45" alt="PHPue Logo">

> **Build Vue-like apps in pure PHP • Hot reload included • 300+ developers believe in less complexity and developer joy!**

> *Join the movement today and build PHPue Websites Today!*

[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4+-brightgreen.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](LICENSE)
[![Used by 300+](https://img.shields.io/badge/Users-300+-orange.svg)](https://phpue.co.uk)
[![Live Demo](https://img.shields.io/badge/Live-Demo-success.svg)](https://phpue.co.uk/)

**Official Website:** [PHPue](https://phpue.co.uk) | **Official Documentation:** [PHPue Documentation](https://phpue.co.uk/docs)
---
[PHPue Insights](https://phpue.co.uk/ue-insights) - [PHPue Releases](https://phpue.co.uk/ue-releases) - [PHPue Deployment](https://phpue.co.uk/deployment)
---
---

## ⚡ **Try it in 30 seconds (no joke)**

```bash
# 1. Download the 2 essential files
git clone https://github.com/PHPue/PHPue.git

# 2. Create your first app
cat > App.pvue << 'EOF'
<template>
    <div style="text-align: center; padding: 3rem;">
        <h1>🎉 Welcome to PHPue!</h1>
        <p>You're running Vue-like syntax in PHP!</p>
        <button onclick="showAlert()">Click me!</button>
    </div>
</template>

<cscript>
    function showAlert() {
        alert("PHPue is working!");
    }
</cscript>
EOF

# 3. Run it with hot reload
php -S localhost:8000
```

**Now open:** ```http://localhost:8000?live=1``` - Edit the file, and watch it auto-refresh! 🔥

```phpue
<!-- Write Vue components that run in PHP -->
<script>
    // you'd normally have the Database class in backend/Database.php
    // yes, it's auto included! so you only have to call `$db = new Database();`
    class Database {
        public function fetchPosts() {
            return [
                ['id' => 1, 'title' => 'First Post', 'content' => 'This is the first post.'],
                ['id' => 2, 'title' => 'Second Post', 'content' => 'This is the second post.'],
            ];
        }
    }
    
    $db = new Database();
    $posts = $db->fetchPosts(); // Real PHP database!
</script>

<template>
    <div p-for="$post in $posts" class="post-card">
        <h3>{{ $post['title'] }}</h3>
        <p>{{ $post['content'] }}</p>
    </div>
</template>

<cscript>
    // PHP variables available in JavaScript!
    console.log({{ $posts }});
</cscript>
```

## 💡 Why PHPue?
✅ Server-Side Rendering - Better SEO and performance

✅ Hot Reload - Instant development feedback

✅ Vue-Inspired Syntax - Familiar and intuitive

✅ AJAX Decoration

✅ Unified File Structure - Accessible PHP, HTML, and JS!

✅ No Framework Lock In ! - Use PHP scripts where you need!

✅ Auto Loader Classes (backend/) - Instead of Middleware!

✅ PHP Power - Full access to PHP ecosystem

✅ Component-Based - Reusable and maintainable

✅ Auto Routing - File-based routing system

✅ PHP+JS Integration - Seamless variable passing

✅ Production Ready - Build system for deployment

## Production Build
```
# Build for production
cd www/ && php index.php build
cd .dist && zip -r ../dist.zip .

# Deploy (just copy files or zip the .dist/ || contents of .dist/!)
# Upload to public_html/ or /var/www/html and it's live!
# (Make sure to setup .htaccess or nginx for the routing to work correctly)
# Which is included in the main branch!
```

**🎉 Get Building!**
PHPue gives you the best of both worlds: the simplicity and power of PHP with the modern developer experience of component-based frameworks. Start building your next amazing web application today! 🚀

PHPue - Server-side rendering with Vue-like syntax. Fast, simple, powerful.