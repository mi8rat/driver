<?php
/**
 * SimpleCMS — Flat-file Content Management System
 * Single-file PHP CMS using JSON for storage.
 * No database required.
 */

define('DATA_FILE', __DIR__ . '/posts.json');
define('ADMIN_PASSWORD', 'admin123'); // Change this!
session_start();

// ─── Data Layer ───────────────────────────────────────────────────────────────

function load_posts(): array {
    if (!file_exists(DATA_FILE)) return [];
    $data = json_decode(file_get_contents(DATA_FILE), true);
    return is_array($data) ? $data : [];
}

function save_posts(array $posts): void {
    file_put_contents(DATA_FILE, json_encode(array_values($posts), JSON_PRETTY_PRINT));
}

function get_post(string $id): ?array {
    foreach (load_posts() as $post) {
        if ($post['id'] === $id) return $post;
    }
    return null;
}

function create_post(string $title, string $body, string $status): array {
    $posts = load_posts();
    $post = [
        'id'        => uniqid('post_', true),
        'title'     => $title,
        'body'      => $body,
        'status'    => $status,
        'slug'      => make_slug($title),
        'created_at'=> date('Y-m-d H:i:s'),
        'updated_at'=> date('Y-m-d H:i:s'),
    ];
    $posts[] = $post;
    save_posts($posts);
    return $post;
}

function update_post(string $id, string $title, string $body, string $status): bool {
    $posts = load_posts();
    foreach ($posts as &$post) {
        if ($post['id'] === $id) {
            $post['title']      = $title;
            $post['body']       = $body;
            $post['status']     = $status;
            $post['slug']       = make_slug($title);
            $post['updated_at'] = date('Y-m-d H:i:s');
            save_posts($posts);
            return true;
        }
    }
    return false;
}

function delete_post(string $id): bool {
    $posts = load_posts();
    $filtered = array_filter($posts, fn($p) => $p['id'] !== $id);
    if (count($filtered) === count($posts)) return false;
    save_posts($filtered);
    return true;
}

function make_slug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

// ─── Auth ─────────────────────────────────────────────────────────────────────

function is_logged_in(): bool {
    return !empty($_SESSION['cms_admin']);
}

function login(string $password): bool {
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['cms_admin'] = true;
        return true;
    }
    return false;
}

function logout(): void {
    unset($_SESSION['cms_admin']);
}

// ─── Router ───────────────────────────────────────────────────────────────────

$action = $_GET['action'] ?? 'home';
$id     = $_GET['id'] ?? null;
$flash  = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['_action'] ?? '';

    if ($post_action === 'login') {
        if (login($_POST['password'] ?? '')) {
            flash('Welcome back!');
            redirect('?action=admin');
        } else {
            flash('Wrong password.', 'error');
            redirect('?action=login');
        }
    }

    if ($post_action === 'logout') {
        logout();
        flash('Logged out.');
        redirect('?action=home');
    }

    if (!is_logged_in()) redirect('?action=login');

    if ($post_action === 'create') {
        $title  = trim($_POST['title'] ?? '');
        $body   = trim($_POST['body'] ?? '');
        $status = $_POST['status'] === 'published' ? 'published' : 'draft';
        if ($title && $body) {
            create_post($title, $body, $status);
            flash('Post created!');
        } else {
            flash('Title and body are required.', 'error');
        }
        redirect('?action=admin');
    }

    if ($post_action === 'update' && $id) {
        $title  = trim($_POST['title'] ?? '');
        $body   = trim($_POST['body'] ?? '');
        $status = $_POST['status'] === 'published' ? 'published' : 'draft';
        if ($title && $body) {
            update_post($id, $title, $body, $status);
            flash('Post updated!');
        } else {
            flash('Title and body are required.', 'error');
        }
        redirect('?action=admin');
    }

    if ($post_action === 'delete' && $id) {
        delete_post($id);
        flash('Post deleted.');
        redirect('?action=admin');
    }
}

// ─── HTML Helpers ─────────────────────────────────────────────────────────────

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function render_flash(?array $flash): string {
    if (!$flash) return '';
    $color = $flash['type'] === 'error' ? '#ff4d4d' : '#00c896';
    return '<div class="flash" style="background:'.$color.'">' . h($flash['msg']) . '</div>';
}

// ─── Views ────────────────────────────────────────────────────────────────────

ob_start();

