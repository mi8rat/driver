# FlatCMS — Lightweight Flat-File CMS

A zero-dependency, flat-file CMS built with **vanilla PHP** (no frameworks, no databases).

## 📁 File Structure

```
flatcms/
├── index.php       ← Public site (reader view)
├── admin.php       ← Admin panel (create/edit/delete pages)
├── helpers.php     ← Core functions (Markdown parser, file I/O, auth)
├── config.php      ← Configuration (site title, password, etc.)
├── content/        ← Created automatically; stores .md page files
│   ├── welcome.md
│   └── about.md
└── README.md
```

## 🚀 Setup

1. Copy the folder to any PHP server (PHP 8.0+ required)
2. Visit `index.php` to see the public site
3. Visit `admin.php` to manage pages
4. Default admin password: **`admin123`**

## 🔑 Changing the Password

Edit `config.php`:

```php
define('ADMIN_PASSWORD', password_hash('your-new-password', PASSWORD_DEFAULT));
```

## ✍️ Content Format

Pages are stored as `.md` files with YAML front matter:

```markdown
---
title: My Page Title
date: 2026-03-01
---

## Hello World

Write your **Markdown** content here.
```

## ✅ Features

- **No database** — all content in flat `.md` files
- **Markdown rendering** — headings, bold, italic, lists, code blocks, blockquotes, links
- **Admin panel** — create, edit, delete pages with a live preview
- **CSRF protection** — all forms are CSRF-token protected
- **Password authentication** — bcrypt hashed password
- **Auto-slug generation** — slugs auto-generated from titles
- **Responsive design** — works on mobile too

## 🔒 Security Notes

- Change the default password in `config.php` before deploying
- Restrict access to `content/` directory in your web server config
- The CMS sanitizes all output with `htmlspecialchars()`

## 📋 Requirements

- PHP 8.0 or higher
- Web server (Apache, Nginx, or built-in PHP server)

### Run locally with PHP's built-in server:

```bash
cd flatcms
php -S localhost:8080
```

Then visit `http://localhost:8080`
