<?php
/**
 * Dynamic XML sitemap index — lists all sitemap files for this domain.
 * Accessible as /sitemap-main.xml via .htaccess rewrite rule.
 *
 * Submit this URL to Google Search Console and Bing Webmaster Tools.
 */

ob_start();
$countryCode = null; // let routing detect from domain
require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/functions.php';
ob_end_clean();

const SITEMAP_PAGE_SIZE = 50000;

$today = gmdate('Y-m-d');
$root  = rtrim($rootDomain . $base, '/');

// Count keywords to determine number of paginated sitemaps needed
$count        = (int)$pdo->query("SELECT COUNT(*) FROM keywords WHERE keywordURL IS NOT NULL AND keywordURL <> ''")->fetchColumn();
$keywordPages = max(1, (int)ceil($count / SITEMAP_PAGE_SIZE));

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages + categories (never exceed 50k, no pagination needed)
foreach ([$root . '/sitemap-pages.xml', $root . '/sitemap-categories.xml'] as $url) {
    echo '  <sitemap>' . "\n";
    echo '    <loc>' . htmlspecialchars($url, ENT_XML1) . '</loc>' . "\n";
    echo '    <lastmod>' . $today . '</lastmod>' . "\n";
    echo '  </sitemap>' . "\n";
}

// Keywords — one entry per page
for ($p = 1; $p <= $keywordPages; $p++) {
    $url = $root . '/sitemap-keywords.xml?page=' . $p;
    echo '  <sitemap>' . "\n";
    echo '    <loc>' . htmlspecialchars($url, ENT_XML1) . '</loc>' . "\n";
    echo '    <lastmod>' . $today . '</lastmod>' . "\n";
    echo '  </sitemap>' . "\n";
}

// Deals
echo '  <sitemap>' . "\n";
echo '    <loc>' . htmlspecialchars($root . '/sitemap-deals.xml', ENT_XML1) . '</loc>' . "\n";
echo '    <lastmod>' . $today . '</lastmod>' . "\n";
echo '  </sitemap>' . "\n";

echo '</sitemapindex>' . "\n";
