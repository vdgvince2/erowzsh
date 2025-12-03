<?php
/*

This script exports all the images URL to a flat file.
import it on the CDN server to download all the required pictures.

Generate this for all countries
*/
declare(strict_types=1);

// All country array
$countryArray = array("FR", "US", "UK", "IE", "BE", "DE", "IT");
foreach($countryArray as $country){

    $outputBaseDir = __DIR__ . '/../data/images'; // Répertoire racine de sortie

    // DB connection
    $dbHost = '127.0.0.1';
    $dbport = '8889';
    $dbName = $country;
    $dbUser = $country;
    $dbPass = 'test';
    $dbCharset = 'utf8mb4';

    $dsn = "mysql:host={$dbHost};dbname={$dbName};port={$dbport};charset={$dbCharset}";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // Débuffer la requête pour streamer ligne par ligne (évite la surcharge mémoire)
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
    ]);

    echo "Start with $country".PHP_EOL;

    // ── Préparation sortie ─────────────────────────────────────────────────────────
    $targetDir  = rtrim($outputBaseDir, '/').'/'.trim($country, '/');
    $finalPath  = $targetDir . '/images.txt';
    $tmpPath    = $finalPath . '.tmp';

    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        fwrite(STDERR, "[ERR] Impossible de créer le répertoire: {$targetDir}\n");
        exit(1);
    }

    // ── Exécution requête ─────────────────────────────────────────────────────────
    $sql = "SELECT photo FROM `ads` 
                WHERE `photo` LIKE '%ebayimg%'";
    $stmt = $pdo->query($sql);

    // ── Écriture atomique du fichier ──────────────────────────────────────────────
    $fh = @fopen($tmpPath, 'wb');
    if ($fh === false) {
        fwrite(STDERR, "[ERR] Impossible d'ouvrir le fichier temporaire en écriture: {$tmpPath}\n");
        exit(1);
    }

    // On verrouille pour éviter les lectures concurrentes d'un fichier partiellement écrit
    if (!flock($fh, LOCK_EX)) {
        fclose($fh);
        @unlink($tmpPath);
        fwrite(STDERR, "[ERR] Impossible d'obtenir un verrou sur: {$tmpPath}\n");
        exit(1);
    }

    // Normaliser les fins de ligne en LF pour compatibilité multi-OS
    $eol = "\n";
    $written = 0;

    try {
        // Itération streamée
        while ($row = $stmt->fetch()) {
            $url = isset($row[0]) ? trim((string)$row[0]) : '';
            if ($url === '') {
                continue;
            }
            // Optionnel: ne garder que des URLs valides
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                // ignorer silencieusement les valeurs non valides
                continue;
            }
            // Écrit une URL par ligne
            fwrite($fh, $url . $eol);
            $written++;
        }
    } catch (Throwable $e) {
        flock($fh, LOCK_UN);
        fclose($fh);
        @unlink($tmpPath);
        fwrite(STDERR, "[ERR] Échec lors de la lecture/écriture: {$e->getMessage()}\n");
        exit(1);
    }

    // Déverrouille et ferme
    flock($fh, LOCK_UN);
    fclose($fh);

    // Remplacement atomique du fichier final
    if (!@rename($tmpPath, $finalPath)) {
        @unlink($tmpPath);
        fwrite(STDERR, "[ERR] Échec du renommage du fichier temporaire vers le fichier final.\n");
        exit(1);
    }

    echo "[OK] {$written} URL(s) écrite(s) dans: {$finalPath}\n";

}