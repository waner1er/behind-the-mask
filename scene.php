<?php

declare(strict_types=1);

use Vigilante\Http\SceneController;

$app = require __DIR__ . '/bootstrap.php';

(new SceneController($app->scenes()))->handle($_GET);
