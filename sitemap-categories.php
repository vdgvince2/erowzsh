<?php
/**
 * Dynamic XML sitemap — category pages.
 * Accessible as /sitemap-categories.xml via .htaccess rewrite rule.
 */

ob_start();
$countryCode = null; // let routing detect from domain
require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/functions.php';
ob_end_clean();

$today = gmdate('Y-m-d');
$stmt  = $pdo->query("SELECT slug_path FROM categories WHERE slug_path IS NOT NULL AND slug_path <> ''");

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $path = $row['slug_path'];
    $loc  = rtrim($rootDomain . $base, '/') . '/s' . (str_starts_with($path, '/') ? $path : '/' . $path);
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . "\n";
    echo '    <lastmod>' . $today . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>0.6</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>' . "\n";
