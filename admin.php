<?php
// ─────────────────────────────────────────────
//  FlatCMS – Admin Panel
// ─────────────────────────────────────────────

require_once __DIR__ . '/helpers.php';
session_start_once();

$action  = $_GET['action']  ?? 'list';
$slug    = $_GET['slug']    ?? '';
$msg     = '';
$error   = '';

// ── Authentication ────────────────────────────
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php?action=login');
    exit;
}

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pw = $_POST['password'] ?? '';
        if (password_verify($pw, ADMIN_PASSWORD)) {
            session_regenerate_id(true);
            $_SESSION['flatcms_auth'] = true;
            header('Location: admin.php');
            exit;
        }
        $error = 'Incorrect password.';
    }
    // Show login form
    render_login_page($error);
    exit;
}

require_login();

// ── Handle POST actions ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token. Go back and try again.');
    }

    // Save (create or update)
    if (($action === 'new' || $action === 'edit') && isset($_POST['save'])) {
        $title    = trim($_POST['title'] ?? '');
        $body     = $_POST['body']  ?? '';
        $new_slug = slugify($_POST['slug'] ?? $title);
        $date     = trim($_POST['date'] ?? date('Y-m-d'));

        if (!$title)    { $error = 'Title is required.'; }
        if (!$new_slug) { $error = 'Slug could not be generated.'; }

        if (!$error) {
            // If slug changed during edit, delete old file
            if ($action === 'edit' && $slug && $new_slug !== $slug) {
                delete_page($slug);
            }
            save_page($new_slug, $title, $body, $date);
            header("Location: admin.php?action=edit&slug={$new_slug}&saved=1");
            exit;
        }
    }

    // Delete
    if ($action === 'delete' && isset($_POST['confirm_delete'])) {
        delete_page($slug);
        header('Location: admin.php?deleted=1');
        exit;
    }
}

$saved   = isset($_GET['saved']);
$deleted = isset($_GET['deleted']);

// ── Render ────────────────────────────────────
$pages = get_all_pages();
$edit_page = ($action === 'edit' && $slug) ? get_page($slug) : null;

