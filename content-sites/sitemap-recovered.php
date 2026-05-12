<?php
/**
 * Sitemap XML — Pages récupérées (Recovered Sites)
 *
 * Génère un sitemap XML pour toutes les recovered_pages générées
 * d'un domaine donné.
 *
 * Usage :
 *   Prod   : https://minderlist.com/sitemap-recovered.xml
 *   Local  : http://minderlist.localhost:8888/SH/content-sites/sitemap-recovered.php
 *
 * Routé via recovered.php si on ajoute le check /sitemap-recovered.xml.
 * Ou appelé directement depuis le vhost du domaine récupéré.
 */

define('CS_CLI', false);

// Config et DB via le système normal
require_once __DIR__ . '/inc/config.php';

// Le domaine courant doit être un recovered site
if (empty($rawHost)) { http_response_code(404); exit; }

$stmt = $pdo->prepare('SELECT * FROM recovered_sites WHERE domain = ? AND status = "active" LIMIT 1');
$stmt->execute([$rawHost]);
$site = $stmt->fetch();
if (!$site) { http_response_code(404); exit; }

$siteId = (int)$site['id'];

// Récupère toutes les pages générées
$stmtPages = $pdo->prepare('
    SELECT slug, updated_at FROM recovered_pages
    WHERE site_id = ? AND status = "generated"
    ORDER BY id ASC
');
$stmtPages->execute([$siteId]);
$pages = $stmtPages->fetchAll();

// URL de base
$baseUrl = $isLocal
    ? ($SERVER_Protocol . '://' . $rawHost . $portStr . $base)
    : ($SERVER_Protocol . '://' . $rawHost . '/');

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <!-- Homepage -->
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?></loc>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
<?php foreach ($pages as $page):
    $loc = $isLocal
        ? $baseUrl . '?rslug=' . urlencode($page['slug'])
        : $baseUrl . $page['slug'];
    $lastmod = $page['updated_at'] ? date('Y-m-d', strtotime($page['updated_at'])) : date('Y-m-d');
?>  <url>
    <loc><?= htmlspecialchars($loc) ?></loc>
    <lastmod><?= $lastmod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
<?php endforeach; ?>
</urlset>
