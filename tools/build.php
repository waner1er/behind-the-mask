<?php

/**
 * Génère la version statique du jeu (pour GitHub Pages, qui n'exécute pas PHP) :
 *   - index.html         : la page complète
 *   - scenes/level-N.html : le décor de chaque niveau
 *
 * Usage : php tools/build.php
 */

define('STATIC_BUILD', true);
chdir(dirname(__DIR__));

ob_start();
require 'index.php';
file_put_contents('index.html', ob_get_clean());
echo "index.html\n";

if (!is_dir('scenes')) {
    mkdir('scenes');
}
foreach (array_keys($album['tracks']) as $index) {
    file_put_contents("scenes/level-$index.html", levelScene($album, $levelConfig, $props, $index));
    echo "scenes/level-$index.html\n";
}

file_put_contents('scenes/level-peace.html', peaceScene($props));
echo "scenes/level-peace.html\n";
