<?php
/**
 * Dynamic XML sitemap — static pages only.
 * Accessible as /sitemap-pages.xml via .htaccess rewrite rule.
 */

ob_start();
$countryCode = null; // let routing detect from domain
require __DIR__ . '/inc/config.php';
ob_end_clean();

$today = gmdate('Y-m-d');

$slugs = [
    'cookies', 'help', 'privacy', 'press', 'about', 'home/categoriesindex',
    'registration', 'money-back-guarantee', 'bidding-and-buying-help', 'stores',
    'start-selling', 'learn-to-sell', 'business-sellers', 'seller-centre',
    'developers', 'security-centre', 'site-map', 'official-time',
];

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($slugs as $slug) {
    $loc = rtrim($rootDomain . $base, '/') . '/s/' . ltrim($slug, '/');
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . "\n";
    echo '    <lastmod>' . $today . '</lastmod>' . "\n";
    echo '    <changefreq>monthly</changefreq>' . "\n";
    echo '    <priority>0.4</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>' . "\n";
