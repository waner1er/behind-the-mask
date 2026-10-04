<?php
require __DIR__ . '/src/bootstrap.php';

$band = "Vigilante";
$title = "Behind the Mask";
$year = $album['year'] ?: 2026;

// numéro de version des fichiers : force le navigateur à recharger après chaque mise à jour
$version = fn(string $file) => $file . '?v=' . filemtime(__DIR__ . '/' . $file);

// Configuration envoyée au JavaScript en JSON
$game = [
    'width' => Scene::WIDTH,
    'height' => Scene::HEIGHT,
    'levelLength' => LEVEL_LENGTH,
    'floor' => ['min' => Scene::GROUND + 6, 'max' => Scene::HEIGHT - 6],
    // reach = portée du coup, projectile = attaque à distance
    // moves = attaques variées (poids) : strike = coup normal, lunge = ruée, throw = lancer d'arme
    'enemies' => [
        'skinhead' => ['name' => 'SKINHEAD', 'hp' => 3, 'speed' => 0.7, 'damage' => 9, 'reach' => 26, 'score' => 100, 'moves' => ['strike' => 3, 'lunge' => 1]],
        'batter' => ['name' => 'SKIN À BATTE', 'hp' => 4, 'speed' => 0.65, 'damage' => 14, 'reach' => 40, 'weapon' => 'bat', 'score' => 150, 'moves' => ['strike' => 3, 'throw' => 1], 'throws' => 'bat'],
        'chainer' => ['name' => 'SKIN À CHAÎNE', 'hp' => 4, 'speed' => 0.9, 'damage' => 11, 'reach' => 44, 'weapon' => 'chain', 'score' => 150, 'moves' => ['strike' => 2, 'lunge' => 2]],
        'hooligan' => ['name' => 'HOOLIGAN', 'hp' => 3, 'speed' => 0.65, 'damage' => 11, 'reach' => 26, 'projectile' => 'bottle', 'every' => 100, 'score' => 150, 'moves' => ['throw' => 4]],
        'masculinist' => ['name' => 'MASCULINISTE', 'hp' => 5, 'speed' => 0.55, 'damage' => 13, 'reach' => 26, 'score' => 150, 'moves' => ['strike' => 2, 'lunge' => 1]],
        'knifer' => ['name' => 'MASCU AU COUTEAU', 'hp' => 3, 'speed' => 1.1, 'damage' => 15, 'reach' => 32, 'weapon' => 'knife', 'score' => 150, 'moves' => ['strike' => 2, 'lunge' => 2, 'throw' => 1], 'throws' => 'knife'],
        'gymbro' => ['name' => 'GYM BRO', 'hp' => 9, 'speed' => 0.45, 'damage' => 22, 'reach' => 30, 'weapon' => 'dumbbell', 'score' => 250, 'moves' => ['strike' => 3, 'lunge' => 1, 'throw' => 1], 'throws' => 'dumbbell'],
    ],
    // Armes lâchées par les ennemis : le héros les garde 15 secondes à la place du jo
    'weaponDuration' => 15 * 60, // en images (60 par seconde)
    'weapons' => [
        'staff' => ['name' => 'JO', 'damage' => 1, 'reach' => 40, 'knockback' => 2.6],
        'bat' => ['name' => 'BATTE', 'damage' => 2, 'reach' => 38, 'knockback' => 4],
        'chain' => ['name' => 'CHAÎNE', 'damage' => 1, 'reach' => 50, 'knockback' => 2.2],
        'knife' => ['name' => 'COUTEAU', 'damage' => 2, 'reach' => 28, 'knockback' => 1.6],
        'dumbbell' => ['name' => 'HALTÈRE', 'damage' => 3, 'reach' => 28, 'knockback' => 5],
    ],
    'levels' => $levels,
    'ending' => [
        "WHO'S BEHIND THE MASK?",
        "IT'S A MEMBER OF YOUR FRIENDS",
        "IT'S A MEMBER OF YOUR FAMILY",
        'IF YOU KILL A MONSTER<br>YOU CAN BECOME A MONSTER',
    ],
    'links' => $album['links'],
    // décor d'un niveau : généré par PHP, ou fichier statique pour GitHub Pages (voir tools/build.php)
    'sceneUrl' => defined('STATIC_BUILD') ? 'scenes/level-%d.html' : 'scene.php?level=%d',
    'sprites' => require __DIR__ . '/src/sprites/characters.php',
    'items' => $props,
];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $band ?> - <?= $title ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Permanent+Marker&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $version('build/style.css') ?>">
</head>

