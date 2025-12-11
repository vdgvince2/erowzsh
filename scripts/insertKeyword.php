<?php
/***
 * Insert keywords in sub domain. NOt valid for domain !!
 * 
 */
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';



$errors = [];
$successCount = 0;

// Fonction pour transformer le mot-clé en "subdomain" (lettres/chiffres uniquement)
function keyword_to_subdomain(string $keyword): string
{
    // Passage en minuscule
    $s = mb_strtolower($keyword, 'UTF-8');

    // Optionnel : enlever les accents
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);

    // Garder uniquement lettres et chiffres
    $s = preg_replace('/[^a-z0-9]/', '', $s);

    return $s ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = $_POST['keywords'] ?? '';

    // On découpe par ligne
    $lines = preg_split('/\r\n|\r|\n/', $raw);

    // Préparation de la requête
    $stmt = $pdo->prepare("
        INSERT INTO subdomain_keywords (keyword_name, subdomain, last_update)
        VALUES (:keyword_name, :subdomain, now())
    ");

    foreach ($lines as $line) {
        $keyword = trim($line);
        if ($keyword === '') {
            continue; // ligne vide → on skip
        }

        $subdomain = keyword_to_subdomain($keyword);

        // Si on n'arrive pas à générer un subdomain valable, on ignore
        if ($subdomain === '') {
            $errors[] = "Impossible de générer un subdomain pour le mot-clé : « " . htmlspecialchars($keyword, ENT_QUOTES) . " »";
            continue;
        }

        try {
            $stmt->execute([
                ':keyword_name' => $keyword,
                ':subdomain'    => $subdomain,
            ]);
            $successCount++;
        } catch (PDOException $e) {
            // En cas de doublon ou autre, on enregistre l'erreur mais on continue
            $errors[] = "Erreur pour « " . htmlspecialchars($keyword, ENT_QUOTES) . " » : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Encoder des mots-clés</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CDN Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-xl w-full bg-white shadow-lg rounded-xl p-6">
        <h1 class="text-2xl font-bold mb-4 text-gray-800">Encoder des mots-clés</h1>
        <p class="text-sm text-gray-600 mb-4">
            Saisis <strong>1 mot-clé par ligne</strong>.  
            Le champ <code>subdomain</code> sera généré automatiquement (lettres/chiffres uniquement).
        </p>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <?php if ($successCount > 0): ?>
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                    <?= $successCount; ?> mot(s)-clé(s) enregistré(s) avec succès.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm space-y-1">
                    <p class="font-semibold">Quelques problèmes sont survenus :</p>
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $err): ?>
                            <li><?= $err; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label for="keywords" class="block text-sm font-medium text-gray-700 mb-1">
                    Mots-clés
                </label>
                <textarea
                    id="keywords"
                    name="keywords"
                    rows="10"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm p-3"
                    placeholder="ex&#10;lit cabane&#10;chaise design&#10;table basse scandinave"
                ><?= isset($_POST['keywords']) ? htmlspecialchars($_POST['keywords'], ENT_QUOTES) : '' ?></textarea>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Enregistrer les mots-clés
                </button>
            </div>
        </form>
    </div>
</body>
</html>
