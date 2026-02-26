# SimpleCMS

A lightweight, flat-file Content Management System built with vanilla PHP.

## Features

- ✅ Create, edit, delete posts
- ✅ Draft / Published status
- ✅ Public blog view with single-post pages
- ✅ Admin dashboard with post stats
- ✅ Password-protected admin area
- ✅ Flat-file JSON storage (no database needed)
- ✅ Auto-generated slugs from titles
- ✅ Session-based auth
- ✅ Single file — easy to deploy

## Setup

1. Copy `index.php` to any folder on a PHP server (PHP 8.0+ recommended).
2. Make sure the directory is **writable** so `posts.json` can be created:
   ```bash
   chmod 755 /your/folder
   ```
3. Open in browser.

## Login

- Default password: `admin123`
- **Change it** by editing the constant at the top of `index.php`:
  ```php
  define('ADMIN_PASSWORD', 'your-secure-password');
  ```

## URL Actions

| URL | Description |
|-----|-------------|
| `?action=home` | Public blog listing |
| `?action=view&id=...` | Single post view |
| `?action=login` | Admin login |
| `?action=admin` | Dashboard |
| `?action=new` | Create post |
| `?action=edit&id=...` | Edit post |

## File Structure

```
/
├── index.php    ← The entire CMS
└── posts.json   ← Auto-created on first post
```

## Security Notes

- Change the default password immediately
- For production, consider adding CSRF tokens
- Optionally move `posts.json` outside the web root
