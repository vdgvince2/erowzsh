<?php
/**
 * Content-Sites — Point d'entrée unique (3 niveaux)
 *
 *   0. Recovered Sites (priorité absolue)
 *   1. nicheSlug seul          → homepage niche
 *   2. nicheSlug + subNicheSlug → homepage sous-niche
 *   3. + articleSlug           → article
 */

require_once __DIR__ . '/inc/config.php';

// ── Priorité 0 : Recovered Sites ──────────────────────────────────────────────
// Vérifie si le domaine courant est un site récupéré via CommonCrawl.
// Ce bloc doit être exécuté avant toute logique niche/sous-niche.
if (!empty($rawHost)) {
    $stmtRec = $pdo->prepare(
        'SELECT * FROM recovered_sites WHERE domain = :d AND status = "active" LIMIT 1'
    );
    $stmtRec->execute([':d' => $rawHost]);
    $recoveredSite = $stmtRec->fetch() ?: null;

    if ($recoveredSite) {
        require __DIR__ . '/inc/recovered.php';
        exit;
    }
    unset($stmtRec, $recoveredSite);
}
// ─────────────────────────────────────────────────────────────────────────────

if (empty($nicheSlug)) {
    http_response_code(404); die('Niche not found.');
}

// ── Helper URL ───────────────────────────────────────────────────────────────
/**
 * Construit l'URL d'une page (sous-répertoire).
 *
 * Local  : http://antiques.localhost:8888/SH/content-sites/antique-clocks/[guide-slug]
 * Prod   : https://antiques.co.uk/antique-clocks/[guide-slug]
 *
 * @param string $subNicheSlug  Slug de la sous-niche (vide = homepage niche)
 * @param string $articleSlug   Slug de l'article     (vide = homepage sous-niche)
 */
function cs_url(string $subNicheSlug = '', string $articleSlug = ''): string
{
    global $nicheBaseUrl;

    if (!$subNicheSlug) return $nicheBaseUrl;

    $subNicheBase = rtrim($nicheBaseUrl, '/') . '/' . $subNicheSlug . '/';

    if (!$articleSlug) return $subNicheBase;

    return $subNicheBase . $articleSlug;
}

// ── Charge la niche ───────────────────────────────────────────────────────────
$stmtNiche = $pdo->prepare('SELECT * FROM niches WHERE slug = :slug LIMIT 1');
$stmtNiche->execute([':slug' => $nicheSlug]);
$niche = $stmtNiche->fetch();
if (!$niche) { http_response_code(404); die('Unknown niche: ' . htmlspecialchars($nicheSlug)); }

// ── Niveau 1 : Homepage niche ─────────────────────────────────────────────────
if (empty($subNicheSlug)) {
    $recTopPages = [];
    $articles  = cs_get_articles_for_niche($pdo, $nicheSlug, $currentDomain);
    $subNiches = cs_get_subniche_nav($pdo, $nicheSlug);

    // Contenu éditorial homepage (3 zones + images Pexels)
    $stmtHpc = $pdo->prepare('SELECT * FROM niche_homepage_content WHERE niche_id = :nid AND domain = :dom LIMIT 1');
    $stmtHpc->execute([':nid' => $niche['id'], ':dom' => $currentDomain]);
    $homepageContent = $stmtHpc->fetch() ?: null;

    // EEAT : experts compacts pour toutes les sous-niches de la niche
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
    $stmtEeat->execute([':slug' => $nicheSlug]);
    $eeatProfiles = $stmtEeat->fetchAll();

    // Galerie Pinterest : ~28 images produit aléatoires de toutes les sous-niches
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
    $stmtGallery->execute([':slug' => $nicheSlug, ':domain' => $currentDomain]);
    $galleryImages = $stmtGallery->fetchAll();
    // Convertit vers s-l500 (meilleure qualité)
    $galleryImages = array_map(function (array $row): array {
        $row['image_url'] = preg_replace('/s-l\d+/', 's-l500', $row['image_url']);
        return $row;
    }, $galleryImages);

    require __DIR__ . '/templates/homepage.php';
    exit;
}

// ── Charge la sous-niche ──────────────────────────────────────────────────────
$stmtSN = $pdo->prepare('
    SELECT sn.* FROM sub_niches sn
    JOIN niches n ON n.id = sn.niche_id
    WHERE n.slug = :niche AND sn.slug = :sn LIMIT 1
');
$stmtSN->execute([':niche' => $nicheSlug, ':sn' => $subNicheSlug]);
$subNiche = $stmtSN->fetch();
if (!$subNiche) { http_response_code(404); die('Unknown sub-niche: ' . htmlspecialchars($subNicheSlug)); }

// ── Niveau 2 : Homepage sous-niche ───────────────────────────────────────────
if (empty($articleSlug)) {
    $snArticles = cs_get_articles_for_subniche($pdo, (int)$subNiche['id'], $currentDomain);
    $subNiches  = cs_get_subniche_nav($pdo, $nicheSlug);
    require __DIR__ . '/templates/subniche-homepage.php';
    exit;
}

// ── Niveau 3 : Article ────────────────────────────────────────────────────────
$article = cs_get_article_by_slug($pdo, $nicheSlug, $subNicheSlug, $currentDomain, $articleSlug);
if (!$article) {
    require __DIR__ . '/templates/coming-soon.php';
    exit;
}

$products        = cs_get_article_products($pdo, $article['id']);
$relatedArticles = cs_get_articles_for_subniche($pdo, (int)$subNiche['id'], $currentDomain, 6, $article['id']);
$pageUrl         = $SERVER_PageFullURL ?? $nicheBaseUrl;

// Market pulse — signaux marché eBay live (cache 1h)
$marketPulse = null;
if (!empty($subNiche['ebay_query']) && !empty($ebay_marketplace)) {
    $marketPulse = cs_fetch_market_pulse($subNiche['ebay_query'], $ebay_marketplace);
}

require __DIR__ . '/templates/article.php';