// ─────────────────────────────────────────────
//  HTML Output
// ─────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= h(SITE_TITLE) ?></title>
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
            --green:     #2d7a4f;
            --sidebar-w: 260px;
        }

        html { font-size: 16px; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--paper);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ── */
        .topbar {
            background: var(--ink);
            height: 52px;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            gap: 1rem;
            position: sticky; top: 0; z-index: 100;
        }
        .topbar-brand {
            font-family: 'DM Serif Display', serif;
            color: var(--paper);
            text-decoration: none;
            font-size: 1.1rem;
        }
        .topbar-brand span { color: var(--accent-2); }
        .topbar-divider { color: #3a3530; }
        .topbar-title { color: var(--ink-faint); font-size: .85rem; }
        .topbar-actions { margin-left: auto; display: flex; gap: .75rem; align-items: center; }
        .btn-sm {
            font-size: .75rem; font-family: 'DM Sans', sans-serif;
            padding: 5px 14px; border-radius: 20px; cursor: pointer;
            text-decoration: none; border: 1px solid transparent;
            transition: all .15s; display: inline-block;
        }
        .btn-ghost  { border-color: #3a3530; color: var(--ink-faint); background: none; }
        .btn-ghost:hover { border-color: var(--accent-2); color: var(--accent-2); }
        .btn-danger { border-color: var(--accent); color: var(--accent); background: none; }
        .btn-danger:hover { background: var(--accent); color: #fff; }

        /* ── Layout ── */
        .layout { flex: 1; display: flex; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            background: #f5f0e8;
            border-right: 1px solid var(--border);
            padding: 1.5rem 0;
            display: flex; flex-direction: column;
        }
        .sidebar-section { padding: 0 1.25rem; margin-bottom: 1.5rem; }
        .sidebar-label {
            text-transform: uppercase;
            letter-spacing: .1em;
            font-size: .67rem;
            font-weight: 600;
            color: var(--ink-faint);
            padding: 0 1.25rem;
            margin-bottom: .6rem;
        }
        .new-btn {
            display: block; text-align: center; text-decoration: none;
            background: var(--accent); color: #fff;
            font-size: .82rem; font-weight: 500;
            padding: .55rem 1rem; border-radius: 6px;
            transition: background .15s;
        }
        .new-btn:hover { background: #a83c22; }

        .page-list { list-style: none; }
        .page-item { border-bottom: 1px solid var(--border); }
        .page-link {
            display: flex; flex-direction: column;
            padding: .65rem 1.25rem;
            text-decoration: none;
            color: var(--ink-soft);
            font-size: .85rem;
            transition: background .12s;
        }
        .page-link:hover { background: #ece7dd; }
        .page-link.active { background: var(--paper); color: var(--ink); font-weight: 500; }
        .page-link-date { font-size: .67rem; color: var(--ink-faint); font-family: 'DM Mono', monospace; margin-top: 2px; }
        .page-slug { font-size: .67rem; color: var(--ink-faint); font-family: 'DM Mono', monospace; }

        .sidebar-footer { margin-top: auto; padding: 1rem 1.25rem; }
        .sidebar-footer a { font-size: .75rem; color: var(--ink-faint); text-decoration: none; }
        .sidebar-footer a:hover { color: var(--accent); }

        /* ── Main area ── */
        .main { flex: 1; padding: 2rem 2.5rem; min-width: 0; }

        /* ── Page list view ── */
        .admin-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 2rem; padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .admin-header h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.75rem;
        }
        .pages-table {
            width: 100%; border-collapse: collapse;
            font-size: .88rem;
        }
        .pages-table th {
            text-align: left; padding: .6rem .75rem;
            border-bottom: 2px solid var(--border);
            font-size: .72rem; text-transform: uppercase;
            letter-spacing: .08em; color: var(--ink-faint);
        }
        .pages-table td {
            padding: .75rem .75rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .pages-table tr:hover td { background: var(--paper-2); }
        .pages-table .col-title { font-weight: 500; }
        .pages-table .col-slug  { font-family: 'DM Mono', monospace; font-size: .78rem; color: var(--ink-faint); }
        .pages-table .col-date  { font-family: 'DM Mono', monospace; font-size: .78rem; color: var(--ink-faint); }
        .pages-table .col-actions { white-space: nowrap; }
        .action-link {
            text-decoration: none; font-size: .78rem; margin-right: .5rem;
            color: var(--ink-soft); transition: color .12s;
        }
        .action-link:hover { color: var(--accent); }
        .action-link.del:hover { color: #c0392b; }

        /* ── Empty state ── */
        .empty-state {
            text-align: center; padding: 4rem 2rem;
            color: var(--ink-faint);
        }
        .empty-state .icon { font-size: 3rem; margin-bottom: 1rem; }
        .empty-state p { margin-bottom: 1.25rem; }

        /* ── Editor ── */
        .editor-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: start;
        }
        .editor-form { display: flex; flex-direction: column; gap: 1.25rem; }
        .form-group { display: flex; flex-direction: column; gap: .4rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        label {
            font-size: .75rem; text-transform: uppercase;
            letter-spacing: .08em; font-weight: 600;
            color: var(--ink-faint);
        }
        input[type=text], input[type=date], textarea {
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem; color: var(--ink);
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: .6rem .85rem;
            width: 100%;
            transition: border-color .15s, box-shadow .15s;
            outline: none;
        }
        input[type=text]:focus, input[type=date]:focus, textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(200,75,47,.08);
        }
        textarea {
            font-family: 'DM Mono', monospace;
            font-size: .82rem;
            min-height: 420px;
            resize: vertical;
            line-height: 1.6;
        }
        .btn-primary {
            background: var(--accent); color: #fff;
            border: none; border-radius: 6px;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem; font-weight: 500;
            padding: .65rem 1.5rem; cursor: pointer;
            transition: background .15s;
        }
        .btn-primary:hover { background: #a83c22; }

        /* ── Preview ── */
        .preview-panel {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            position: sticky; top: 68px;
        }
        .preview-label {
            font-size: .7rem; text-transform: uppercase;
            letter-spacing: .1em; font-weight: 600;
            color: var(--ink-faint); margin-bottom: 1rem;
        }
        .preview-title {
            font-family: 'DM Serif Display', serif;
            font-size: 1.5rem; margin-bottom: 1rem;
            padding-bottom: .75rem; border-bottom: 1px solid var(--border);
            color: var(--ink);
        }
        .preview-body { font-size: .88rem; line-height: 1.75; color: var(--ink); }
        .preview-body h1,.preview-body h2,.preview-body h3 {
            font-family: 'DM Serif Display', serif; margin: 1.25rem 0 .5rem;
        }
        .preview-body p  { margin-bottom: .9rem; }
        .preview-body code {
            font-family: 'DM Mono', monospace; font-size: .82em;
            background: var(--paper-2); padding: 2px 5px; border-radius: 3px; color: var(--accent);
        }
        .preview-body pre {
            background: var(--ink); color: #e8e2d8;
            padding: 1rem 1.25rem; border-radius: 6px;
            overflow-x: auto; font-family: 'DM Mono', monospace;
            font-size: .78rem; margin-bottom: 1rem;
        }
        .preview-body pre code { background: none; color: inherit; }
        .preview-body blockquote {
            border-left: 3px solid var(--accent); margin: 1rem 0;
            padding: .5rem 1rem; background: var(--paper-2);
            color: var(--ink-soft); font-style: italic;
        }
        .preview-body hr { border: none; border-top: 1px solid var(--border); margin: 1.5rem 0; }
        .preview-body ul, .preview-body ol { margin: 0 0 .9rem 1.2rem; }
        .preview-body li { margin-bottom: .3rem; }
        .preview-body strong { font-weight: 600; }
        .preview-body a { color: var(--accent); }

        /* ── Delete confirm ── */
        .delete-panel {
            max-width: 480px;
            background: white; border: 1px solid #f3c4bc; border-radius: 8px;
            padding: 2rem;
        }
        .delete-panel h2 { font-family: 'DM Serif Display', serif; margin-bottom: .75rem; }
        .delete-panel p  { color: var(--ink-soft); margin-bottom: 1.5rem; font-size: .9rem; }
        .delete-panel .actions { display: flex; gap: .75rem; }
        .btn-cancel {
            padding: .6rem 1.25rem; border: 1px solid var(--border);
            background: none; border-radius: 6px; cursor: pointer;
            font-family: 'DM Sans', sans-serif; font-size: .9rem;
            text-decoration: none; color: var(--ink-soft);
            transition: all .15s;
        }
        .btn-cancel:hover { background: var(--paper-2); }
        .btn-delete {
            padding: .6rem 1.25rem; border: none;
            background: #c0392b; color: #fff; border-radius: 6px;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            font-size: .9rem; transition: background .15s;
        }
        .btn-delete:hover { background: #a93226; }

        /* ── Alerts ── */
        .alert {
            padding: .7rem 1rem; border-radius: 6px;
            font-size: .85rem; margin-bottom: 1.5rem;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* ── Stats bar ── */
        .stats-bar {
            display: flex; gap: 2rem; margin-bottom: 2rem;
            padding: 1rem 1.25rem; background: var(--paper-2);
            border-radius: 8px; border: 1px solid var(--border);
        }
        .stat { display: flex; flex-direction: column; }
        .stat-num { font-family: 'DM Serif Display', serif; font-size: 1.6rem; color: var(--ink); }
        .stat-lbl { font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; color: var(--ink-faint); font-weight: 600; }

        @media (max-width: 900px) {
            .editor-grid { grid-template-columns: 1fr; }
            .preview-panel { position: static; }
        }
        @media (max-width: 680px) {
            .sidebar { display: none; }
            .main { padding: 1.25rem; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <a href="admin.php" class="topbar-brand"><?= h(SITE_TITLE) ?><span>.</span></a>
    <span class="topbar-divider">›</span>
    <span class="topbar-title">
        <?php
        $titles = ['list'=>'All Pages','new'=>'New Page','edit'=>'Edit Page','delete'=>'Delete Page'];
        echo h($titles[$action] ?? 'Admin');
        ?>
    </span>
    <div class="topbar-actions">
        <a href="index.php" class="btn-sm btn-ghost" target="_blank">View Site ↗</a>
        <a href="admin.php?action=logout" class="btn-sm btn-ghost">Logout</a>
    </div>
</header>

<div class="layout">

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-section">
            <a href="admin.php?action=new" class="new-btn">+ New Page</a>
        </div>
        <p class="sidebar-label">Pages</p>
        <ul class="page-list">
            <?php foreach ($pages as $p):
                $active = (($action === 'edit' || $action === 'delete') && $slug === $p['slug']) ? 'active' : '';
            ?>
            <li class="page-item">
                <a href="admin.php?action=edit&slug=<?= h($p['slug']) ?>" class="page-link <?= $active ?>">
                    <?= h($p['title']) ?>
                    <span class="page-slug">/<?= h($p['slug']) ?></span>
                </a>
            </li>
            <?php endforeach ?>
            <?php if (!$pages): ?>
            <li style="padding:.75rem 1.25rem;font-size:.82rem;color:var(--ink-faint)">No pages yet.</li>
            <?php endif ?>
        </ul>
        <div class="sidebar-footer">
            <a href="index.php" target="_blank">← Back to site</a>
        </div>
    </nav>

    <!-- Main -->
    <main class="main">

        <?php if ($saved): ?>
            <div class="alert alert-success">✓ Page saved successfully.</div>
        <?php endif ?>
        <?php if ($deleted): ?>
            <div class="alert alert-success">✓ Page deleted.</div>
        <?php endif ?>
        <?php if ($error): ?>
            <div class="alert alert-error">⚠ <?= h($error) ?></div>
        <?php endif ?>

        <?php /* ── LIST ── */ if ($action === 'list'): ?>

            <div class="admin-header">
                <h1>All Pages</h1>
                <a href="admin.php?action=new" class="btn-primary" style="text-decoration:none;border-radius:6px;padding:.55rem 1.25rem;font-size:.88rem">+ New Page</a>
            </div>

            <div class="stats-bar">
                <div class="stat">
                    <span class="stat-num"><?= count($pages) ?></span>
                    <span class="stat-lbl">Total Pages</span>
                </div>
                <div class="stat">
                    <span class="stat-num"><?= count(array_filter($pages, fn($p) => $p['date'] === date('Y-m-d'))) ?></span>
                    <span class="stat-lbl">Published Today</span>
                </div>
            </div>

            <?php if ($pages): ?>
            <table class="pages-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $p): ?>
                    <tr>
                        <td class="col-title"><?= h($p['title']) ?></td>
                        <td class="col-slug">/<?= h($p['slug']) ?></td>
                        <td class="col-date"><?= h($p['date']) ?></td>
                        <td class="col-actions">
                            <a href="index.php?page=<?= h($p['slug']) ?>" class="action-link" target="_blank">View</a>
                            <a href="admin.php?action=edit&slug=<?= h($p['slug']) ?>" class="action-link">Edit</a>
                            <a href="admin.php?action=delete&slug=<?= h($p['slug']) ?>" class="action-link del">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📄</div>
                <p>No pages yet. Create your first page!</p>
                <a href="admin.php?action=new" class="btn-primary" style="text-decoration:none;display:inline-block;border-radius:6px;padding:.6rem 1.5rem">+ Create Page</a>
            </div>
            <?php endif ?>

        <?php /* ── NEW / EDIT ── */ elseif ($action === 'new' || $action === 'edit'): ?>

            <div class="admin-header">
                <h1><?= $action === 'new' ? 'New Page' : 'Edit Page' ?></h1>
                <?php if ($edit_page): ?>
                    <div style="display:flex;gap:.75rem;align-items:center">
                        <a href="index.php?page=<?= h($slug) ?>" class="btn-sm btn-ghost" target="_blank">View ↗</a>
                        <a href="admin.php?action=delete&slug=<?= h($slug) ?>" class="btn-sm btn-danger">Delete</a>
                    </div>
                <?php endif ?>
            </div>

            <div class="editor-grid">
                <form method="POST" action="admin.php?action=<?= h($action) ?>&slug=<?= h($slug) ?>" class="editor-form" id="editor-form">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

                    <div class="form-group">
                        <label for="f-title">Title</label>
                        <input type="text" id="f-title" name="title" required
                            value="<?= h($edit_page['title'] ?? '') ?>"
                            placeholder="My Awesome Page">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="f-slug">Slug (URL)</label>
                            <input type="text" id="f-slug" name="slug"
                                value="<?= h($edit_page['slug'] ?? '') ?>"
                                placeholder="auto-generated">
                        </div>
                        <div class="form-group">
                            <label for="f-date">Date</label>
                            <input type="date" id="f-date" name="date"
                                value="<?= h($edit_page['date'] ?? date('Y-m-d')) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="f-body">Content (Markdown)</label>
                        <textarea id="f-body" name="body" spellcheck="false"
                            placeholder="## Hello World&#10;&#10;Write your content here using **Markdown**."><?= h($edit_page['body'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <button type="submit" name="save" class="btn-primary">Save Page</button>
                    </div>
                </form>

                <div class="preview-panel">
                    <p class="preview-label">Live Preview</p>
                    <div id="preview-title" class="preview-title"><?= h($edit_page['title'] ?? 'Untitled') ?></div>
                    <div id="preview-body" class="preview-body">
                        <!-- filled by JS -->
                        <em style="color:var(--ink-faint)">Start typing to see a preview…</em>
                    </div>
                </div>
            </div>

        <?php /* ── DELETE ── */ elseif ($action === 'delete'): ?>

            <?php $del = get_page($slug) ?>
            <div class="admin-header"><h1>Delete Page</h1></div>

            <?php if ($del): ?>
            <div class="delete-panel">
                <h2>Are you sure?</h2>
                <p>You're about to permanently delete <strong><?= h($del['title']) ?></strong> (<code>/<?= h($slug) ?></code>). This cannot be undone.</p>
                <form method="POST" action="admin.php?action=delete&slug=<?= h($slug) ?>">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="actions">
                        <a href="admin.php?action=edit&slug=<?= h($slug) ?>" class="btn-cancel">Cancel</a>
                        <button type="submit" name="confirm_delete" class="btn-delete">Yes, Delete</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
                <p>Page not found.</p>
            <?php endif ?>

        <?php endif ?>

    </main>
</div>

<script>
// ── Auto-slug from title ──────────────────────
const titleEl = document.getElementById('f-title');
const slugEl  = document.getElementById('f-slug');

if (titleEl && slugEl) {
    titleEl.addEventListener('input', () => {
        // Only auto-fill if user hasn't manually typed a slug (or it's new page)
        const isNew = !slugEl.value || slugEl.dataset.manual !== '1';
        if (isNew) {
            slugEl.value = titleEl.value
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        }
    });
    slugEl.addEventListener('input', () => { slugEl.dataset.manual = '1'; });
}

// ── Live Markdown preview ─────────────────────
const bodyEl  = document.getElementById('f-body');
const prevTitle = document.getElementById('preview-title');
const prevBody  = document.getElementById('preview-body');

function markdownToHtml(md) {
    let h = md
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    // Code blocks
    h = h.replace(/```[\w]*\n?([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
    // Inline code
    h = h.replace(/`([^`]+)`/g, '<code>$1</code>');
    // Headings
    h = h.replace(/^###### (.+)$/gm, '<h6>$1</h6>');
    h = h.replace(/^##### (.+)$/gm, '<h5>$1</h5>');
    h = h.replace(/^#### (.+)$/gm, '<h4>$1</h4>');
    h = h.replace(/^### (.+)$/gm, '<h3>$1</h3>');
    h = h.replace(/^## (.+)$/gm, '<h2>$1</h2>');
    h = h.replace(/^# (.+)$/gm, '<h1>$1</h1>');
    // Bold/italic
    h = h.replace(/\*\*\*(.+?)\*\*\*/gs, '<strong><em>$1</em></strong>');
    h = h.replace(/\*\*(.+?)\*\*/gs, '<strong>$1</strong>');
    h = h.replace(/\*(.+?)\*/gs, '<em>$1</em>');
    // Blockquotes
    h = h.replace(/^&gt; (.+)$/gm, '<blockquote>$1</blockquote>');
    // HR
    h = h.replace(/^[-*]{3,}$/gm, '<hr>');
    // Lists
    h = h.replace(/(^[*\-] .+(\n[*\-] .+)*)/gm, m => {
        const items = m.replace(/^[*\-] (.+)$/gm, '<li>$1</li>');
        return `<ul>${items}</ul>`;
    });
    h = h.replace(/(^\d+\. .+(\n\d+\. .+)*)/gm, m => {
        const items = m.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');
        return `<ol>${items}</ol>`;
    });
    // Links
    h = h.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');
    // Paragraphs
    const blocks = h.split(/\n{2,}/);
    return blocks.map(b => {
        b = b.trim();
        if (!b) return '';
        if (/^<(h[1-6]|ul|ol|blockquote|pre|hr)/.test(b)) return b;
        return '<p>' + b.replace(/\n/g, '<br>') + '</p>';
    }).join('\n');
}

if (bodyEl && prevBody) {
    function updatePreview() {
        prevBody.innerHTML = markdownToHtml(bodyEl.value) || '<em style="color:var(--ink-faint)">Start typing…</em>';
    }
    bodyEl.addEventListener('input', updatePreview);

    if (titleEl && prevTitle) {
        titleEl.addEventListener('input', () => {
            prevTitle.textContent = titleEl.value || 'Untitled';
        });
    }
    updatePreview();
}

// ── Tab key in textarea ───────────────────────
if (bodyEl) {
    bodyEl.addEventListener('keydown', e => {
        if (e.key === 'Tab') {
            e.preventDefault();
            const start = bodyEl.selectionStart;
            const end   = bodyEl.selectionEnd;
            bodyEl.value = bodyEl.value.substring(0, start) + '    ' + bodyEl.value.substring(end);
            bodyEl.selectionStart = bodyEl.selectionEnd = start + 4;
        }
    });
}
</script>
</body>
</html>
<?php

// ─────────────────────────────────────────────
//  Login page renderer
// ─────────────────────────────────────────────
function render_login_page(string $error = ''): void {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login — <?= h(SITE_TITLE) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #1a1612;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .card {
            background: #faf8f4;
            border-radius: 12px;
            padding: 2.5rem;
            width: 100%; max-width: 380px;
            box-shadow: 0 25px 60px rgba(0,0,0,.4);
        }
        .card-brand {
            font-family: 'DM Serif Display', serif;
            font-size: 1.75rem; text-align: center; margin-bottom: .25rem;
            color: #1a1612;
        }
        .card-brand span { color: #c84b2f; }
        .card-sub {
            text-align: center; color: #9e978f; font-size: .82rem;
            margin-bottom: 2rem;
        }
        .alert {
            padding: .65rem .9rem; border-radius: 6px;
            background: #f8d7da; color: #721c24;
            border: 1px solid #f5c6cb;
            font-size: .85rem; margin-bottom: 1.25rem;
        }
        label {
            font-size: .72rem; text-transform: uppercase; letter-spacing: .08em;
            font-weight: 600; color: #9e978f; display: block; margin-bottom: .4rem;
        }
        input[type=password] {
            width: 100%; padding: .65rem .9rem;
            border: 1px solid #e0d9cf; border-radius: 6px;
            font-family: 'DM Sans', sans-serif; font-size: .95rem;
            background: #fff; outline: none; transition: border-color .15s;
            margin-bottom: 1.25rem;
        }
        input[type=password]:focus { border-color: #c84b2f; }
        button {
            width: 100%; background: #c84b2f; color: #fff;
            border: none; border-radius: 6px; padding: .75rem;
            font-family: 'DM Sans', sans-serif; font-size: 1rem;
            font-weight: 500; cursor: pointer; transition: background .15s;
        }
        button:hover { background: #a83c22; }
        .card-hint { text-align: center; margin-top: 1.25rem; font-size: .75rem; color: #9e978f; }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="card-brand"><?= h(SITE_TITLE) ?><span>.</span></h1>
        <p class="card-sub">Admin Panel</p>
        <?php if ($error): ?>
            <div class="alert"><?= h($error) ?></div>
        <?php endif ?>
        <form method="POST" action="admin.php?action=login">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label for="pw">Password</label>
            <input type="password" id="pw" name="password" autofocus required>
            <button type="submit">Sign In</button>
        </form>
        <p class="card-hint">Default password: <strong>admin123</strong></p>
    </div>
</body>
</html>
<?php
}
