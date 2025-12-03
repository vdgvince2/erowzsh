<?php

/**
 * Enregistre l'URL de la page visitée dans un array de session
 *
 */

// Initialiser le tableau des pages visitées
if (!isset($_SESSION['visited_pages']) || !is_array($_SESSION['visited_pages'])) {
    $_SESSION['visited_pages'] = [];
}

// Construire l'URL telle que vue par le navigateur (URL réécrite)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$uri    = $_SERVER['REQUEST_URI'] ?? '/';

$currentUrl = $scheme . '://' . $host . $uri;

// Référence vers le tableau en session
$visited = &$_SESSION['visited_pages'];

// Ajouter uniquement si l'URL n'est pas encore présente
if (!in_array($currentUrl, $visited, true)) {
    $visited[] = $currentUrl;

    // Optionnel : limiter à 50 pages max, en gardant les plus récentes
    $maxPages = 50;
    if (count($visited) > $maxPages) {
        $visited = array_slice($visited, -$maxPages);
    }
}


?>