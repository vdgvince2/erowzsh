#!/usr/bin/env php
<?php
/**
 * Publie le dernier article en statut 'draft' et ping les moteurs.
 *
 * Usage CLI :
 *   php publish-article.php GB
 *
 * Le script :
 *  1. Sélectionne l'article draft le plus récent
 *  2. Passe son statut à 'published'
 *  3. Ping IndexNow + Google Indexing API
 *  4. Logue le résultat en DB
 *
 * Appelé par article-cron.sh juste après generate-article.php.
 */

define('CS_CLI', true);
$currentDomain = $argv[1] ?? 'IE'; // code pays (IE/GB…) ou domaine prod (antiques.ie)

require_once __DIR__ . '/../inc/config.php';

echo "[publish-article] Démarrage — domain: {$currentDomain}" . PHP_EOL;

// 1. Récupère le dernier draft
$stmt = $pdo->prepare('
    SELECT a.*, sn.slug AS sub_niche_slug, n.slug AS niche_slug, n.name AS niche_name
    FROM articles a
    JOIN sub_niches sn ON sn.id = a.sub_niche_id
    JOIN niches n ON n.id = sn.niche_id
    WHERE a.status = "draft" AND a.domain = :domain
    ORDER BY a.updated_at DESC
    LIMIT 1
');
$stmt->execute([':domain' => $currentDomain]);
$article = $stmt->fetch();

if (!$article) {
    echo "[publish-article] Aucun article draft trouvé pour {$currentDomain}. Rien à publier." . PHP_EOL;
    exit(0);
}

echo "[publish-article] Publication : \"{$article['title']}\" (ID: {$article['id']})" . PHP_EOL;

// 2. Publie
cs_publish_article($pdo, (int)$article['id']);

// 3. Construit l'URL publique de l'article
// En prod : https://antique-clocks.art.co.uk/
// La constante DEALS_PROD_DOMAINS donne le domaine racine de la niche parente.
// On reconstruit le subdomain de la sous-niche à la volée.
$pageUrl = null;

// Récupère le domaine racine du pays depuis la map existante
$nicheRootDomain = DEALS_PROD_DOMAINS[$countryCode] ?? null; // $countryCode dérivé de sites.json

if ($nicheRootDomain) {
    // ex: https://for-sale.co.uk → on remplace le domaine par art.co.uk
    // Pour l'instant on construit : https://{sub-niche}.{niche}.{tld}/
    // La map prod des domaines niches est à définir dans le tenant — fallback générique ici.
    $host    = parse_url($nicheRootDomain, PHP_URL_HOST);
    $pageUrl = 'https://' . $article['sub_niche_slug'] . '.' . $article['niche_slug'] . '.' . $host . '/';
}

// En CLI, indexing_is_local() ne voit pas HTTP_HOST — on détecte via gethostname()
$isLocalCli = defined('CS_CLI') && (
    str_contains(gethostname(), 'local') ||
    str_contains(gethostname(), 'MacBook') ||
    str_contains(gethostname(), 'macbook')
);

// 4. Ping indexation (réutilise les fonctions existantes)
if ($pageUrl && !indexing_is_local() && !$isLocalCli) {
    echo "[publish-article] Ping IndexNow : {$pageUrl}" . PHP_EOL;
    $host = parse_url($pageUrl, PHP_URL_HOST);
    indexnow_ping($host, [$pageUrl]);
    cs_log_indexing($pdo, (int)$article['id'], $pageUrl, 'indexnow', 'sent');

    echo "[publish-article] Ping Google Indexing API..." . PHP_EOL;
    google_indexing_ping($pageUrl);
    cs_log_indexing($pdo, (int)$article['id'], $pageUrl, 'google', 'sent');

    echo "[publish-article] Ping Rapid Indexer..." . PHP_EOL;
    rapid_indexer_ping([$pageUrl], $article['title']);
    cs_log_indexing($pdo, (int)$article['id'], $pageUrl, 'rapid_indexer', 'sent');

    cs_mark_indexed($pdo, (int)$article['id']);
} else {
    echo "[publish-article] Env local détecté ou URL prod absente — pings ignorés." . PHP_EOL;
}

echo "[publish-article] Terminé. Article \"{$article['title']}\" publié." . PHP_EOL;
exit(0);
