<?php

/**
 * Génère le module OpenBOR pour la borne Recalbox : php tools/build-openbor.php [--fast]
 * → build/openbor/Vigilante.pak (et build/openbor/data/, le même contenu décompressé)
 * --fast : réutilise les captures, sons, décors et musiques du build précédent (mise au point).
 */

declare(strict_types=1);

use Vigilante\Export\OpenBor\ModuleBuilder;
use Vigilante\Export\OpenBor\Toolchain;

$app = require dirname(__DIR__) . '/bootstrap.php';

$fast = in_array('--fast', $argv, true);
$pak = (new ModuleBuilder($app, $app->root . '/build/openbor', new Toolchain($app->root), $fast))->build();
printf("%s (%.1f Mo)\n", $pak, filesize($pak) / 1e6);
