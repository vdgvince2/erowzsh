<?php
/**
 * Import EEAT profiles from CSV into eeat_profiles table.
 *
 * Usage:
 *   php import_eeat.php ie          # insert + translate for content_ie
 *   php import_eeat.php ie --retranslate  # force re-translation even if bio already set
 *
 * Requires GOOGLE_TRANSLATE_API_KEY in .env to translate bios.
 * Without the key, only bio_fr is stored; run again once the key is set.
 *
 * CSV: /SH/data/eeat.csv  (Niche, Expert, Social_Media_Link, Biographie)
 * Source language of bios: French (fr)
 * Target languages: EN, DE, IT
 */

declare(strict_types=1);
define('CS_CLI', true);

$currentDomain = $argv[1] ?? 'IE'; // code pays (IE/GB…) ou domaine prod
$retranslate = in_array('--retranslate', $argv, true);

$scriptDir = dirname(__DIR__);
require_once $scriptDir . '/inc/config.php';

$csvFile = dirname($scriptDir) . '/data/eeat.csv';
if (!file_exists($csvFile)) {
    die("❌ CSV introuvable : $csvFile\n");
}

// ── Parse CSV ────────────────────────────────────────────────────────────────
$rows = array_map('str_getcsv', file($csvFile));
array_shift($rows); // header

$profiles = [];
foreach ($rows as $row) {
    if (count($row) < 4) continue;
    [$niche, $expert, $social, $bio] = array_map('trim', $row);
    if ($niche === '' || $expert === '' || $bio === '') continue;
    if (!isset($profiles[$niche])) {
        $profiles[$niche] = ['expert' => $expert, 'social' => $social, 'bio_fr' => $bio];
    }
}

echo "📄 " . count($profiles) . " profils uniques dans le CSV.\n";

// ── Load sub_niches ───────────────────────────────────────────────────────────
$stmt = $pdo->query("SELECT id, name FROM sub_niches");
$subNiches = [];
foreach ($stmt->fetchAll() as $row) {
    $subNiches[$row['name']] = (int)$row['id'];
}

// ── Google Translate helper ───────────────────────────────────────────────────
function google_translate(string $text, string $target, string $apiKey): ?string
{
    if ($apiKey === '' || $text === '') return null;

    $url  = 'https://translation.googleapis.com/language/translate/v2?key=' . urlencode($apiKey);
    $body = json_encode(['q' => $text, 'source' => 'fr', 'target' => $target, 'format' => 'text']);

    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($body),
        'content' => $body,
        'timeout' => 15,
    ]]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) {
        echo "  ⚠️  Google Translate error for target=$target\n";
        return null;
    }
    $data = json_decode($resp, true);
    return $data['data']['translations'][0]['translatedText'] ?? null;
}

// ── Import loop ───────────────────────────────────────────────────────────────
$apiKey  = GOOGLE_TRANSLATE_API_KEY;
$hasKey  = $apiKey !== '';
$insert  = 0;
$update  = 0;
$skipped = [];

$insertSql = $pdo->prepare("
    INSERT INTO eeat_profiles (sub_niche_id, expert_name, social_link, bio_fr, bio_en, bio_de, bio_it)
    VALUES (:sid, :name, :social, :fr, :en, :de, :it)
    ON DUPLICATE KEY UPDATE
        expert_name = VALUES(expert_name),
        social_link = VALUES(social_link),
        bio_fr      = VALUES(bio_fr),
        bio_en      = VALUES(bio_en),
        bio_de      = VALUES(bio_de),
        bio_it      = VALUES(bio_it)
");

// Requête pour vérifier si des traductions existent déjà
$checkSql = $pdo->prepare("SELECT bio_en FROM eeat_profiles WHERE sub_niche_id = :sid");

foreach ($profiles as $nicheName => $p) {
    if (!isset($subNiches[$nicheName])) {
        $skipped[] = $nicheName;
        continue;
    }

    $sid   = $subNiches[$nicheName];
    $bioFr = $p['bio_fr'];
    $bioEn = null;
    $bioDe = null;
    $bioIt = null;

    // Vérifie si une traduction existe déjà (sauf --retranslate)
    $needsTranslation = true;
    if (!$retranslate) {
        $checkSql->execute([':sid' => $sid]);
        $existing = $checkSql->fetch();
        if ($existing && $existing['bio_en'] !== null) {
            $needsTranslation = false;
        }
    }

    if ($hasKey && $needsTranslation) {
        echo "  🌐 Traduction : $nicheName\n";
        $bioEn = google_translate($bioFr, 'en', $apiKey) ?? $bioFr;
        $bioDe = google_translate($bioFr, 'de', $apiKey);
        $bioIt = google_translate($bioFr, 'it', $apiKey);
        usleep(200000); // 200 ms entre chaque appel
    } elseif (!$hasKey) {
        $bioEn = $bioFr; // fallback FR si pas de clé
    } else {
        // Traductions déjà présentes, skip
        echo "  ⏭  $nicheName (traductions existantes)\n";
        // On fait quand même un upsert pour mettre à jour nom/social si nécessaire
        $checkSql->execute([':sid' => $sid]);
        $ex = $checkSql->fetch();
        $bioEn = $ex['bio_en'] ?? $bioFr;
        // Pour DE et IT on laisse NULL — le ON DUPLICATE KEY UPDATE les préservera
        // via une requête séparée simplifiée
        $pdo->prepare("UPDATE eeat_profiles SET expert_name=:name, social_link=:social, bio_fr=:fr WHERE sub_niche_id=:sid")
            ->execute([':name' => $p['expert'], ':social' => $p['social'], ':fr' => $bioFr, ':sid' => $sid]);
        $update++;
        continue;
    }

    $insertSql->execute([
        ':sid'    => $sid,
        ':name'   => $p['expert'],
        ':social' => $p['social'],
        ':fr'     => $bioFr,
        ':en'     => $bioEn,
        ':de'     => $bioDe,
        ':it'     => $bioIt,
    ]);

    $pdo->lastInsertId() ? $insert++ : $update++;
    echo "  ✓ $nicheName\n";
}

// ── Summary ───────────────────────────────────────────────────────────────────
echo "\n✅ Import terminé : $insert insérés, $update mis à jour.\n";
if (!$hasKey) {
    echo "⚠️  GOOGLE_TRANSLATE_API_KEY manquante : seule bio_fr est stockée.\n";
    echo "   Relancez avec la clé pour traduire en EN/DE/IT.\n";
}
if ($skipped) {
    echo "⛔ Sous-niches non trouvées en DB (" . count($skipped) . ") :\n";
    foreach ($skipped as $s) echo "   - $s\n";
}
