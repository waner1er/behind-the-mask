<?php

/**
 * Prépare le jeu pour l'app iOS : php tools/build-ios.php → ios/www/
 * Le CSS doit être compilé avant (npm run css) : on copie build/style.css tel quel.
 */

declare(strict_types=1);

use Vigilante\Build\IosBundleBuilder;

$app = require dirname(__DIR__) . '/bootstrap.php';

$target = $app->root . '/ios/www';
$written = (new IosBundleBuilder($app, $target))->build();
printf("%d fichiers et dossiers → ios/www/\n", count($written));
