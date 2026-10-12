<?php

/**
 * Génère le site publié sur GitHub Pages : php tools/build.php → build/site/
 * Le CSS doit être compilé avant (npm run css) : on copie build/style.css tel quel.
 */

declare(strict_types=1);

use Vigilante\Build\SiteBundleBuilder;

$app = require dirname(__DIR__) . '/bootstrap.php';

$written = (new SiteBundleBuilder($app, $app->root . '/build/site'))->build();
printf("%d fichiers et dossiers → build/site/\n", count($written));
