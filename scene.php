<?php
// Renvoie le décor SVG d'un niveau : scene.php?level=3
require __DIR__ . '/src/bootstrap.php';

// scene.php?level=peace : la ville libérée de la fin du jeu
if (($_GET['level'] ?? '') === 'peace') {
    header('Content-Type: text/html; charset=utf-8');
    echo peaceScene($props);
    exit;
}

$index = (int) ($_GET['level'] ?? 0);
if (!isset($album['tracks'][$index])) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo levelScene($album, $levelConfig, $props, $index);
