<?php

/**
 * Charge tout ce dont le jeu a besoin : l'album (paroles + audio),
 * la configuration des niveaux, les sprites.
 * Utilisé par index.php (la page) et scene.php (le décor d'un niveau).
 */

require __DIR__ . '/PixelArt.php';
require __DIR__ . '/Scene.php';
require __DIR__ . '/Album.php';

const ALBUM_DIR = 'medias/audio/Vigilante - Behind the Mask';
const LEVEL_LENGTH = Scene::WIDTH * 5;

// Phrases qu'on ne veut pas voir dans le jeu
const EXCLUDED_WORDS = ['NO JUSTICE', 'NO PEACE'];

$album = Album::load(dirname(__DIR__) . '/' . ALBUM_DIR, implode('/', array_map('rawurlencode', explode('/', ALBUM_DIR))));
$levelConfig = require __DIR__ . '/levels.php';
$props = require __DIR__ . '/sprites/props.php';

/** Graffitis d'un niveau : ceux du thème, puis des phrases courtes des paroles. */
function levelTags(array $track, array $config): array
{
    $short = Album::phrases($track['lyrics'], 14, EXCLUDED_WORDS);

    return array_values(array_unique(array_merge($config['tags'], $short)));
}

/** Vagues d'ennemis : de plus en plus nombreuses, avec de nouveaux ennemis armés à chaque niveau. */
function levelWaves(int $number, array $boss): array
{
    // ennemi(s) débloqué(s) à chaque niveau
    $unlocks = [1 => ['skinhead', 'batter'], 2 => ['masculinist', 'knifer'], 3 => ['chainer'], 4 => ['hooligan'], 5 => ['gymbro']];
    $pool = [];
    foreach ($unlocks as $level => $types) {
        if ($level <= $number) {
            array_push($pool, ...$types);
        }
    }

    mt_srand($number * 97);
    $waves = [];
    foreach ([0, 360, 760] as $i => $at) {
        $enemies = [];
        for ($e = 0, $count = 2 + intdiv($number, 3) + $i; $e < $count; $e++) {
            $enemies[] = $pool[mt_rand(0, count($pool) - 1)];
        }
        $waves[] = ['at' => $at, 'enemies' => $enemies];
    }

    $waves[] = ['at' => LEVEL_LENGTH - Scene::WIDTH, 'enemies' => [], 'boss' => $boss];

    return $waves;
}

$levels = [];
foreach ($album['tracks'] as $track) {
    $config = $levelConfig[$track['number']] ?? $levelConfig[1];
    $lyrics = array_map('strtoupper', $track['lyrics']);

    $levels[] = [
        'number' => $track['number'],
        'title' => strtoupper($track['title']),
        'feat' => $track['feat'] ? strtoupper($track['feat']) : null,
        'audio' => $track['audio'],
        'intro' => array_slice($lyrics, 0, 2),
        'shouts' => Album::phrases($track['lyrics'], 22, EXCLUDED_WORDS),
        'accent' => $config['theme']['accent'],
        'rain' => $config['theme']['rain'] ?? false,
        'fog' => $config['theme']['fog'] ?? false,
        'chaos' => $config['theme']['chaos'] ?? 0,
        'boss' => $config['boss'],
        'waves' => levelWaves($track['number'], $config['boss']),
        // caisse mystère avec le Wall of Death (une par niveau, cachée quelque part)
        'wodBox' => 560 + $track['number'] * 37,
        // otages à libérer (position x dans le niveau)
        'pows' => [480 + $track['number'] * 7, 1040 - $track['number'] * 5],
    ];
}

/** Le décor SVG d'un niveau (index de 0 à 8). */
function levelScene(array $album, array $levelConfig, array $props, int $index): string
{
    $track = $album['tracks'][$index];
    $config = $levelConfig[$track['number']] ?? $levelConfig[1];

    return (new Scene($config['theme'], levelTags($track, $config), $props))->render();
}

/** La ville libérée, pour la fin du jeu. */
function peaceScene(array $props): string
{
    $theme = [
        'seed' => 2026, 'sky' => 'paper', 'celestial' => 'sun', 'accent' => '#ffc62a', 'chaos' => 0, 'peace' => true,
        'signs' => ['LOVE', 'GRATUIT', 'JARDIN', 'CAFÉ', 'LIBRE'],
    ];
    $tags = ['MERCI PETE', 'LIBRES !', 'BYE BYE IA', 'TOUT EST GRATUIT', 'PEACE'];

    return (new Scene($theme, $tags, $props))->render();
}
