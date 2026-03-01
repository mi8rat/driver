<?php
// ─────────────────────────────────────────────
//  FlatCMS – Public Front-End
// ─────────────────────────────────────────────

require_once __DIR__ . '/helpers.php';

$slug = trim($_GET['page'] ?? '', '/');
$slug = $slug ? slugify($slug) : '';

// Seed default content if empty
if (!is_dir(CONTENT_DIR)) {
    mkdir(CONTENT_DIR, 0755, true);
    save_page('welcome', 'Welcome to FlatCMS',
        "## Hello, World!\n\nThis is your first page. Visit the [admin panel](admin.php) to create, edit, and delete content.\n\n### Features\n\n- **Flat-file storage** — no database needed\n- **Markdown** — write content naturally\n- **Lightweight** — pure vanilla PHP\n- **Secure** — CSRF protection & password hashing\n\n---\n\nEdit this page or create a new one from the admin panel.",
        date('Y-m-d')
    );
    save_page('about', 'About This Site',
        "## About FlatCMS\n\nFlatCMS is a minimal content management system that stores all content as Markdown files.\n\n### How it works\n\n1. Pages are stored as `.md` files in the `content/` folder\n2. Each file has YAML front matter for metadata\n3. The PHP engine renders everything on-the-fly\n\n### Getting started\n\nHead to the [admin panel](admin.php) to manage your content. Default password: **admin123** (change it in `config.php`).",
        date('Y-m-d')
    );
}

$pages   = get_all_pages();
$page    = $slug ? get_page($slug) : null;
$is_home = !$slug;

// Default to first page on home
if ($is_home && !empty($pages)) {
    $page = get_page($pages[0]['slug']);
}

