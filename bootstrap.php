<?php

declare(strict_types=1);

use Vigilante\Application;

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit("Dépendances manquantes : lance « composer install ».\n");
}
require $autoload;

return new Application(__DIR__);