switch ($action) {

    // ── Public: Home ──────────────────────────────────────────────────────────
    case 'home':
    default:
        $posts = array_filter(load_posts(), fn($p) => $p['status'] === 'published');
        usort($posts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        ?>
        <h1 class="site-title">SimpleCMS</h1>
        <p class="site-tagline">A flat-file content management system</p>
        <section class="post-list">
        <?php if (empty($posts)): ?>
            <div class="empty-state">No published posts yet. <a href="?action=login">Admin →</a></div>
        <?php else: ?>
            <?php foreach ($posts as $p): ?>
            <article class="card">
                <div class="card-meta"><?= h(date('M j, Y', strtotime($p['created_at']))) ?></div>
                <h2 class="card-title"><a href="?action=view&id=<?= h($p['id']) ?>"><?= h($p['title']) ?></a></h2>
                <p class="card-excerpt"><?= h(substr(strip_tags($p['body']), 0, 180)) ?>…</p>
                <a href="?action=view&id=<?= h($p['id']) ?>" class="read-more">Read more →</a>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
        </section>
        <?php
        break;

    // ── Public: View post ─────────────────────────────────────────────────────
    case 'view':
        $post = $id ? get_post($id) : null;
        if (!$post || $post['status'] !== 'published') {
            echo '<div class="error-page"><h2>Post not found.</h2><a href="?action=home">← Back</a></div>';
            break;
        }
        ?>
        <a href="?action=home" class="back-link">← All posts</a>
        <article class="single-post">
            <div class="card-meta"><?= h(date('F j, Y', strtotime($post['created_at']))) ?> &mdash; <span class="badge published">published</span></div>
            <h1><?= h($post['title']) ?></h1>
            <div class="post-body"><?= nl2br(h($post['body'])) ?></div>
        </article>
        <?php
        break;

    // ── Auth: Login ───────────────────────────────────────────────────────────
    case 'login':
        if (is_logged_in()) redirect('?action=admin');
        ?>
        <div class="auth-wrap">
            <?= render_flash($flash) ?>
            <h1>Admin Login</h1>
            <form method="post" class="auth-form">
                <input type="hidden" name="_action" value="login">
                <label>Password</label>
                <input type="password" name="password" autofocus required>
                <button type="submit">Sign in →</button>
            </form>
            <p class="hint">Default password: <code>admin123</code></p>
        </div>
        <?php
        break;

    // ── Admin: Dashboard ──────────────────────────────────────────────────────
    case 'admin':
        if (!is_logged_in()) redirect('?action=login');
        $posts = load_posts();
        usort($posts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        ?>
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <div class="admin-actions">
                <a href="?action=home" class="btn btn-ghost">View Site</a>
                <a href="?action=new" class="btn btn-primary">+ New Post</a>
                <form method="post" style="display:inline">
                    <input type="hidden" name="_action" value="logout">
                    <button class="btn btn-ghost">Logout</button>
                </form>
            </div>
        </div>
        <?= render_flash($flash) ?>
        <div class="stats-bar">
            <div class="stat"><span><?= count($posts) ?></span>Total Posts</div>
            <div class="stat"><span><?= count(array_filter($posts, fn($p) => $p['status'] === 'published')) ?></span>Published</div>
            <div class="stat"><span><?= count(array_filter($posts, fn($p) => $p['status'] === 'draft')) ?></span>Drafts</div>
        </div>
        <table class="posts-table">
            <thead>
                <tr><th>Title</th><th>Status</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($posts)): ?>
                <tr><td colspan="4" class="empty-td">No posts yet. <a href="?action=new">Create one →</a></td></tr>
            <?php else: ?>
                <?php foreach ($posts as $p): ?>
                <tr>
                    <td><strong><?= h($p['title']) ?></strong><br><small class="slug">/<?= h($p['slug']) ?></small></td>
                    <td><span class="badge <?= $p['status'] ?>"><?= $p['status'] ?></span></td>
                    <td><?= h(date('M j, Y', strtotime($p['created_at']))) ?></td>
                    <td class="row-actions">
                        <?php if ($p['status'] === 'published'): ?>
                        <a href="?action=view&id=<?= h($p['id']) ?>" class="btn btn-xs btn-ghost">View</a>
                        <?php endif; ?>
                        <a href="?action=edit&id=<?= h($p['id']) ?>" class="btn btn-xs btn-primary">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this post?')" style="display:inline">
                            <input type="hidden" name="_action" value="delete">
                            <button class="btn btn-xs btn-danger" formaction="?action=admin&id=<?= h($p['id']) ?>">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
        break;

    // ── Admin: New Post ───────────────────────────────────────────────────────
    case 'new':
        if (!is_logged_in()) redirect('?action=login');
        ?>
        <div class="form-header">
            <a href="?action=admin" class="back-link">← Dashboard</a>
            <h1>New Post</h1>
        </div>
        <?= render_flash($flash) ?>
        <form method="post" class="post-form">
            <input type="hidden" name="_action" value="create">
            <label>Title</label>
            <input type="text" name="title" placeholder="Post title…" required autofocus>
            <label>Body</label>
            <textarea name="body" rows="14" placeholder="Write your content here…" required></textarea>
            <div class="form-footer">
                <select name="status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
                <button type="submit" class="btn btn-primary">Create Post</button>
            </div>
        </form>
        <?php
        break;

    // ── Admin: Edit Post ──────────────────────────────────────────────────────
    case 'edit':
        if (!is_logged_in()) redirect('?action=login');
        $post = $id ? get_post($id) : null;
        if (!$post) { flash('Post not found.', 'error'); redirect('?action=admin'); }
        ?>
        <div class="form-header">
            <a href="?action=admin" class="back-link">← Dashboard</a>
            <h1>Edit Post</h1>
        </div>
        <?= render_flash($flash) ?>
        <form method="post" action="?action=admin&id=<?= h($post['id']) ?>" class="post-form">
            <input type="hidden" name="_action" value="update">
            <label>Title</label>
            <input type="text" name="title" value="<?= h($post['title']) ?>" required autofocus>
            <label>Body</label>
            <textarea name="body" rows="14" required><?= h($post['body']) ?></textarea>
            <div class="form-footer">
                <select name="status">
                    <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
        <div class="meta-info">
            <small>Created: <?= h($post['created_at']) ?> &nbsp;|&nbsp; Updated: <?= h($post['updated_at']) ?></small>
        </div>
        <?php
        break;
}

$content = ob_get_clean();

// ─── Layout ───────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SimpleCMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&family=DM+Mono&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:     #0e0f11;
    --surface:#16181c;
    --border: #2a2d35;
    --text:   #e8e9ec;
    --muted:  #6b7080;
    --accent: #f0c040;
    --red:    #ff4d4d;
    --green:  #00c896;
    --radius: 8px;
    --font-display: 'Playfair Display', serif;
    --font-body:    'DM Sans', sans-serif;
    --font-mono:    'DM Mono', monospace;
  }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--font-body);
    font-weight: 300;
    min-height: 100vh;
    line-height: 1.7;
  }

  a { color: var(--accent); text-decoration: none; }
  a:hover { text-decoration: underline; }

  /* Nav */
  nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 2rem;
    border-bottom: 1px solid var(--border);
    background: var(--surface);
    position: sticky; top: 0; z-index: 100;
  }
  .nav-brand {
    font-family: var(--font-display);
    font-size: 1.25rem;
    color: var(--accent);
    font-weight: 700;
    letter-spacing: -0.02em;
  }
  .nav-links a {
    font-size: 0.85rem;
    color: var(--muted);
    margin-left: 1.5rem;
    transition: color .2s;
  }
  .nav-links a:hover { color: var(--text); text-decoration: none; }

  /* Main wrap */
  main {
    max-width: 820px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
  }

  /* Flash */
  .flash {
    padding: .75rem 1.25rem;
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
    font-size: .9rem;
    font-weight: 500;
    color: #fff;
  }

  /* Home */
  .site-title {
    font-family: var(--font-display);
    font-size: 3.5rem;
    font-weight: 900;
    letter-spacing: -0.04em;
    color: var(--accent);
    line-height: 1;
    margin-bottom: .5rem;
  }
  .site-tagline {
    color: var(--muted);
    font-size: 1rem;
    margin-bottom: 3rem;
  }

  /* Cards */
  .post-list { display: flex; flex-direction: column; gap: 1.5rem; }
  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.75rem;
    transition: border-color .2s, transform .2s;
  }
  .card:hover { border-color: var(--accent); transform: translateY(-2px); }
  .card-meta { font-size: .8rem; color: var(--muted); margin-bottom: .5rem; font-family: var(--font-mono); }
  .card-title { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; margin-bottom: .75rem; }
  .card-title a { color: var(--text); }
  .card-title a:hover { color: var(--accent); text-decoration: none; }
  .card-excerpt { color: var(--muted); font-size: .95rem; margin-bottom: 1rem; }
  .read-more { font-size: .85rem; font-weight: 500; color: var(--accent); }

  /* Single post */
  .back-link { font-size: .85rem; color: var(--muted); display: inline-block; margin-bottom: 2rem; }
  .back-link:hover { color: var(--text); text-decoration: none; }
  .single-post h1 { font-family: var(--font-display); font-size: 2.5rem; font-weight: 900; letter-spacing: -0.03em; margin: .75rem 0 1.5rem; }
  .post-body { color: #c8cad0; line-height: 1.9; font-size: 1.05rem; }

  /* Badges */
  .badge {
    display: inline-block;
    padding: .2rem .6rem;
    border-radius: 4px;
    font-size: .75rem;
    font-weight: 500;
    font-family: var(--font-mono);
  }
  .badge.published { background: rgba(0,200,150,.15); color: var(--green); }
  .badge.draft     { background: rgba(107,112,128,.15); color: var(--muted); }

  /* Empty */
  .empty-state { color: var(--muted); padding: 3rem; text-align: center; border: 1px dashed var(--border); border-radius: var(--radius); }
  .error-page { text-align: center; padding: 4rem 0; }
  .error-page h2 { font-family: var(--font-display); font-size: 2rem; color: var(--red); margin-bottom: 1rem; }

  /* Auth */
  .auth-wrap { max-width: 380px; margin: 0 auto; }
  .auth-wrap h1 { font-family: var(--font-display); font-size: 2rem; margin-bottom: 2rem; }
  .auth-form label { display: block; font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; margin-bottom: .4rem; }
  .auth-form input {
    width: 100%; padding: .75rem 1rem;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); color: var(--text); font-family: var(--font-body);
    font-size: 1rem; margin-bottom: 1.25rem; outline: none;
    transition: border-color .2s;
  }
  .auth-form input:focus { border-color: var(--accent); }
  .hint { font-size: .8rem; color: var(--muted); margin-top: 1rem; }
  .hint code { background: var(--surface); padding: .1em .4em; border-radius: 3px; font-family: var(--font-mono); }

  /* Admin */
  .admin-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
  .admin-header h1 { font-family: var(--font-display); font-size: 2rem; }
  .admin-actions { display: flex; gap: .75rem; align-items: center; }

  .stats-bar { display: flex; gap: 1rem; margin-bottom: 2rem; }
  .stat {
    flex: 1; background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 1rem 1.25rem; text-align: center;
  }
  .stat span { display: block; font-family: var(--font-display); font-size: 2rem; font-weight: 700; color: var(--accent); }
  .stat { font-size: .8rem; color: var(--muted); }

  /* Table */
  .posts-table { width: 100%; border-collapse: collapse; }
  .posts-table th {
    text-align: left; font-size: .75rem; text-transform: uppercase;
    letter-spacing: .08em; color: var(--muted); padding: .75rem 1rem;
    border-bottom: 1px solid var(--border);
  }
  .posts-table td { padding: 1rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
  .posts-table tr:hover td { background: var(--surface); }
  .slug { font-family: var(--font-mono); font-size: .75rem; color: var(--muted); }
  .empty-td { text-align: center; color: var(--muted); padding: 2.5rem; }
  .row-actions { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }

  /* Buttons */
  .btn {
    display: inline-flex; align-items: center;
    padding: .55rem 1.1rem; border-radius: var(--radius);
    font-family: var(--font-body); font-size: .875rem; font-weight: 500;
    cursor: pointer; border: 1px solid transparent;
    transition: all .15s; text-decoration: none;
  }
  .btn:hover { text-decoration: none; }
  .btn-primary  { background: var(--accent); color: #000; }
  .btn-primary:hover { background: #f7d060; }
  .btn-ghost    { background: transparent; border-color: var(--border); color: var(--text); }
  .btn-ghost:hover { border-color: var(--accent); color: var(--accent); }
  .btn-danger   { background: transparent; border-color: transparent; color: var(--red); }
  .btn-danger:hover { background: rgba(255,77,77,.1); }
  .btn-xs       { padding: .3rem .7rem; font-size: .8rem; }

  /* Post form */
  .form-header { margin-bottom: 2rem; }
  .form-header h1 { font-family: var(--font-display); font-size: 2rem; margin-top: .5rem; }
  .post-form label { display: block; font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; margin-bottom: .4rem; }
  .post-form input, .post-form textarea, .post-form select {
    width: 100%; padding: .75rem 1rem;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); color: var(--text); font-family: var(--font-body);
    font-size: 1rem; margin-bottom: 1.25rem; outline: none;
    transition: border-color .2s;
  }
  .post-form input:focus,
  .post-form textarea:focus { border-color: var(--accent); }
  .post-form textarea { resize: vertical; line-height: 1.7; }
  .post-form select { cursor: pointer; }
  .form-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
  .form-footer select { width: auto; margin-bottom: 0; }
  .meta-info { margin-top: 1.5rem; color: var(--muted); font-size: .8rem; font-family: var(--font-mono); }

  @media (max-width: 600px) {
    .site-title { font-size: 2.5rem; }
    .stats-bar { flex-direction: column; }
    .admin-header { flex-direction: column; align-items: flex-start; }
    .posts-table thead { display: none; }
    .posts-table td { display: block; border: none; padding: .5rem 1rem; }
    .posts-table tr { border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 1rem; display: block; }
  }
</style>
</head>
<body>
<nav>
  <span class="nav-brand">SimpleCMS</span>
  <div class="nav-links">
    <a href="?action=home">Blog</a>
    <?php if (is_logged_in()): ?>
      <a href="?action=admin">Admin</a>
      <a href="?action=new">+ New</a>
    <?php else: ?>
      <a href="?action=login">Login</a>
    <?php endif; ?>
  </div>
</nav>
<main><?= $content ?></main>
</body>
</html>
