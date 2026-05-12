<?php
/**
 * Recovered Sites — Routeur prioritaire
 *
 * Inclus depuis index.php dès qu'un domaine matche recovered_sites.
 * $recoveredSite, $pdo, $rawHost, $portStr, $SERVER_Protocol,
 * $isLocal, $base, $mainLanguage sont déjà définis.
 *
 * Priorités :
 *   1. /sitemap-html[/page/N]  → sitemap HTML paginé
 *   2. slug exact              → render article
 *   3. old_path               → 301 vers slug
 *   4. /                      → homepage recovered
 *   5. tout le reste           → 301 homepage
 */

declare(strict_types=1);

// ── Helpers ──────────────────────────────────────────────────────────────────

function rec_base_url(array $site, string $protocol, string $portStr, bool $isLocal, string $base): string
{
    if ($isLocal) {
        return $protocol . '://' . $site['domain'] . $portStr . $base;
    }
    return $protocol . '://' . $site['domain'] . '/';
}

function rec_page_url(array $site, string $slug, string $protocol, string $portStr, bool $isLocal, string $base): string
{
    $b = rec_base_url($site, $protocol, $portStr, $isLocal, $base);
    if ($isLocal) return $b . '?rslug=' . urlencode($slug);
    return $b . $slug;
}

function rec_sitemap_url(array $site, int $page, string $protocol, string $portStr, bool $isLocal, string $base): string
{
    $b = rec_base_url($site, $protocol, $portStr, $isLocal, $base);
    if ($isLocal) return $b . '?rpage=sitemap-html&p=' . $page;
    $suffix = $page > 1 ? 'sitemap-html/page/' . $page : 'sitemap-html';
    return $b . $suffix;
}

function rec_sitemap_xml_url(array $site, string $protocol, string $portStr, bool $isLocal, string $base): string
{
    $b = rec_base_url($site, $protocol, $portStr, $isLocal, $base);
    if ($isLocal) return $b . '?rpage=sitemap-recovered.xml';
    return $b . 'sitemap-recovered.xml';
}

/**
 * Extrait le slug demandé depuis l'URI/GET selon le contexte.
 */
function rec_requested_slug(bool $isLocal): string
{
    if ($isLocal) {
        return trim($_GET['rslug'] ?? '', '/');
    }
    return trim($_SERVER['REQUEST_URI'] ?? '/', '/');
}

/**
 * Détecte si la requête cible le sitemap HTML.
 * Retourne le numéro de page (1+) ou 0 si ce n'est pas le sitemap.
 */
function rec_is_sitemap_request(bool $isLocal): int
{
    if ($isLocal) {
        if (($_GET['rpage'] ?? '') === 'sitemap-html') {
            return max(1, (int)($_GET['p'] ?? 1));
        }
        return 0;
    }
    $uri = trim($_SERVER['REQUEST_URI'] ?? '/', '/');
    if ($uri === 'sitemap-html') return 1;
    if (preg_match('#^sitemap-html/page/(\d+)$#', $uri, $m)) return (int)$m[1];
    return 0;
}

// ── Init variables ────────────────────────────────────────────────────────────

$recBaseUrl  = rec_base_url($recoveredSite, $SERVER_Protocol, $portStr, $isLocal, $base);
$recSiteId   = (int)$recoveredSite['id'];
$recLang     = $recoveredSite['language'];
$recNicheId  = $recoveredSite['niche_id'] ? (int)$recoveredSite['niche_id'] : null;

// ── 1a. Sitemap XML ───────────────────────────────────────────────────────────

$requestedUriRaw = trim($_SERVER['REQUEST_URI'] ?? '/', '/');
$isSitemapXml = (!$isLocal && $requestedUriRaw === 'sitemap-recovered.xml')
             || ($isLocal && ($_GET['rpage'] ?? '') === 'sitemap-recovered.xml');

if ($isSitemapXml) {
    require __DIR__ . '/../sitemap-recovered.php';
    exit;
}

// ── 1b. Sitemap HTML ──────────────────────────────────────────────────────────