if ($slug && !$page) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page ? h($page['title']) . ' — ' : '' ?><?= h(SITE_TITLE) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&family=DM+Mono&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ink:       #1a1612;
            --ink-soft:  #5c5248;
            --ink-faint: #9e978f;
            --paper:     #faf8f4;
            --paper-2:   #f2ede5;
            --accent:    #c84b2f;
            --accent-2:  #e8a87c;
            --border:    #e0d9cf;
            --nav-w:     280px;
        }

        html { font-size: 17px; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--paper);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Top Bar ── */
        .topbar {
            background: var(--ink);
            color: var(--paper);
            padding: 0 2rem;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .topbar-brand {
            font-family: 'DM Serif Display', serif;
            font-size: 1.25rem;
            color: var(--paper);
            text-decoration: none;
            letter-spacing: 0.02em;
        }
        .topbar-brand span { color: var(--accent-2); }
        .topbar-admin {
            font-size: 0.75rem;
            color: var(--ink-faint);
            text-decoration: none;
            border: 1px solid #3a3530;
            padding: 4px 12px;
            border-radius: 20px;
            transition: all .2s;
        }
        .topbar-admin:hover { border-color: var(--accent-2); color: var(--accent-2); }

        /* ── Layout ── */
        .layout {
            flex: 1;
            display: flex;
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
            padding: 0 1rem;
            gap: 0;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--nav-w);
            flex-shrink: 0;
            padding: 2.5rem 2rem 2rem 0;
            border-right: 1px solid var(--border);
        }
        .sidebar-label {
            text-transform: uppercase;
            letter-spacing: .12em;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--ink-faint);
            margin-bottom: 1rem;
        }
        .nav-list { list-style: none; }
        .nav-item { border-bottom: 1px solid var(--border); }
        .nav-item:first-child { border-top: 1px solid var(--border); }
        .nav-link {
            display: block;
            padding: .7rem .5rem;
            text-decoration: none;
            color: var(--ink-soft);
            font-size: .9rem;
            transition: all .15s;
            position: relative;
        }
        .nav-link:hover { color: var(--ink); padding-left: .9rem; }
        .nav-link.active {
            color: var(--accent);
            font-weight: 500;
            padding-left: .9rem;
        }
        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            transform: translateY(-50%);
            width: 3px; height: 60%;
            background: var(--accent);
            border-radius: 2px;
        }
        .nav-date {
            display: block;
            font-size: .68rem;
            color: var(--ink-faint);
            margin-top: 2px;
            font-family: 'DM Mono', monospace;
        }

        /* ── Main content ── */
        .main {
            flex: 1;
            padding: 2.5rem 0 3rem 3rem;
            min-width: 0;
        }

        .page-header {
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .page-title {
            font-family: 'DM Serif Display', serif;
            font-size: 2.4rem;
            line-height: 1.2;
            color: var(--ink);
            margin-bottom: .4rem;
        }
        .page-meta {
            font-size: .8rem;
            color: var(--ink-faint);
            font-family: 'DM Mono', monospace;
        }

        /* ── Prose ── */
        .prose { line-height: 1.75; color: var(--ink); }
        .prose h1, .prose h2, .prose h3, .prose h4 {
            font-family: 'DM Serif Display', serif;
            margin: 2rem 0 .75rem;
            line-height: 1.3;
        }
        .prose h2 { font-size: 1.5rem; }
        .prose h3 { font-size: 1.2rem; }
        .prose p  { margin-bottom: 1.1rem; }
        .prose ul, .prose ol { margin: 0 0 1.1rem 1.4rem; }
        .prose li { margin-bottom: .4rem; }
        .prose a  { color: var(--accent); text-decoration: none; border-bottom: 1px solid var(--accent-2); transition: border-color .15s; }
        .prose a:hover { border-color: var(--accent); }
        .prose code {
            font-family: 'DM Mono', monospace;
            font-size: .85em;
            background: var(--paper-2);
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--accent);
        }
        .prose pre {
            background: var(--ink);
            color: #e8e2d8;
            padding: 1.25rem 1.5rem;
            border-radius: 8px;
            overflow-x: auto;
            margin-bottom: 1.25rem;
            font-family: 'DM Mono', monospace;
            font-size: .82rem;
            line-height: 1.6;
        }
        .prose pre code { background: none; padding: 0; color: inherit; }
        .prose blockquote {
            border-left: 3px solid var(--accent);
            margin: 1.5rem 0;
            padding: .75rem 1.25rem;
            background: var(--paper-2);
            color: var(--ink-soft);
            font-style: italic;
        }
        .prose hr { border: none; border-top: 1px solid var(--border); margin: 2rem 0; }
        .prose strong { font-weight: 600; }
        .prose img { max-width: 100%; border-radius: 6px; margin: 1rem 0; }

        /* ── 404 ── */
        .not-found {
            text-align: center;
            padding: 4rem 1rem;
        }
        .not-found .big-num {
            font-family: 'DM Serif Display', serif;
            font-size: 7rem;
            color: var(--border);
            line-height: 1;
        }
        .not-found h2 { font-family: 'DM Serif Display', serif; font-size: 1.5rem; margin: 1rem 0 .5rem; }
        .not-found p  { color: var(--ink-soft); }
        .not-found a  { color: var(--accent); text-decoration: none; }

        /* ── Footer ── */
        footer {
            text-align: center;
            padding: 1.25rem;
            font-size: .72rem;
            color: var(--ink-faint);
            border-top: 1px solid var(--border);
            font-family: 'DM Mono', monospace;
        }
        footer a { color: var(--ink-faint); }

        /* ── Responsive ── */
        @media (max-width: 680px) {
            .sidebar { display: none; }
            .main    { padding: 1.5rem 0; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <a href="index.php" class="topbar-brand"><?= h(SITE_TITLE) ?><span>.</span></a>
    <a href="admin.php" class="topbar-admin">⚙ Admin</a>
</header>

<div class="layout">
    <nav class="sidebar">
        <p class="sidebar-label">Pages</p>
        <ul class="nav-list">
            <?php foreach ($pages as $p):
                $active = ($page && $page['slug'] === $p['slug']) ? 'active' : '';
            ?>
            <li class="nav-item">
                <a href="index.php?page=<?= h($p['slug']) ?>" class="nav-link <?= $active ?>">
                    <?= h($p['title']) ?>
                    <?php if ($p['date']): ?>
                        <span class="nav-date"><?= h($p['date']) ?></span>
                    <?php endif ?>
                </a>
            </li>
            <?php endforeach ?>
        </ul>
    </nav>

    <main class="main">
        <?php if ($slug && !$page): ?>
            <div class="not-found">
                <div class="big-num">404</div>
                <h2>Page not found</h2>
                <p>The page <strong><?= h($slug) ?></strong> doesn't exist. <a href="index.php">Go home</a>.</p>
            </div>
        <?php elseif ($page): ?>
            <div class="page-header">
                <h1 class="page-title"><?= h($page['title']) ?></h1>
                <?php if ($page['date']): ?>
                    <p class="page-meta">Published <?= date(DATE_FORMAT, strtotime($page['date'])) ?></p>
                <?php endif ?>
            </div>
            <div class="prose">
                <?= markdown_to_html($page['body']) ?>
            </div>
        <?php else: ?>
            <div class="not-found">
                <div class="big-num">∅</div>
                <h2>No pages yet</h2>
                <p>Head to the <a href="admin.php">admin panel</a> to create your first page.</p>
            </div>
        <?php endif ?>
    </main>
</div>

<footer>
    <?= h(SITE_TITLE) ?> — powered by vanilla PHP &nbsp;·&nbsp; <a href="admin.php">Admin</a>
</footer>
</body>
</html>
