<?php
/**
 * Dynamic XML sitemap — keyword search pages (paginated).
 * Accessible as /sitemap-keywords.xml?page=N via .htaccess rewrite rule.
 * Max 50 000 URLs per page (Google sitemap limit).
 */

ob_start();
$countryCode = null; // let routing detect from domain
require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/functions.php';
ob_end_clean();

const SITEMAP_PAGE_SIZE = 50000;

$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * SITEMAP_PAGE_SIZE;
$today  = gmdate('Y-m-d');

$stmt = $pdo->prepare("
    SELECT keywordURL AS url
    FROM keywords
    WHERE keywordURL IS NOT NULL AND keywordURL <> ''
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit',  SITEMAP_PAGE_SIZE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,           PDO::PARAM_INT);
$stmt->execute();

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $raw = trim((string)$row['url']);
    if ($raw === '') continue;

    if (preg_match('#^https?://#i', $raw)) {
        $loc = $raw;
    } else {
        $slug = trim($raw, '/');
        if ($slug === '') continue;
        $loc = rtrim($rootDomain . $base, '/') . '/' . $slug;
    }

    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . "\n";
    echo '    <lastmod>' . $today . '</lastmod>' . "\n";
    echo '    <changefreq>daily</changefreq>' . "\n";
    echo '    <priority>0.7</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>' . "\n";