<body>
    <div class="cabinet">
        <header class="marquee">
            <h1 class="marquee__title">
                <!-- le logo de l'album (recoloré pour le fond noir) -->
                <img class="marquee__logo" src="medias/images/logo-vigilante.png" alt="<?= $band ?> - <?= $title ?>">
            </h1>
            <p class="marquee__copyright">© <?= $year ?> <?= strtoupper($band) ?> · <?= strtoupper($title) ?></p>
        </header>

        <div class="bezel">
            <div class="screen">
                <svg class="screen__scene" viewBox="0 0 <?= Scene::WIDTH ?> <?= Scene::HEIGHT ?>" shape-rendering="crispEdges" aria-hidden="true">
                    <?= levelScene($album, $levelConfig, $props, 0) ?>
                </svg>

                <!-- canvas en résolution x2 : les boss peuvent grossir par pas de 0.5 en gardant des pixels nets -->
                <canvas class="screen__actors" width="<?= Scene::WIDTH * 2 ?>" height="<?= Scene::HEIGHT * 2 ?>"></canvas>

                <div class="hud">
                    <div class="hud__player">
                        <div class="hud__row">
                            <span class="hud__label">1UP</span>
                            <span class="hud__score" data-hud="score">000000</span>
                        </div>
                        <div class="hud__bar"><div class="hud__fill" data-hud="life"></div></div>
                        <span class="hud__lives" data-hud="lives">♥ x3</span>
                    </div>
                    <div class="hud__center">
                        <span class="hud__label hud__label--red" data-hud="level">HI-SCORE</span>
                        <span data-hud="hiscore">000000</span>
                    </div>
                    <div class="hud__enemy" data-hud="enemy" hidden>
                        <span class="hud__label" data-hud="enemy-name"></span>
                        <div class="hud__bar hud__bar--enemy"><div class="hud__fill" data-hud="enemy-life"></div></div>
                    </div>

                    <div class="hud__message" data-hud="message"></div>
                    <p class="hud__go" data-hud="go" hidden>GO ➜</p>
                    <p class="hud__mute" data-hud="mute" hidden>♪ OFF</p>
                </div>
            </div>
        </div>

        <div class="panel">
            <!-- ordinateur : le stick suit les flèches du clavier ; mobile : stick tactile -->
            <div class="joystick" data-joystick aria-label="Joystick"></div>
            <div class="touch-stick" data-touch-stick aria-label="Stick">
                <span class="touch-stick__knob"></span>
            </div>
            <ul class="panel__help">
                <li><kbd>←</kbd><kbd>→</kbd><kbd>↑</kbd><kbd>↓</kbd> marcher · <kbd>ENTRÉE</kbd> start</li>
                <li><kbd>ESPACE</kbd> coup de jo · <kbd>B</kbd> coup de pied sauté</li>
                <li><kbd>V</kbd> bombe · <kbd>C</kbd> skate · <kbd>M</kbd> musique</li>
            </ul>
            <div class="panel__buttons">
                <?php foreach ([['red', 'Space', 'JO'], ['yellow', 'KeyB', 'B'], ['white', 'KeyV', 'V'], ['black', 'KeyC', 'C']] as [$color, $key, $label]): ?>
                    <label class="arcade-btn-wrap">
                        <button class="arcade-btn arcade-btn--<?= $color ?>" type="button" data-key="<?= $key ?>" aria-label="<?= $label ?>"></button>
                        <span><?= $label ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="panel__system">
                <button class="system-btn" type="button" data-key="Enter">START</button>
                <button class="system-btn" type="button" data-key="KeyM">♪</button>
            </div>
            <div class="coin-door">
                <?php for ($i = 0; $i < 2; $i++): ?>
                    <div class="coin-door__slot"><span>1 €</span></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- mobile en portrait : on demande de tourner le téléphone -->
    <div class="rotate-hint" aria-hidden="true">
        <span class="rotate-hint__icon">📱</span>
        <p>TOURNE TON TÉLÉPHONE<br>EN MODE PAYSAGE</p>
    </div>

    <script type="application/json" id="game-data"><?= json_encode($game, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <script src="<?= $version('js/sfx.js') ?>"></script>
    <script src="<?= $version('js/game.js') ?>"></script>
</body>

</html>
