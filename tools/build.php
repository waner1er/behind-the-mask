<?php

/**
 * Génère la version statique du jeu pour GitHub Pages : php tools/build.php
 */

declare(strict_types=1);

use Vigilante\Build\StaticSiteBuilder;

$app = require dirname(__DIR__) . '/bootstrap.php';

foreach ((new StaticSiteBuilder($app))->build() as $file) {
    echo $file, "\n";
}
