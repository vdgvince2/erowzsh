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
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    return trim($path, '/');
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

// ── Debug (token protégé) ─────────────────────────────────────────────────────
if (isset($_GET['_rdbg']) && $_GET['_rdbg'] === (getenv('RECOVER_API_TOKEN') ?: 'off')) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "rawHost      : $rawHost\n";
    echo "currentDomain: $currentDomain\n";
    echo "countryCode  : $countryCode\n";
    echo "nicheSlug    : $nicheSlug\n";
    echo "recSiteId    : $recSiteId\n";
    echo "recNicheId   : $recNicheId\n\n";

    $_sitesDbg = json_decode(file_get_contents(__DIR__ . '/../sites.json'), true);
    echo "json_last_error: " . json_last_error_msg() . "\n";
    echo "sites.json key 'bolsoverantiquescentre.co.uk': ";
    var_export($_sitesDbg['bolsoverantiquescentre.co.uk'] ?? 'KEY MISSING');
    echo "\n";
    echo "sites.json key 'GB': ";
    var_export($_sitesDbg['GB'] ?? 'KEY MISSING');
    echo "\n";
    exit;
}

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

    // Charge la niche liée pour afficher la homepage normale + section recovered
    $niche           = null;
    $articles        = [];
    $subNiches       = [];
    $homepageContent = null;
    $eeatProfiles    = [];
    $galleryImages   = [];

    if ($recNicheId) {
        $stmtNiche = $pdo->prepare('SELECT * FROM niches WHERE id = :id LIMIT 1');
        $stmtNiche->execute([':id' => $recNicheId]);
        $niche = $stmtNiche->fetch() ?: null;
    }

    if ($niche) {
        // Aligne la langue sur celle du site récupéré
        $mainLanguage = strtoupper($recLang);

        $articles  = cs_get_articles_for_niche($pdo, $niche['slug'], $currentDomain);
        $subNiches = cs_get_subniche_nav($pdo, $niche['slug']);

        $stmtHpc = $pdo->prepare('SELECT * FROM niche_homepage_content WHERE niche_id = :nid AND domain = :dom LIMIT 1');
        $stmtHpc->execute([':nid' => $niche['id'], ':dom' => $currentDomain]);
        $homepageContent = $stmtHpc->fetch() ?: null;

        $stmtEeat = $pdo->prepare('
            SELECT ep.expert_name, ep.social_link,
                   ep.bio_en, ep.bio_fr, ep.bio_de, ep.bio_it,
                   sn.name AS sub_niche_name, sn.slug AS sub_niche_slug
            FROM eeat_profiles ep
            JOIN sub_niches sn ON sn.id = ep.sub_niche_id
            JOIN niches n ON n.id = sn.niche_id
            WHERE n.slug = :slug
            ORDER BY sn.sort_order ASC, sn.name ASC
        ');
        $stmtEeat->execute([':slug' => $niche['slug']]);
        $eeatProfiles = $stmtEeat->fetchAll();

        $stmtGallery = $pdo->prepare('
            SELECT ap.image_url, sn.slug AS sub_niche_slug, sn.name AS sub_niche_name
            FROM article_products ap
            JOIN articles a ON a.id = ap.article_id
            JOIN sub_niches sn ON sn.id = a.sub_niche_id
            JOIN niches n ON n.id = sn.niche_id
            WHERE n.slug = :slug
              AND a.domain = :domain
              AND a.status = "published"
              AND ap.image_url IS NOT NULL
              AND ap.image_url != ""
            ORDER BY RAND()
            LIMIT 28
        ');
        $stmtGallery->execute([':slug' => $niche['slug'], ':domain' => $currentDomain]);
        $galleryImages = $stmtGallery->fetchAll();
        $galleryImages = array_map(function (array $row): array {
            $row['image_url'] = preg_replace('/s-l\d+/', 's-l500', $row['image_url']);
            return $row;
        }, $galleryImages);

        require __DIR__ . '/../templates/homepage.php';
        exit;
    }

    // Fallback si aucune niche liée : ancienne homepage recovered
    $recNicheArticles = [];
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

// ── 5. Sous-niche du site lié ─────────────────────────────────────────────────
// Si le slug correspond à une sous-niche de la niche liée, on sert sa homepage.

if ($recNicheId) {
    $stmtSN = $pdo->prepare('
        SELECT sn.*, n.slug AS niche_slug FROM sub_niches sn
        JOIN niches n ON n.id = sn.niche_id
        WHERE sn.niche_id = ? AND sn.slug = ? LIMIT 1
    ');
    $stmtSN->execute([$recNicheId, $requestedSlug]);
    $subNiche = $stmtSN->fetch();

    if ($subNiche) {
        // Charge les articles + nav pour la homepage sous-niche
        $nicheSlug    = $subNiche['niche_slug'];
        $subNicheSlug = $subNiche['slug'];
        $articleSlug  = null;
        $stmtNiche    = $pdo->prepare('SELECT * FROM niches WHERE id = ? LIMIT 1');
        $stmtNiche->execute([$recNicheId]);
        $niche        = $stmtNiche->fetch() ?: null;
        $snArticles   = cs_get_articles_for_subniche($pdo, (int)$subNiche['id'], $currentDomain);
        $subNiches    = cs_get_subniche_nav($pdo, $nicheSlug);
        require __DIR__ . '/../templates/subniche-homepage.php';
        exit;
    }

    // Tente aussi le niveau article : slug = subniche-article ou chemin à 2 segments
    // ex: /antique-furniture/guide-buying-victorian-chairs
    $rawUri      = trim($_SERVER['REQUEST_URI'] ?? '/', '/');
    $uriSegments = array_values(array_filter(explode('/', parse_url('/' . $rawUri, PHP_URL_PATH) ?? '/')));
    if (count($uriSegments) === 2) {
        [$snSlug, $artSlug] = $uriSegments;
        $stmtSN2 = $pdo->prepare('
            SELECT sn.*, n.slug AS niche_slug FROM sub_niches sn
            JOIN niches n ON n.id = sn.niche_id
            WHERE sn.niche_id = ? AND sn.slug = ? LIMIT 1
        ');
        $stmtSN2->execute([$recNicheId, $snSlug]);
        $subNiche2 = $stmtSN2->fetch();

        if ($subNiche2) {
            $nicheSlug    = $subNiche2['niche_slug'];
            $subNicheSlug = $subNiche2['slug'];
            $articleSlug  = $artSlug;
            $stmtNiche2   = $pdo->prepare('SELECT * FROM niches WHERE id = ? LIMIT 1');
            $stmtNiche2->execute([$recNicheId]);
            $niche        = $stmtNiche2->fetch() ?: null;
            $article      = cs_get_article_by_slug($pdo, $nicheSlug, $subNicheSlug, $currentDomain, $articleSlug);
            if ($article) {
                $products        = cs_get_article_products($pdo, $article['id']);
                $relatedArticles = cs_get_articles_for_subniche($pdo, (int)$subNiche2['id'], $currentDomain, 6, $article['id']);
                $subNiches       = cs_get_subniche_nav($pdo, $nicheSlug);
                $subNiche        = $subNiche2;
                $pageUrl         = $SERVER_PageFullURL ?? $recBaseUrl;
                $marketPulse     = null;
                require __DIR__ . '/../templates/article.php';
                exit;
            }
        }
    }
}

// ── 6. Fallback → 301 homepage ────────────────────────────────────────────────

header('Location: ' . $recBaseUrl, true, 301);
exit;
