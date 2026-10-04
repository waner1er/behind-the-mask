<?php
/**
 * La borne d'arcade.
 *
 * @var string $band
 * @var string $title
 * @var int $year
 * @var string $logo
 * @var int $width
 * @var int $height
 * @var string $scene décor SVG du premier niveau
 * @var string $gameJson données du jeu (voir Vigilante\Game\GameData)
 * @var string $importMap
 * @var \Vigilante\View\AssetVersioner $assets
 */

$buttons = [['red', 'Space', 'KATANA'], ['yellow', 'KeyB', 'B'], ['white', 'KeyV', 'V'], ['black', 'KeyC', 'C']];
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
    <link rel="stylesheet" href="<?= $assets->url('build/style.css') ?>">
    <script type="importmap"><?= $importMap ?></script>
</head>

<body>
    <div class="cabinet">
        <header class="marquee">
            <h1 class="marquee__title">
                <img class="marquee__logo" src="<?= $logo ?>" alt="<?= $band ?> - <?= $title ?>">
            </h1>
            <p class="marquee__copyright">© <?= $year ?> <?= strtoupper($band) ?> · <?= strtoupper($title) ?></p>
        </header>

        <div class="bezel">
            <div class="screen">
                <svg class="screen__scene" viewBox="0 0 <?= $width ?> <?= $height ?>" shape-rendering="crispEdges" aria-hidden="true">
                    <?= $scene ?>
                </svg>

                <!-- résolution x2 : les boss grossissent par pas de 0.5 en gardant des pixels nets -->
                <canvas class="screen__actors" width="<?= $width * 2 ?>" height="<?= $height * 2 ?>"></canvas>

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
                    <div class="hud__dialog" data-hud="dialog" hidden></div>
                    <div class="hud__credits" data-hud="credits" hidden></div>
                    <p class="hud__go" data-hud="go" hidden>GO ➜</p>
                    <p class="hud__mute" data-hud="mute" hidden>♪ OFF</p>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="joystick" data-joystick aria-label="Joystick"></div>
            <div class="touch-stick" data-touch-stick aria-label="Stick">
                <span class="touch-stick__knob"></span>
            </div>
            <ul class="panel__help">
                <li><kbd>←</kbd><kbd>→</kbd><kbd>↑</kbd><kbd>↓</kbd> marcher · <kbd>ENTRÉE</kbd> start</li>
                <li><kbd>ESPACE</kbd> coup de katana · <kbd>B</kbd> coup de pied sauté</li>
                <li><kbd>V</kbd> vinyle · <kbd>ESPACE</kbd>+<kbd>V</kbd> wall of death · <kbd>C</kbd> skate</li>
            </ul>
            <div class="panel__buttons">
                <?php foreach ($buttons as [$color, $key, $label]) : ?>
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
                <?php for ($i = 0; $i < 2; $i++) : ?>
                    <div class="coin-door__slot"><span>1 €</span></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div class="rotate-hint" aria-hidden="true">
        <span class="rotate-hint__icon">📱</span>
        <p>TOURNE TON TÉLÉPHONE<br>EN MODE PAYSAGE</p>
    </div>

    <script type="application/json" id="game-data"><?= $gameJson ?></script>
    <script type="module" src="<?= $assets->url('js/main.js') ?>"></script>
</body>

</html>
