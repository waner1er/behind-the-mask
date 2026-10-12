<?php

/**
 * Prépare le jeu pour l'app iOS : php tools/build-ios.php → ios/www/
 * et l'icône de l'app (commitée : elle ne change que si le sprite de Pete change).
 * Le CSS doit être compilé avant (npm run css) : on copie build/style.css tel quel.
 */

declare(strict_types=1);

use Vigilante\Build\AppIcon;
use Vigilante\Build\SiteBundleBuilder;

$app = require dirname(__DIR__) . '/bootstrap.php';

$target = $app->root . '/ios/www';
$written = (new SiteBundleBuilder($app, $target))->build();
printf("%d fichiers et dossiers → ios/www/\n", count($written));

file_put_contents($app->root . '/ios/Vigilante/Assets.xcassets/AppIcon.appiconset/AppIcon.png', AppIcon::png());
echo "icône → ios/Vigilante/Assets.xcassets/AppIcon.appiconset/AppIcon.png\n";
