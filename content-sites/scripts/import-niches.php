#!/usr/bin/env php
<?php
/**
 * Import one-time des niches et sous-niches depuis data/niches.md → DB.
 *
 * Usage :
 *   php import-niches.php GB
 *   php import-niches.php FR   (même données, DB différente)
 *
 * Le fichier niches.md utilise le format :
 *   ### **1. Nom Niche**
 *   - Sous-niche 1
 *   - Sous-niche 2
 *
 * Ce script est idempotent (INSERT IGNORE).
 */

define('CS_CLI', true);
$currentDomain = $argv[1] ?? 'IE'; // code pays (IE/GB…) ou domaine prod

require_once __DIR__ . '/../inc/config.php';

$nichesMd = file_get_contents(__DIR__ . '/../data/niches.md');
if (!$nichesMd) {
    die("Impossible de lire data/niches.md\n");
}

echo "[import-niches] Lecture de niches.md..." . PHP_EOL;

// Parse le fichier markdown
$lines      = explode("\n", $nichesMd);
$niches     = [];   // [['name'=>..., 'subniches'=>[...]], ...]
$currentNiche = null;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    // Titre de niche : ### **1. Antiquités et Art**
    if (preg_match('/^###\s+\*\*\d+\.\s+(.+)\*\*$/', $line, $m)) {
        if ($currentNiche !== null) {
            $niches[] = $currentNiche;
        }
        $currentNiche = ['name' => trim($m[1]), 'subniches' => []];
        continue;
    }

    // Sous-niche : - Antique clocks
    if ($currentNiche !== null && preg_match('/^-\s+(.+)$/', $line, $m)) {
        $currentNiche['subniches'][] = trim($m[1]);
    }
}
if ($currentNiche !== null) {
    $niches[] = $currentNiche;
}

echo "[import-niches] " . count($niches) . " niches trouvées." . PHP_EOL;

// Insert en DB
$stmtNiche = $pdo->prepare('
    INSERT IGNORE INTO niches (name, slug, sort_order)
    VALUES (:name, :slug, :sort)
');

$stmtSN = $pdo->prepare('
    INSERT IGNORE INTO sub_niches (niche_id, name, slug, ebay_query, sort_order)
    VALUES (:niche_id, :name, :slug, :ebay_query, :sort)
');

$stmtGetNiche = $pdo->prepare('SELECT id FROM niches WHERE slug = :slug LIMIT 1');

foreach ($niches as $nicheOrder => $nicheData) {
    $nicheSlug = cs_slugify($nicheData['name']);

    $stmtNiche->execute([
        ':name' => $nicheData['name'],
        ':slug' => $nicheSlug,
        ':sort' => $nicheOrder,
    ]);

    $stmtGetNiche->execute([':slug' => $nicheSlug]);
    $nicheId = (int) $stmtGetNiche->fetchColumn();

    echo "[import-niches] Niche #{$nicheId}: {$nicheData['name']}" . PHP_EOL;

    foreach ($nicheData['subniches'] as $snOrder => $snName) {
        if (mb_strtolower(trim($snName)) === mb_strtolower(trim($nicheData['name']))) {
            echo "  → Skip (même nom que la niche parente) : {$snName}" . PHP_EOL;
            continue;
        }

        $snSlug  = cs_slugify($snName);
        // La ebay_query est le nom anglais de la sous-niche — à affiner manuellement si besoin
        $ebayQuery = $snName;

        $stmtSN->execute([
            ':niche_id'   => $nicheId,
            ':name'       => $snName,
            ':slug'       => $snSlug,
            ':ebay_query' => $ebayQuery,
            ':sort'       => $snOrder,
        ]);

        echo "  → {$snName} (slug: {$snSlug})" . PHP_EOL;
    }
}

echo PHP_EOL . "[import-niches] Import terminé dans la DB {$dbConfig['dbname']}." . PHP_EOL;
echo "[import-niches] Relancer pour chaque pays : php import-niches.php FR, DE, IT, BE, IE" . PHP_EOL;
