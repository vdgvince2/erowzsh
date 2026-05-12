<?php
/**
 * Dynamic XML sitemap — /deals/ pages only.
 *
 * Submit once to:
 *   Google Search Console → https://{domain}/sitemap-deals.xml
 *   Bing Webmaster Tools  → https://{domain}/sitemap-deals.xml
 *
 * Accessible as /sitemap-deals.xml via .htaccess rewrite rule.
 * <lastmod> is derived from the deal cache file mtime (refreshed hourly by the crawler).
 */

ob_start();

$countryCode = null; // let routing detect from domain
require __DIR__ . '/inc/config.php';

ob_end_clean();

$catalog  = json_decode(file_get_contents(__DIR__ . '/assets/JSON/deals_catalog.json'), true) ?? [];
$cacheDir = __DIR__ . '/cache';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex'); // sitemap itself must not be indexed

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($catalog as $catSlug => $catData) {
    foreach ($catData['countries'][$countryCode]['keywords'] ?? [] as $kw) {
        $slug = $kw['slug'];
        $loc  = rtrim($rootDomain . $base, '/') . '/deals/' . $catSlug . '/' . rawurlencode($slug);

        // lastmod = cache file mtime if available, else today
        $cacheKey  = $catSlug . '_' . $slug . '_' . $countryCode . '_best';
        $cacheFile = $cacheDir . '/deals_' . preg_replace('/[^a-z0-9_\-]/i', '_', $cacheKey) . '.json';
        $lastmod   = file_exists($cacheFile) ? date('Y-m-d', filemtime($cacheFile)) : date('Y-m-d');

        echo '  <url>' . "\n";
        echo '    <loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . "\n";
        echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
        echo '    <changefreq>hourly</changefreq>' . "\n";
        echo '    <priority>0.8</priority>' . "\n";
        echo '  </url>' . "\n";
    }
}

echo '</urlset>' . "\n";