$sitemapPage = rec_is_sitemap_request($isLocal);
if ($sitemapPage > 0) {
    $perPage  = 50;
    $offset   = ($sitemapPage - 1) * $perPage;

    $total = (int)$pdo->prepare('SELECT COUNT(*) FROM recovered_pages WHERE site_id = ? AND status = "generated"')
                      ->execute([$recSiteId]) ? $pdo->query("SELECT COUNT(*) FROM recovered_pages WHERE site_id = $recSiteId AND status = 'generated'")->fetchColumn() : 0;

    // Recount properly
    $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM recovered_pages WHERE site_id = ? AND status = "generated"');
    $stmtCount->execute([$recSiteId]);
    $total = (int)$stmtCount->fetchColumn();

    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($sitemapPage > $totalPages) { http_response_code(404); exit; }

    $stmtPages = $pdo->prepare('
        SELECT slug, title FROM recovered_pages
        WHERE site_id = ? AND status = "generated"
        ORDER BY id ASC
        LIMIT ? OFFSET ?
    ');
    $stmtPages->execute([$recSiteId, $perPage, $offset]);
    $sitemapItems = $stmtPages->fetchAll();

    $recSitemapBaseUrl = rec_sitemap_url($recoveredSite, 1, $SERVER_Protocol, $portStr, $isLocal, $base);
    require __DIR__ . '/../templates/recovered-sitemap.php';
    exit;
}

// ── Slug demandé ──────────────────────────────────────────────────────────────

$requestedSlug = rec_requested_slug($isLocal);

// ── 2. Homepage ───────────────────────────────────────────────────────────────

if ($requestedSlug === '') {
    $stmtTop = $pdo->prepare('
        SELECT slug, title FROM recovered_pages
        WHERE site_id = ? AND status = "generated"
        ORDER BY id ASC LIMIT 10
    ');
    $stmtTop->execute([$recSiteId]);
    $recTopPages = $stmtTop->fetchAll();

    // Articles content-sites liés à la niche (pour la sidebar/footer)
    $recNicheArticles = [];
    if ($recNicheId) {
        $stmtNA = $pdo->prepare('
            SELECT a.title, a.slug, sn.slug AS sub_niche_slug
            FROM articles a
            JOIN sub_niches sn ON sn.id = a.sub_niche_id
            WHERE sn.niche_id = ? AND a.language = ? AND a.status = "published"
            ORDER BY a.published_at DESC LIMIT 6
        ');
        $stmtNA->execute([$recNicheId, $recLang]);
        $recNicheArticles = $stmtNA->fetchAll();
    }

    require __DIR__ . '/../templates/recovered-homepage.php';
    exit;
}

// ── 3. Slug exact → render article ───────────────────────────────────────────

$stmtBySlug = $pdo->prepare('
    SELECT * FROM recovered_pages
    WHERE site_id = ? AND slug = ? LIMIT 1
');
$stmtBySlug->execute([$recSiteId, $requestedSlug]);
$recPage = $stmtBySlug->fetch();

if ($recPage && $recPage['status'] === 'generated') {
    // Maillage : 3 autres pages du même site récupéré
    $stmtRel = $pdo->prepare('
        SELECT slug, title FROM recovered_pages
        WHERE site_id = ? AND slug != ? AND status = "generated"
        ORDER BY RAND() LIMIT 3
    ');
    $stmtRel->execute([$recSiteId, $requestedSlug]);
    $recRelatedOld = $stmtRel->fetchAll();

    // Maillage : 3 articles content-sites de la même niche
    $recRelatedNew = [];
    if ($recNicheId) {
        $stmtNew = $pdo->prepare('
            SELECT a.title, a.slug, sn.slug AS sub_niche_slug
            FROM articles a
            JOIN sub_niches sn ON sn.id = a.sub_niche_id
            WHERE sn.niche_id = ? AND a.language = ? AND a.status = "published"
            ORDER BY RAND() LIMIT 3
        ');
        $stmtNew->execute([$recNicheId, $recLang]);
        $recRelatedNew = $stmtNew->fetchAll();
    }

    $recPageUrl = rec_page_url($recoveredSite, $recPage['slug'], $SERVER_Protocol, $portStr, $isLocal, $base);
    require __DIR__ . '/../templates/recovered-article.php';
    exit;
}

// Page exists but content pending → redirect homepage
if ($recPage && $recPage['status'] !== 'generated') {
    header('Location: ' . $recBaseUrl, true, 302);
    exit;
}

// ── 4. old_path → 301 vers slug ──────────────────────────────────────────────

// Normalise le chemin demandé pour matching
$normalizedPath = '/' . ltrim($requestedSlug, '/');

$stmtByPath = $pdo->prepare('
    SELECT slug FROM recovered_pages
    WHERE site_id = ? AND original_path = ? AND status = "generated" LIMIT 1
');
$stmtByPath->execute([$recSiteId, $normalizedPath]);
$foundByPath = $stmtByPath->fetch();

if ($foundByPath) {
    $target = rec_page_url($recoveredSite, $foundByPath['slug'], $SERVER_Protocol, $portStr, $isLocal, $base);
    header('Location: ' . $target, true, 301);
    exit;
}

// ── 5. Fallback → 301 homepage ────────────────────────────────────────────────

header('Location: ' . $recBaseUrl, true, 301);
exit;
