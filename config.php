<?php
// ─────────────────────────────────────────────
//  FlatCMS – Configuration
// ─────────────────────────────────────────────

define('SITE_TITLE',       'FlatCMS');
define('SITE_TAGLINE',     'A lightweight flat-file CMS');
define('CONTENT_DIR',      __DIR__ . '/content/');
define('ADMIN_PASSWORD',   password_hash('admin123', PASSWORD_DEFAULT)); // Change this!
define('DATE_FORMAT',      'F j, Y');

// Allowed tags for the simple Markdown parser
define('ALLOWED_HTML_TAGS', '<h1><h2><h3><h4><p><ul><ol><li><strong><em><a><code><pre><blockquote><hr><br><img>');
