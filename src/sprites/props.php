<?php

/**
 * Petits éléments de décor posés sur le trottoir.
 * Même principe que le punk : texte -> grille -> SVG.
 */

$palette = [
    'K' => '#120a18',
    'R' => '#e8203a', 'r' => '#8f1424', 'H' => '#ff7a86', // bouche incendie
    'W' => '#b8b8c8', 'w' => '#6e6e82',                   // métal
    'C' => '#77736a', 'c' => '#4e4b45', 'D' => '#2e2c28', // poubelle
    'Y' => '#ffd23f', 'y' => '#c08a1a',                   // déchets
    'F' => '#c79a62', 'f' => '#8a6538',                   // carton
    'G' => '#3f8a3a', 'g' => '#a8e0a0',                   // bouteille
    'O' => '#4a5a2a', 'o' => '#7a8a40',                   // grenade
    'P' => '#1a1a22', 'p' => '#7fd4ff',                   // téléphone
    'L' => '#ff3ea5', 'l' => '#ffb0d8',                   // cœur
    'B' => '#e8b020', 'b' => '#f4f4f4',                   // canette de bière (bonus)
];

$sprites = [
    'hydrant' => [
        '...RR...',
        '..RHRr..',
        '.RRRRRr.',
        '..wWWw..',
        '.RHRRRr.',
        'WRHRRRrW',
        'wRHRRRrw',
        '.RHRRRr.',
        '.RHRRRr.',
        '.RRRRrr.',
        'wWWWWWWw',
    ],
    'trash' => [
        '.....Y......',
        '..WWWyWWWW..',
        '.WWWWWWWWWw.',
        '..DDDDDDDD..',
        '..CcCCcCCc..',
        '..CcCCcCCc..',
        '..CcCCcCCc..',
        '..CcCCcCCc..',
        '..DDDDDDDD..',
        '..CcCCcCCc..',
        '..CcCCcCCc..',
        '..CcCCcCCc..',
        '..CcCCcCCc..',
        '..DDDDDDDD..',
    ],
    'box' => [
        'FFFFFFFfFFF',
        'FFFFFFFfFFf',
        'fffffffffff',
        'FFFFFFFFFFf',
        'FFFKKFFFFFf',
        'FFFFFFFFFFf',
        'FFFFFFFFFFf',
    ],
];

// Projectiles lancés par les boss, et bonus
$items = [
    'bottle' => ['..g.', '..G.', '.GGG', '.GgG', '.GGG', '.GGG'],
    'grenade' => ['.WW.', 'OOOO', 'OoOO', 'OOOO', '.OO.'],
    'phone' => ['PPPP', 'PppP', 'PppP', 'PppP', 'PPPP'],
    'heart' => ['LL.LL', 'LlLLL', 'LLLLL', '.LLL.', '..L..'],
    'beer' => ['.WW.', 'BBBB', 'BbbB', 'BbbB', 'BBBB', 'BBBB'],
    // caisse de bombes (comme la caisse "B" de Metal Slug)
    'bombs' => ['ffffffff', 'fFFFFFFf', 'fFRRRFFf', 'fFRFFRFf', 'fFRRRFFf', 'fFRFFRFf', 'fFRRRFFf', 'ffffffff'],
];

$grids = [];
foreach ($sprites as $name => $rows) {
    $width = max(array_map('strlen', $rows));
    $grids[$name] = PixelArt::compose($width, count($rows), [['rows' => $rows]]);
}

foreach ($items as $name => $rows) {
    $grids[$name] = PixelArt::compose(strlen($rows[0]), count($rows), [['rows' => $rows]]);
}

return ['palette' => $palette, 'sprites' => $grids];
