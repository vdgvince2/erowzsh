#!/usr/bin/env php
<?php
/**
 * Génère un article pour la prochaine sous-niche sans contenu.
 *
 * Usage CLI :
 *   php generate-article.php GB
 *   php generate-article.php FR
 *
 * Le script :
 *  1. Sélectionne la prochaine sous-niche non traitée (ou en erreur)
 *  2. Appelle l'API eBay pour récupérer des produits
 *  3. Appelle Claude Sonnet pour générer le HTML de l'article
 *  4. Insère l'article en DB avec status=published
 *  5. Sauvegarde les produits associés
 */

define('CS_CLI', true);
$currentDomain = $argv[1] ?? 'IE'; // code pays (IE/GB…) ou domaine prod (antiques.ie)

require_once __DIR__ . '/../inc/config.php';

echo "[generate-article] Démarrage — domain: {$currentDomain}, langue: {$mainLanguage}" . PHP_EOL;

// 1. Prochaine sous-niche à traiter
$subNiche = cs_next_pending_subniche($pdo, $currentDomain);
if (!$subNiche) {
    echo "[generate-article] Toutes les sous-niches ont un article pour {$mainLanguage}. Rien à faire." . PHP_EOL;
    exit(0);
}

echo "[generate-article] Sous-niche : {$subNiche['name']} (niche: {$subNiche['niche_name']})" . PHP_EOL;

// 2. Récupère les produits eBay
echo "[generate-article] Appel API eBay ({$ebay_marketplace}) : \"{$subNiche['ebay_query']}\"..." . PHP_EOL;
$products = cs_fetch_ebay_products($subNiche['ebay_query'], $ebay_marketplace, CS_EBAY_PRODUCTS_PER_ARTICLE, $currency);

if (empty($products)) {
    echo "[generate-article] WARN: Aucun produit eBay trouvé pour \"{$subNiche['ebay_query']}\". On continue avec 0 produits." . PHP_EOL;
}

echo "[generate-article] " . count($products) . " produits récupérés." . PHP_EOL;

// 3. Titre : utilise le keyword pré-généré si disponible, sinon template statique
if (!empty($subNiche['kw_title'])) {
    $title = $subNiche['kw_title'];
    echo "[generate-article] Titre depuis keyword pré-généré (ID {$subNiche['kw_id']})." . PHP_EOL;
} else {
    $title = cs_generate_article_title($subNiche['name'], $mainLanguage, $countryLabel);
    echo "[generate-article] Titre depuis template (aucun keyword pré-généré)." . PHP_EOL;
}

$metaDescription = cs_generate_meta_description($subNiche['name'], $mainLanguage, $countryLabel);

echo "[generate-article] Appel Claude Sonnet — titre : \"{$title}\"..." . PHP_EOL;
$contentHtml = cs_generate_article_html(
    $subNiche['name'],
    $subNiche['niche_name'],
    $mainLanguage,
    $countryLabel,
    $products
);

if ($contentHtml === null) {
    // Marque l'article en erreur si déjà en DB, sinon insert avec status=error
    $slug = cs_slugify($title);
    cs_upsert_article($pdo, (int)$subNiche['id'], $currentDomain, $mainLanguage, $title, $slug, $metaDescription, '', 'error');
    echo "[generate-article] ERREUR: Génération Claude échouée. Article marqué 'error'." . PHP_EOL;
    exit(1);
}

echo "[generate-article] HTML généré (" . strlen($contentHtml) . " caractères)." . PHP_EOL;

// 4. Insère l'article en DB
$slug      = cs_slugify($title);
$articleId = cs_upsert_article(
    $pdo,
    (int) $subNiche['id'],
    $currentDomain,
    $mainLanguage,
    $title,
    $slug,
    $metaDescription,
    $contentHtml,
    'published'
);
cs_publish_article($pdo, $articleId);

echo "[generate-article] Article inséré/mis à jour — ID: {$articleId}" . PHP_EOL;

// 5. Sauvegarde les produits
if (!empty($products)) {
    cs_save_article_products($pdo, $articleId, $products);
    echo "[generate-article] " . count($products) . " produits sauvegardés." . PHP_EOL;
}

// 6. Marque le keyword comme utilisé
if (!empty($subNiche['kw_id'])) {
    cs_mark_keyword_used($pdo, (int) $subNiche['kw_id']);
    echo "[generate-article] Keyword ID {$subNiche['kw_id']} marqué comme utilisé." . PHP_EOL;
}

echo "[generate-article] Terminé. Article ID {$articleId} publié." . PHP_EOL;
exit(0);
