#!/usr/bin/env php
<?php
/**
 * Pré-génère les titres d'articles via Claude pour toutes les sous-niches.
 *
 * Flux :
 *  1. Récupère les sous-niches sans keywords pour cette langue
 *  2. Pour chaque sous-niche, appelle Claude (combinaisons sous-niche × angles)
 *  3. Claude filtre par intention informationnelle et renvoie un JSON scoré
 *  4. Stocke tous les candidats en DB, sélectionne le meilleur
 *
 * Usage CLI :
 *   php generate-keywords.php GB
 *   php generate-keywords.php FR --limit=20
 *   php generate-keywords.php DE --all
 *   php generate-keywords.php IT --force    (re-génère même si keywords existants)
 *
 * Options :
 *   --limit=N   Nombre max de sous-niches à traiter par run (défaut: 10)
 *   --all       Traite toutes les sous-niches sans limite
 *   --force     Re-génère même si des keywords existent déjà pour cette langue
 */

define('CS_CLI', true);
$currentDomain = $argv[1] ?? 'IE'; // code pays (IE/GB…) ou domaine prod (antiques.ie)

// ── Parse options ─────────────────────────────────────────────────────────────
$opts  = array_slice($argv, 2);
$limit = 10;
$all   = false;
$force = false;

foreach ($opts as $opt) {
    if (preg_match('/^--limit=(\d+)$/', $opt, $m)) $limit = (int) $m[1];
    if ($opt === '--all')   $all   = true;
    if ($opt === '--force') $force = true;
}

require_once __DIR__ . '/../inc/config.php';

echo "[generate-keywords] Démarrage — domain: {$currentDomain}, langue: {$mainLanguage}" . PHP_EOL;
if ($force) echo "[generate-keywords] Mode --force : re-génère les sous-niches déjà traitées." . PHP_EOL;
if ($all)   echo "[generate-keywords] Mode --all : pas de limite de nombre." . PHP_EOL;
else        echo "[generate-keywords] Limite : {$limit} sous-niche(s) par run." . PHP_EOL;

// ── 1. Sélection des sous-niches à traiter ───────────────────────────────────
$sql = '
    SELECT sn.id, sn.name, sn.ebay_query,
           n.name AS niche_name, n.slug AS niche_slug
    FROM sub_niches sn
    JOIN niches n ON n.id = sn.niche_id
';

if (!$force) {
    // Exclut les sous-niches qui ont déjà des keywords pour ce domaine
    $sql .= '
    WHERE sn.id NOT IN (
        SELECT DISTINCT sub_niche_id
        FROM sub_niche_keywords
        WHERE domain = :domain
    )
    ';
}

$sql .= ' ORDER BY n.sort_order ASC, sn.sort_order ASC';

if (!$all) {
    $sql .= ' LIMIT ' . $limit;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($force ? [] : [':domain' => $currentDomain]);
$subNiches = $stmt->fetchAll();

if (empty($subNiches)) {
    echo "[generate-keywords] Toutes les sous-niches ont déjà des keywords pour {$currentDomain}. Rien à faire." . PHP_EOL;
    exit(0);
}

echo "[generate-keywords] " . count($subNiches) . " sous-niche(s) à traiter." . PHP_EOL;

// ── 2. Génération des keywords ────────────────────────────────────────────────
$ok     = 0;
$errors = 0;

foreach ($subNiches as $sn) {
    echo PHP_EOL;
    echo "[generate-keywords] ─────────────────────────────────────────────────" . PHP_EOL;
    echo "[generate-keywords] Sous-niche : \"{$sn['name']}\" (niche: {$sn['niche_name']})" . PHP_EOL;

    $keywords = cs_generate_keywords_for_subniche(
        $sn['name'],
        $sn['niche_name'],
        $mainLanguage,
        $countryLabel
    );

    if (empty($keywords)) {
        echo "[generate-keywords] WARN: Aucun keyword généré pour \"{$sn['name']}\". Passage à la suivante." . PHP_EOL;
        $errors++;
        // Petite pause avant le prochain appel
        sleep(1);
        continue;
    }

    echo "[generate-keywords] " . count($keywords) . " keyword(s) générés :" . PHP_EOL;
    foreach ($keywords as $kw) {
        $score  = str_pad((string) $kw['score'], 3, ' ', STR_PAD_LEFT);
        $intent = str_pad($kw['intent'], 15);
        echo "  [{$score}] [{$intent}] {$kw['title']}" . PHP_EOL;
    }

    // ── 3. Sauvegarde en DB ───────────────────────────────────────────────────
    if ($force) {
        // En mode force : reset les keywords existants pour cette sous-niche + domaine
        $pdo->prepare('
            DELETE FROM sub_niche_keywords
            WHERE sub_niche_id = :sn AND domain = :domain AND used = 0
        ')->execute([':sn' => (int)$sn['id'], ':domain' => $currentDomain]);
    }

    cs_save_keywords($pdo, (int) $sn['id'], $currentDomain, $mainLanguage, $keywords);

    // Affiche le meilleur keyword (sera utilisé en priorité lors de la génération)
    $best = $pdo->prepare('
        SELECT title, intent_score FROM sub_niche_keywords
        WHERE sub_niche_id = :sn AND domain = :domain AND used = 0
        ORDER BY intent_score DESC
        LIMIT 1
    ');
    $best->execute([':sn' => (int)$sn['id'], ':domain' => $currentDomain]);
    $top = $best->fetch();

    if ($top) {
        echo "[generate-keywords] ✓ Meilleur candidat [{$top['intent_score']}] : \"{$top['title']}\"" . PHP_EOL;
    }

    $ok++;

    // Pause entre les appels Claude pour éviter le rate limiting
    if ($ok < count($subNiches)) {
        usleep(500000); // 500ms
    }
}

// ── Résumé ────────────────────────────────────────────────────────────────────
echo PHP_EOL;
echo "[generate-keywords] ════════════════════════════════════════════════════" . PHP_EOL;
echo "[generate-keywords] Terminé. OK: {$ok} | Erreurs: {$errors}" . PHP_EOL;

exit($errors > 0 ? 1 : 0);
