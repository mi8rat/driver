<?php
// ─────────────────────────────────────────────
//  FlatCMS – Helper Functions
// ─────────────────────────────────────────────

require_once __DIR__ . '/config.php';

// ── Session ──────────────────────────────────
function session_start_once(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function is_logged_in(): bool {
    session_start_once();
    return !empty($_SESSION['flatcms_auth']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: admin.php?action=login');
        exit;
    }
}

// ── Slug ─────────────────────────────────────
function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ── File helpers ──────────────────────────────
function content_path(string $slug): string {
    return CONTENT_DIR . $slug . '.md';
}

function get_all_pages(): array {
    if (!is_dir(CONTENT_DIR)) return [];
    $files = glob(CONTENT_DIR . '*.md');
    $pages = [];
    foreach ($files as $file) {
        $slug = basename($file, '.md');
        $meta = parse_page_file($file);
        $pages[] = array_merge($meta, ['slug' => $slug]);
    }
    usort($pages, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return $pages;
}

function get_page(string $slug): ?array {
    $path = content_path($slug);
    if (!file_exists($path)) return null;
    $data = parse_page_file($path);
    $data['slug'] = $slug;
    return $data;
}

// ── Front Matter Parser ───────────────────────
function parse_page_file(string $path): array {
    $raw = file_get_contents($path);
    $meta  = ['title' => 'Untitled', 'date' => '', 'body' => ''];

    if (str_starts_with($raw, '---')) {
        $parts = preg_split('/^---\s*$/m', $raw, 3);
        if (count($parts) >= 3) {
            $front = trim($parts[1]);
            $body  = trim($parts[2]);
            foreach (explode("\n", $front) as $line) {
                if (str_contains($line, ':')) {
                    [$key, $val] = explode(':', $line, 2);
                    $meta[trim($key)] = trim($val);
                }
            }
            $meta['body'] = $body;
            return $meta;
        }
    }
    $meta['body'] = $raw;
    return $meta;
}

function save_page(string $slug, string $title, string $body, string $date = ''): bool {
    if (!is_dir(CONTENT_DIR)) mkdir(CONTENT_DIR, 0755, true);
    if (!$date) $date = date('Y-m-d');
    $content = "---\ntitle: {$title}\ndate: {$date}\n---\n\n{$body}";
    return file_put_contents(content_path($slug), $content) !== false;
}

function delete_page(string $slug): bool {
    $path = content_path($slug);
    if (file_exists($path)) return unlink($path);
    return false;
}

// ── Minimal Markdown → HTML ───────────────────
function markdown_to_html(string $md): string {
    $html = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');

    // Headings
    $html = preg_replace('/^#{6} (.+)$/m', '<h6>$1</h6>', $html);
    $html = preg_replace('/^#{5} (.+)$/m', '<h5>$1</h5>', $html);
    $html = preg_replace('/^#{4} (.+)$/m', '<h4>$1</h4>', $html);
    $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.+)$/m',  '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.+)$/m',   '<h1>$1</h1>', $html);

    // Bold / italic
    $html = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $html);
    $html = preg_replace('/\*\*(.+?)\*\*/s',      '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.+?)\*/s',           '<em>$1</em>', $html);
    $html = preg_replace('/__(.+?)__/s',           '<strong>$1</strong>', $html);
    $html = preg_replace('/_(.+?)_/s',             '<em>$1</em>', $html);

    // Inline code
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

    // Code blocks
    $html = preg_replace('/```[\w]*\n?(.*?)```/s', '<pre><code>$1</code></pre>', $html);

    // Blockquotes
    $html = preg_replace('/^&gt; (.+)$/m', '<blockquote>$1</blockquote>', $html);

    // Horizontal rules
    $html = preg_replace('/^[-*]{3,}$/m', '<hr>', $html);

    // Unordered lists
    $html = preg_replace_callback('/^[*\-] .+(\n[*\-] .+)*/m', function($m) {
        $items = preg_replace('/^[*\-] (.+)$/m', '<li>$1</li>', $m[0]);
        return "<ul>{$items}</ul>";
    }, $html);

    // Ordered lists
    $html = preg_replace_callback('/^\d+\. .+(\n\d+\. .+)*/m', function($m) {
        $items = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $m[0]);
        return "<ol>{$items}</ol>";
    }, $html);

    // Links
    $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

    // Images
    $html = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $html);

    // Paragraphs: wrap double-newline-separated blocks not already HTML
    $blocks = preg_split('/\n{2,}/', $html);
    $result = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;
        if (preg_match('/^<(h[1-6]|ul|ol|li|blockquote|pre|hr)/', $block)) {
            $result .= $block . "\n";
        } else {
            $result .= '<p>' . str_replace("\n", '<br>', $block) . "</p>\n";
        }
    }
    return $result;
}

// ── XSS / CSRF helpers ────────────────────────
function csrf_token(): string {
    session_start_once();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    session_start_once();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
