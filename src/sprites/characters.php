<?php

/**
 * Personnages du jeu : le Vigilante (kimono, ceinture noire, katana, masque)
 * et les ennemis (skinhead, masculiniste).
 *
 * Tous partagent le même squelette : mêmes jambes, mêmes positions de bras,
 * seules les palettes, têtes et torses changent. Les lettres "génériques"
 * des calques partagés (A = pantalon, O = chaussures, H = manche...) sont
 * recolorées pour chaque personnage.
 *
 * Repère : le personnage regarde à droite, ses pieds sont centrés en x = 18.
 */

const SPRITE_HEIGHT = 40;
const ANCHOR_X = 19; // centre des pieds (avec la marge de 1 px du contour)
const ANCHOR_Y = 40; // sous les semelles

// ---------------------------------------------------------------------------
// Calques partagés
// ---------------------------------------------------------------------------

$legs = [
    'forward' => ['y' => 28, 'rows' => [
        '................AAAa',
        '................AAAa',
        '.................AAAa',
        '.................AAAa',
        '..................AAAa',
        '..................AAAa',
        '...................AAAa',
        '...................OOOo',
        '...................OOOOOo',
        '...................OOOOOOo',
        '...................XXXXXXX',
    ]],
    'back' => ['y' => 28, 'rows' => [
        '..............AAAa',
        '..............AAAa',
        '.............AAAa',
        '.............AAAa',
        '............AAAa',
        '...........AAAa',
        '..........AAAa',
        '.........OOOo',
        '.........OOOOOo',
        '.........OOOOOOo',
        '.........XXXXXXX',
    ]],
    'support' => ['y' => 28, 'rows' => [
        '...............AAAa',
        '...............AAAa',
        '...............AAAa',
        '...............AAAa',
        '...............AAAa',
        '...............AAAa',
        '...............AAAa',
        '...............OOOo',
        '...............OOOOOo',
        '...............OOOOOOo',
        '...............XXXXXXX',
    ]],
    'lifted' => ['y' => 28, 'rows' => [
        '...............AAAa',
        '................AAAa',
        '................AAAa',
        '................AAAa',
        '...............AAAa',
        '..............AAAa',
        '.............OOOo',
        '.............OOOOOo',
        '.............XXXXXX',
    ]],
];

// Bras des ennemis : H = manche, h = ombre, S = main
$arms = [
    'back' => ['y' => 18, 'rows' => [
        '................hHH',
        '...............hHH',
        '...............hHH',
        '..............hHH',
        '..............hHH',
        '.............SSs',
        '.............SSs',
    ]],
    'mid' => ['y' => 18, 'rows' => [
        '................hHH',
        '................hHH',
        '................hHH',
        '................hHH',
        '................hHH',
        '................SSs',
        '................SSs',
    ]],
    'front' => ['y' => 18, 'rows' => [
        '................hHH',
        '.................hHH',
        '.................hHH',
        '..................hHH',
        '..................hHH',
        '...................SSs',
        '...................SSs',
    ]],
    'punch' => ['y' => 18, 'rows' => [
        '................hHH',
        '.................HHHHHHHHSSS',
        '.................hhhhhhhhSSs',
    ]],
];

// Jambes du coup de pied sauté (héros) : jambe tendue et jambes repliées
$legs['kick'] = ['y' => 25, 'rows' => [
    '...........................OOO',
    '.................AAAAAAAAAAOOOX',
    '.................AAAAAAAAAAOOOX',
    '.................aaaaaaaaaaOOOX',
    '...........................OOO',
]];
$legs['tuck'] = ['y' => 27, 'rows' => [
    '..............AAA',
    '.............AAAa',
    '............AAAa',
    '..........AAAAAa',
    '.........OOOo',
    '........OOOOo',
    '........XXXX',
]];
$legs['tuckFront'] = ['y' => 27, 'rows' => [
    '...............AAAa',
    '................AAAa',
    '.................AAAa',
    '...............AAAAa',
    '..............OOOOo',
    '.............OOOOOo',
    '.............XXXXXX',
]];

$nearLeg = [];
$farLeg = ['A' => 'a', 'a' => 'v', 'O' => 'q', 'o' => 'O'];

// Cycle de marche commun
$walkCycle = [
    ['far' => 'back',    'near' => 'forward', 'nearArm' => 'back',  'farArm' => 'front', 'bob' => 1],
    ['far' => 'lifted',  'near' => 'support', 'nearArm' => 'mid',   'farArm' => 'mid',   'bob' => 0],
    ['far' => 'forward', 'near' => 'back',    'nearArm' => 'front', 'farArm' => 'back',  'bob' => 1],
    ['far' => 'support', 'near' => 'lifted',  'nearArm' => 'mid',   'farArm' => 'mid',   'bob' => 0],
];

/** Décale un calque (rebond du buste, penché en avant...). */
function shift(array $layer, int $dx = 0, int $dy = 0): array
{
    $layer['x'] = ($layer['x'] ?? 0) + $dx;
    $layer['y'] = ($layer['y'] ?? 0) + $dy;

    return $layer;
}

// ---------------------------------------------------------------------------
// Le Vigilante
// ---------------------------------------------------------------------------

$hero = (function () use ($legs, $nearLeg, $farLeg, $walkCycle) {
    $palette = [
        'K' => '#0c0a10',
        'M' => '#e8203a', 'm' => '#9a1224', 'P' => '#ff7a8a',  // casquette
        'Z' => '#3a2418',                                      // cheveux
        'S' => '#f2b48a', 's' => '#c27a52',                    // peau
        'G' => '#141018', 'w' => '#ffffff',                    // masque
        'Y' => '#ffd23f',                                      // boucle d'oreille
        'C' => '#f4f2ec', 'c' => '#bdbacb', 'e' => '#8d8a9e',  // kimono
        'D' => '#141018',                                      // ceinture noire
        'A' => '#ecebe4', 'a' => '#b5b2c4', 'v' => '#8a879c',  // pantalon
        'O' => '#2a2430', 'o' => '#4a4256', 'q' => '#18141e',  // rangers
        'X' => '#1e1a24',
        'N' => '#eef0f6', 'n' => '#8a8c9a', 'h' => '#2a1a20',  // katana : lame, reflet, poignée
        'k' => '#1a1a1e', 'r' => '#ffd23f', 'T' => '#9a9aa6', 'W' => '#f4f4f4', // skate
        '1' => '#e4e4ec', '2' => '#8a8a98', '3' => '#1c1a20', '4' => '#ffc62a', // armes ramassées
    ];

    // Main du bras de devant pour chaque pose, et pose d'arme correspondante
    $hands = ['guard' => [[18, 22], 'walk'], 'windup' => [[12, 21], 'windup'], 'strike' => [[22, 20], 'strike']];
    // les couleurs des armes (M, m, L, j) sont déjà prises par la casquette : on les renomme
    $weaponMap = ['M' => '1', 'm' => '2', 'L' => '3', 'j' => '4'];

    // Casquette de skateur à visière, masque noir noué (sans pans qui dépassent)
    $head = ['y' => 4, 'rows' => [
        '............MMMMMM',
        '..........MMMMMMMMP',
        '..........mMMMMMMMPM',
        '..........mMMMMMMMMMMMMM',
        '..........ZssSSSSSSSSmmm',
        '..........sssSSSSSSSSSS',
        '..........GGGGGGGGGGGGG',
        '..........GGGGGGwwGGGwG',
        '..........sSsSSSSSSSSSSS',
        '..........sYSSSSSSSSSSs',
        '...........YsSSSSSSKKs',
        '............ssSSSSSSS',
    ]];

    // Le skate porté dans le dos
    $boardOnBack = ['y' => 14, 'rows' => [
        '..........kr',
        '.........Tkr',
        '........WTkr',
        '.........Tkr',
        '..........kr',
        '..........kr',
        '..........kr',
        '..........kr',
        '..........kr',
        '..........kr',
        '..........kr',
        '.........Tkr',
        '........WTkr',
        '.........Tkr',
        '..........kr',
    ]];

    // Le skate sous les pieds (attaque en glisse)
    $boardUnder = ['y' => 36, 'rows' => [
        '.......k....................k',
        '........kkkkkkkkkkkkkkkkkkkk',
        '.........rrrrrrrrrrrrrrrrrr',
        '..........WW...........WW',
    ]];

    $torso = ['y' => 16, 'rows' => [
        '..............sssss',
        '............cCCessseCCc',
        '............cCCCeseCCCc',
        '............cCCCCeCCCCc',
        '............cCCCCCeCCCc',
        '............cCCCCCCeCCc',
        '............cCCCCCCCeCc',
        '............cCCCCCCCCCc',
        '............ccCCCCCCCcc',
        '.............DDDDDDDDD',
        '.............cCCCDCDCc',
        '.............cCCDCCDCc',
    ]];

    $poses = [
        'guard' => [
            // katana en garde, pointe vers le haut : poignée dans les mains, garde dorée, lame
            'sword' => [
                PixelArt::line(16, 26, 23, 19, 'h', 'h'),
                ['x' => 24, 'y' => 16, 'rows' => ['YY', 'YY']],
                PixelArt::line(26, 16, 38, 4, 'N', 'n'),
            ],
            'nearArm' => ['y' => 18, 'rows' => [
                '...............cCC',
                '................cCC',
                '................cCCC',
                '.................cCC',
                '..................SSs',
                '..................SSs',
            ]],
            'farArm' => ['y' => 17, 'rows' => [
                '....................cCC',
                '.....................cCC',
                '.......................SSs',
                '.......................SSs',
            ]],
        ],
        'windup' => [
            // katana armé en arrière, lame vers l'arrière
            'sword' => [
                PixelArt::line(11, 22, 20, 21, 'h', 'h'),
                ['x' => 9, 'y' => 21, 'rows' => ['Y', 'Y', 'Y']],
                PixelArt::line(0, 23, 8, 22, 'N', 'n'),
            ],
            'nearArm' => ['y' => 18, 'rows' => [
                '...............cCC',
                '..............cCC',
                '.............cCC',
                '............SSs',
                '............SSs',
            ]],
            'farArm' => ['y' => 18, 'rows' => [
                '..................cCC',
                '..................cCC',
                '..................cCC',
                '..................SSs',
                '..................SSs',
            ]],
        ],
        'strike' => [
            // coup tendu : la lame file vers l'avant
            'sword' => [
                PixelArt::line(19, 20, 29, 20, 'h', 'h'),
                ['x' => 30, 'y' => 19, 'rows' => ['Y', 'Y', 'Y', 'Y']],
                PixelArt::line(31, 20, 52, 20, 'N', 'n'),
            ],
            'nearArm' => ['y' => 18, 'rows' => [
                '...............cCC',
                '................cCCCCC',
                '.................cCCCCSSs',
                '......................SSs',
            ]],
            'farArm' => ['y' => 18, 'rows' => [
                '..................cCC',
                '...................cCCCCCCC',
                '............................SSs',
                '............................SSs',
            ]],
        ],
    ];

    // $weapon : arme ramassée sur un ennemi (à la place de son katana)
    $frame = function (string $far, string $near, string $pose, int $dx = 0, int $dy = 0, bool $riding = false, ?callable $weapon = null) use ($legs, $nearLeg, $farLeg, $head, $torso, $poses, $boardOnBack, $boardUnder, $hands, $weaponMap) {
        // sur la planche, tout le corps monte de 3 pixels
        $lift = $riding ? -3 : 0;
        $up = fn(array $layer) => shift($layer, $dx, $dy + $lift);

        // le katana de Pete, ou l'arme ramassée sur un ennemi
        $held = $poses[$pose]['sword'];
        if ($weapon) {
            [$hand, $weaponPose] = $hands[$pose];
            $held = [($weapon($weaponPose, $hand) ?? ['rows' => []]) + ['map' => $weaponMap]];
        }

        return PixelArt::compose(56, SPRITE_HEIGHT, [
            $riding ? $boardUnder : $up($boardOnBack),
            shift($legs[$far], 0, $lift) + ['map' => $farLeg],
            shift($legs[$near], 0, $lift) + ['map' => $nearLeg],
            $up($torso),
            $up($head),
            $up($poses[$pose]['farArm']),
            ...array_map($up, $held),
            $up($poses[$pose]['nearArm']),
        ]);
    };

    // Toutes les animations, avec le katana (ou avec une arme ramassée)
    $build = function (?callable $weapon = null) use ($frame, $walkCycle) {
        $walk = [];
        foreach ($walkCycle as $step) {
            $walk[] = $frame($step['far'], $step['near'], 'guard', 0, $step['bob'], false, $weapon);
        }

        return [
            'idle' => [$frame('back', 'forward', 'guard', 0, 1, false, $weapon), $frame('back', 'forward', 'guard', 0, 0, false, $weapon)],
            'walk' => $walk,
            'attack' => [$frame('back', 'forward', 'windup', -1, 1, false, $weapon), $frame('back', 'forward', 'strike', 2, 1, false, $weapon)],
            'skate' => [$frame('back', 'forward', 'strike', 0, 1, true, $weapon), $frame('back', 'forward', 'strike', 0, 1, true, $weapon)],
            // saut (jambes repliées) puis coup de pied tendu
            'jump' => [$frame('tuck', 'tuckFront', 'guard', 0, -2, false, $weapon)],
            'kick' => [$frame('tuck', 'kick', 'windup', 0, -1, false, $weapon)],
        ];
    };

    return ['palette' => $palette, 'frames' => $build(), 'build' => $build];
})();

// ---------------------------------------------------------------------------
// Ennemis
// ---------------------------------------------------------------------------

// Couleurs communes des armes, ajoutées à la palette de chaque ennemi
const WEAPON_COLORS = [
    'N' => '#c8955a', 'n' => '#8a5a30', // bois
    'M' => '#e4e4ec', 'm' => '#8a8a98', // métal
    'L' => '#1c1a20',                   // cuir, caoutchouc
    'j' => '#ffc62a',                   // or, bière
    'P' => '#1a1a22', 'p' => '#7fd4ff', // téléphone, bouclier
];

// Position de la main du bras de devant, pour chaque pose de bras
const HANDS = ['back' => [13, 23], 'mid' => [16, 23], 'front' => [19, 23], 'punch' => [25, 19]];

/** Place une petite grille de pixels à une position donnée. */
function sprite(int $x, int $y, array $rows): array
{
    return ['x' => $x, 'y' => $y, 'rows' => $rows];
}

/**
 * Les armes : pour chaque pose ('walk', 'windup' = armer, 'strike' = frapper)
 * on renvoie un calque positionné par rapport à la main [x, y].
 */
$weapons = [
    'bat' => fn(string $pose, array $h) => match ($pose) {
        'walk' => PixelArt::line($h[0] + 1, $h[1], $h[0] - 4, $h[1] - 14, 'N', 'n'),
        'windup' => PixelArt::line($h[0] + 1, $h[1], $h[0] - 10, $h[1] - 12, 'N', 'n'),
        'strike' => PixelArt::line($h[0] + 2, $h[1], $h[0] + 21, $h[1] - 2, 'N', 'n'),
    },
    'chain' => fn(string $pose, array $h) => match ($pose) {
        'walk' => PixelArt::line($h[0] + 1, $h[1] + 2, $h[0] + 2, $h[1] + 11, 'M', 'm'),
        'windup' => PixelArt::line($h[0], $h[1], $h[0] - 8, $h[1] - 13, 'M', 'm'),
        'strike' => PixelArt::line($h[0] + 3, $h[1], $h[0] + 24, $h[1] + 3, 'M', 'm'),
    },
    'knife' => fn(string $pose, array $h) => match ($pose) {
        'walk' => PixelArt::line($h[0] + 1, $h[1] + 2, $h[0] + 1, $h[1] + 6, 'M', 'm'),
        'windup' => PixelArt::line($h[0], $h[1] - 1, $h[0] - 2, $h[1] - 6, 'M', 'm'),
        'strike' => PixelArt::line($h[0] + 3, $h[1], $h[0] + 9, $h[1], 'M', 'm'),
    },
    'dumbbell' => function (string $pose, array $h) {
        $rows = ['LL....LL', 'LLmMMmLL', 'LLmMMmLL', 'LL....LL'];
        return match ($pose) {
            'walk' => sprite($h[0] - 2, $h[1] - 1, $rows),
            'windup' => sprite($h[0] - 4, $h[1] - 11, $rows),
            'strike' => sprite($h[0] + 1, $h[1] - 1, $rows),
        };
    },
    'bottle' => fn(string $pose, array $h) => match ($pose) {
        'walk' => sprite($h[0], $h[1] - 5, ['.n.', '.N.', 'NjN', 'NNN', 'NNN']),
        'windup' => sprite($h[0] - 3, $h[1] - 13, ['.n.', '.N.', 'NjN', 'NNN', 'NNN']),
        'strike' => null, // la bouteille est partie
    },
    // armes des boss
    'briefcase' => function (string $pose, array $h) {
        $rows = ['..LLL..', 'LLLLLLL', 'LLLjLLL', 'LLLLLLL', 'LLLLLLL'];
        return match ($pose) {
            'walk' => sprite($h[0] - 2, $h[1] + 2, $rows),
            'windup' => sprite($h[0] - 7, $h[1] - 8, $rows),
            'strike' => sprite($h[0] + 2, $h[1] - 2, $rows),
        };
    },
    'mug' => function (string $pose, array $h) {
        $rows = ['MMMMM.', 'jjjjjL', 'jMjjj.L', 'jjjjjL', 'jjjjj.'];
        return match ($pose) {
            'walk' => sprite($h[0] - 1, $h[1] - 4, $rows),
            'windup' => sprite($h[0] - 5, $h[1] - 13, $rows),
            'strike' => sprite($h[0] + 2, $h[1] - 3, $rows),
        };
    },
    'baton' => fn(string $pose, array $h) => match ($pose) {
        'walk' => PixelArt::line($h[0] + 1, $h[1], $h[0] + 1, $h[1] + 10, 'L', 'L'),
        'windup' => PixelArt::line($h[0], $h[1], $h[0] - 6, $h[1] - 12, 'L', 'L'),
        'strike' => PixelArt::line($h[0] + 3, $h[1], $h[0] + 16, $h[1] - 1, 'L', 'L'),
    },
    'cane' => fn(string $pose, array $h) => match ($pose) {
        'walk' => PixelArt::line($h[0] + 1, $h[1], $h[0] + 3, $h[1] + 14, 'j', 'n'),
        'windup' => PixelArt::line($h[0], $h[1], $h[0] - 7, $h[1] - 12, 'j', 'n'),
        'strike' => PixelArt::line($h[0] + 3, $h[1], $h[0] + 19, $h[1] - 2, 'j', 'n'),
    },
    'phone' => fn(string $pose, array $h) => match ($pose) {
        'walk', 'windup' => sprite($h[0], $h[1] - 4, ['PPP', 'PpP', 'PpP', 'PPP']),
        'strike' => null,
    },
    'katana' => fn(string $pose, array $h) => match ($pose) {
        'walk' => PixelArt::line($h[0] + 1, $h[1] + 1, $h[0] - 12, $h[1] + 12, 'M', 'm'),
        'windup' => PixelArt::line($h[0], $h[1], $h[0] - 10, $h[1] - 18, 'M', 'm'),
        'strike' => PixelArt::line($h[0] + 3, $h[1], $h[0] + 28, $h[1] - 2, 'M', 'm'),
    },
];

/**
 * Construit toutes les frames d'un ennemi à partir de sa tête, son torse et sa palette.
 *
 * $options :
 *   weapon => callable (voir $weapons)
 *   back   => calques derrière le corps (cape...)
 *   over   => calques par-dessus le torse/la tête (ceinture, chapeau, casque audio...)
 *   front  => calques tout devant (bouclier...)
 */
function enemy(array $palette, array $head, array $torso, array $armColors, array $legs, array $arms, array $walkCycle, array $farLeg, array $options = []): array
{
    // couleurs des mains : 'S' par défaut, ou gants, etc.
    $hand = $armColors['S'] ?? 'S';
    $farHand = $armColors['s'] ?? 's';
    $farArmColors = ['H' => $armColors['h'], 'h' => $armColors['h'], 'S' => $farHand, 's' => $farHand];
    $nearArmColors = ['H' => $armColors['H'], 'h' => $armColors['h'], 'S' => $hand, 's' => $farHand];
    $weapon = $options['weapon'] ?? null;

    $frame = function (string $far, string $near, string $nearArm, string $farArm, string $pose, int $dx = 0, int $dy = 0, bool $armed = true) use ($head, $torso, $legs, $arms, $farLeg, $farArmColors, $nearArmColors, $weapon, $options) {
        $up = fn(array $layer) => shift($layer, $dx, $dy);
        $layers = array_map($up, $options['back'] ?? []);

        $layers[] = $up(shift($arms[$farArm], 3)) + ['map' => $farArmColors];
        $layers[] = $legs[$far] + ['map' => $farLeg];
        $layers[] = $legs[$near];
        $layers[] = $up($torso);
        $layers[] = $up($head);
        array_push($layers, ...array_map($up, $options['over'] ?? []));
        $layers[] = $up($arms[$nearArm]) + ['map' => $nearArmColors];

        if ($armed && $weapon && ($held = $weapon($pose, HANDS[$nearArm]))) {
            $layers[] = $up($held);
        }
        array_push($layers, ...array_map($up, $options['front'] ?? []));

        return PixelArt::compose(52, SPRITE_HEIGHT, $layers);
    };

    $walk = [];
    foreach ($walkCycle as $step) {
        $walk[] = $frame($step['far'], $step['near'], $step['nearArm'], $step['farArm'], 'walk', 0, $step['bob']);
    }

    return [
        'palette' => $palette + WEAPON_COLORS,
        'frames' => [
            'idle' => [$frame('back', 'forward', 'mid', 'mid', 'walk', 0, 1), $frame('back', 'forward', 'mid', 'mid', 'walk')],
            'walk' => $walk,
            'attack' => [$frame('back', 'forward', 'back', 'front', 'windup', -1, 1), $frame('back', 'forward', 'punch', 'back', 'strike', 2, 1)],
            // au tapis : l'arme a été lâchée
            'dead' => [$frame('back', 'forward', 'mid', 'mid', 'walk', 0, 0, false)],
        ],
    ];
}

// --- Skinheads --------------------------------------------------------------

$skinPalette = [
    'K' => '#0c0a10',
    'S' => '#e9b08a', 's' => '#b9785a', 'r' => '#c0303a',  // peau, cicatrice
    'C' => '#4f6b38', 'c' => '#33482a', 'R' => '#e07a22',  // bomber, doublure orange
    'z' => '#c0c0c8', 'D' => '#24301a',
    'A' => '#7a98c2', 'a' => '#53719c', 'v' => '#34507a',  // jean délavé
    'U' => '#b0202a',                                      // bretelles
    'O' => '#6a1a24', 'o' => '#9a2a36', 'q' => '#3a0e14',  // coquées bordeaux
    'X' => '#e8c84a',                                      // semelle jaune
];

$skinHead = ['y' => 6, 'rows' => [
    '............sssSSSs',
    '..........sssSSSSSSSs',
    '..........ssSSSSSSSSSS',
    '..........sSSrSSSSSSSSS',
    '..........sSSSrKKKSSKKS',
    '..........SssSSSSSSKSSK',
    '..........SssSSSSSSSSSSS',
    '..........sSsSSSSSSSSSs',
    '...........sSSSSSSKKKKs',
    '............ssSSSSSSS',
]];

$skinTorso = ['y' => 16, 'rows' => [
    '..............sssss',
    '............cCRRRRRRCCc',
    '............cCCCCzCCCCc',
    '............cCCCCzCCCCc',
    '............cCCCCzCCCCc',
    '............cCCCCzCCCCc',
    '............cCCCCzCCCCc',
    '............cCCCCzCCCCc',
    '............cCCCCzCCCCc',
    '............DDDDDDDDDDD',
    '.............AUAAAAAUa',
    '.............AUAAAAAUa',
]];

$beanie = ['y' => 4, 'rows' => [
    '.............UUUU',
    '...........UUUUUUUU',
    '..........UUUUUUUUUUU',
    '..........RRRRRRRRRRRR',
]];

$skin = fn(array $colors, array $options = [], ?array $head = null) => enemy(
    $colors + $skinPalette, $head ?? $skinHead, $skinTorso, ['H' => 'C', 'h' => 'c'], $legs, $arms, $walkCycle, $farLeg, $options
);

$skinhead = $skin([]);
$batter = $skin(['C' => '#2a2a32', 'c' => '#18181e'], ['weapon' => $weapons['bat']]);
$chainer = $skin(['C' => '#5a1a24', 'c' => '#3a0e16', 'A' => '#2a2a32', 'a' => '#1a1a20', 'v' => '#101014'], ['weapon' => $weapons['chain']]);
$hooligan = $skin(['C' => '#2e4a9a', 'c' => '#1e3270', 'R' => '#f4f4f4'], ['weapon' => $weapons['bottle'], 'over' => [$beanie]]);

// --- Masculinistes ----------------------------------------------------------

$mascPalette = [
    'K' => '#0c0a10',
    'S' => '#d99a6c', 's' => '#a96a44',                    // peau (bras nus)
    'Q' => '#18161e', 'I' => '#3c3846',                    // casquette
    'G' => '#101018', 'g' => '#7aa0ff',                    // lunettes de soleil
    'E' => '#3a2418',                                      // barbe
    'C' => '#a2a2ae', 'c' => '#72727f', 'Y' => '#ffd23f',  // débardeur, chaîne en or
    'W' => '#f0f0f0',
    'A' => '#2c2c38', 'a' => '#1e1e28', 'v' => '#15151d',  // jogging
    'O' => '#ececf2', 'o' => '#b8b8c8', 'q' => '#8a8a9a',  // baskets blanches
    'X' => '#d0d0da',
];

$mascHead = ['y' => 4, 'rows' => [
    '............QQQQQQ',
    '..........QQIQQQQQQ',
    '..........QQIQQQQQQQ',
    '..........QQQQQQQQQQQQQQ',
    '..........sSSSSSSSSSS',
    '..........sSSSSSSSSSSS',
    '..........sSSSSGGGGGGGG',
    '..........SsSSSGgGGGgGG',
    '..........SsESSSSSSSSSSS',
    '..........sEEEESSSEEEEs',
    '...........EEEEEEEEEEEE',
    '............EEEEEEEEEE',
    '..............EEEEEE',
]];

$mascTorso = ['y' => 16, 'rows' => [
    '..............sssss',
    '...........SSCCYYYYCCSS',
    '...........SSCCCYYCCCSS',
    '............cCCCCCCCCCc',
    '............cCCCCCCCCCc',
    '............cCCCCCCCCCc',
    '............cCCCCCCCCCc',
    '............cCCCCCCCCCc',
    '............ccCCCCCCCcc',
    '.............AAAAWWAAa',
    '.............AAAAAWAAa',
    '.............AAAAAAAAa',
]];

$masc = fn(array $colors, array $options = []) => enemy(
    $colors + $mascPalette, $mascHead, $mascTorso, ['H' => 'S', 'h' => 's'], $legs, $arms, $walkCycle, $farLeg, $options
);

$masculinist = $masc([]);
$knifer = $masc(['C' => '#ececf2', 'c' => '#a8a8b8', 'Q' => '#b0202a', 'I' => '#e04050'], ['weapon' => $weapons['knife']]);
$gymbro = $masc(['S' => '#c8804e', 's' => '#93562e', 'C' => '#1c1c24', 'c' => '#101016', 'A' => '#3a1a5a', 'a' => '#28103e'], ['weapon' => $weapons['dumbbell']]);

// ---------------------------------------------------------------------------
// Armes ramassables : le héros peut reprendre l'arme d'un ennemi mis K.O.
// ---------------------------------------------------------------------------

const PICKABLE_WEAPONS = ['bat', 'chain', 'knife', 'dumbbell'];

$heroArmed = [];
$weaponIcons = [];
foreach (PICKABLE_WEAPONS as $name) {
    // le héros avec cette arme en main
    $heroArmed["hero-$name"] = ['palette' => $hero['palette'], 'frames' => ($hero['build'])($weapons[$name])];

    // l'arme posée au sol (on rogne les colonnes vides)
    $grid = PixelArt::compose(34, 10, [$weapons[$name]('strike', [0, 5])]);
    $used = array_filter(range(0, strlen($grid[0]) - 1), fn($x) => array_filter($grid, fn($row) => $row[$x] !== '.'));
    $weaponIcons[$name] = array_map(fn($row) => substr($row, min($used), max($used) - min($used) + 1), $grid);
}
unset($hero['build']);

// ---------------------------------------------------------------------------
// Otage ligoté (comme les prisonniers de Metal Slug) : un pote punk à libérer
// ---------------------------------------------------------------------------

$powPalette = [
    'K' => '#0c0a10', 'G' => '#7dff5a', 'g' => '#3aa02a', 'S' => '#efb48c', 's' => '#bb7a56',
    'J' => '#26262e', 'j' => '#4a4a58', 'W' => '#dfe3f0', 'r' => '#d8c08a', 'R' => '#9a7a4a',
    'A' => '#3d63d6', 'a' => '#26408f', 'O' => '#2a2430', 'X' => '#1e1a24',
];
$powBody = [
    '.....JJJJJJJ',
    '....JjrrrrrJJ',
    '....JJJJJJJJJ',
    '....JjRrrrrRJ',
    '....JJJJJJJJJ',
    '.....AAAAAAAAA',
    '.....AAAAAAAAAA',
    '..OOOAAAAAAAAAA',
    '.OOOOOaaa.OOOO',
    '.XXXXX....XXXX',
];
$powHead = fn(bool $shout) => [
    '.......G.G.G',
    '......gGGGGG',
    '.......GGGG',
    '......SSSSS',
    '.....SSSSSSs',
    '.....SKSSKSs',
    '.....SSSSSSs',
    $shout ? '......SKKKs' : '......SSKSs',
    '.......sss',
];
$pow = [
    'palette' => $powPalette,
    'frames' => [
        'tied' => [
            PixelArt::compose(20, 20, [['y' => 10, 'rows' => $powBody], ['y' => 1, 'rows' => $powHead(true)]]),
            PixelArt::compose(20, 20, [['y' => 10, 'rows' => $powBody], ['x' => 1, 'y' => 1, 'rows' => $powHead(false)]]),
        ],
    ],
];

// ---------------------------------------------------------------------------
// Boss : un par morceau de l'album
// ---------------------------------------------------------------------------

$bossSkin = ['K' => '#0c0a10', 'S' => '#efb48c', 's' => '#bb7a56'];
$boss = fn(array $palette, array $head, array $torso, array $armColors, array $options = []) => enemy($bossSkin + $palette, $head, $torso, $armColors, $legs, $arms, $walkCycle, $farLeg, $options);

$bosses = [];

// 01 Walk Straight : costard, lunettes, cravate rouge
$bosses['manager'] = $boss(
    [
        'H' => '#3a2a20', 'G' => '#0c0a10', 'g' => '#9fd8ff',
        'C' => '#3a3a44', 'c' => '#26262e', 'W' => '#f2f2f2', 'R' => '#d01c32', 'Y' => '#c8c8d0', 'D' => '#141418',
        'A' => '#3a3a44', 'a' => '#26262e', 'v' => '#1a1a20',
        'O' => '#141418', 'o' => '#3a3a44', 'q' => '#0a0a0c', 'X' => '#0a0a0c',
    ],
    ['y' => 5, 'rows' => [
        '...........HHHHHHHH',
        '..........HHHHHHHHHHH',
        '..........HHHHHHHHHHHH',
        '..........HHSSSSSSSSSS',
        '..........HSSSSSSSSSSSS',
        '..........HSSSGGGGSGGGG',
        '..........SssSGgGGSGgGS',
        '..........SssSSSSSSSSSSS',
        '..........sSsSSSSSSSSSs',
        '...........sSSSSSSSKKKs',
        '............ssSSSSSSS',
    ]],
    ['y' => 16, 'rows' => [
        '..............sssss',
        '............cCCWWRWWCCc',
        '............cCCCWRWCCCc',
        '............cCCCWRWCCCc',
        '............cCCCCRCCCCc',
        '............cCCCCRCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCYCCCCc',
        '............cCCCCCCCCCc',
        '............ccCCCYCCCcc',
        '.............DDDDDDDDD',
        '.............AAAAAAAAa',
    ]],
    ['H' => 'C', 'h' => 'c'],
    ['weapon' => $weapons['briefcase']]
);

// 02 Metal Slug : béret rouge, moustache, médailles
$bosses['general'] = $boss(
    [
        'B' => '#c0202e', 'b' => '#801420', 'E' => '#2a1a12',
        'C' => '#7a7a48', 'c' => '#555530', 'Y' => '#ffd23f', 'R' => '#d01c32', 'D' => '#3a2a18',
        'A' => '#6a6a3c', 'a' => '#4c4c2a', 'v' => '#33331c',
        'O' => '#2a1c14', 'o' => '#4a3424', 'q' => '#160e0a', 'X' => '#0a0a0c',
    ],
    ['y' => 4, 'rows' => [
        '...........BBBBBBB',
        '..........BBBBBBBBBB',
        '.........bBBBBBBBBBBB',
        '.........b.sSSSSSSSSS',
        '..........ssSSSSSSSSSS',
        '..........sSSSSSSSSSSSS',
        '..........sSSSSKKKSSKKS',
        '..........SssSSSSSSKSSK',
        '..........SssSSSSSSSSSSS',
        '..........sSsSSSEEEEEEs',
        '...........sSSSSSEEEEEs',
        '............ssSSSSSSS',
    ]],
    ['y' => 16, 'rows' => [
        '..............sssss',
        '............cCCCCCCCCCc',
        '............cCYRCCCCCCc',
        '............cCRYCCCCCCc',
        '............cCCCCYCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCYCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCYCCCCc',
        '............ccCCCCCCCcc',
        '.............DDDDYDDDD',
        '.............AAAAAAAAa',
    ]],
    ['H' => 'C', 'h' => 'c'],
    ['back' => [
        // bazooka porté sur l'épaule, derrière la tête
        ['y' => 15, 'rows' => [
            '.mm.LLLLLLLLLLLLLLLLLLLLLLLLmmm',
            'mmmmLLLLLLLLLLLLLLLLLLLLLLLLmmm',
            '.mm.LLLLLLLLLLLLLLLLLLLLLLLLmmm',
        ]],
    ], 'over' => [
        // épaulettes dorées
        ['y' => 17, 'rows' => ['...........jjj.......jjj', '...........j.j.......j.j']],
    ]]
);

// 03 Nightmare : ton double, en version ombre (même sprite que le héros, autre palette)
$bosses['nightmare'] = [
    'palette' => [
        'K' => '#05030a',
        'M' => '#2a1d3a', 'm' => '#160e22', 'P' => '#4a3466',
        'Z' => '#3a2c4a', 'S' => '#8a7a9a', 's' => '#5e4e70',
        'G' => '#05030a', 'w' => '#c53cff', 'Y' => '#9b4dff',
        'C' => '#3a2a52', 'c' => '#261a38', 'e' => '#9b4dff', 'D' => '#c53cff',
        'A' => '#3a2a52', 'a' => '#261a38', 'v' => '#160e22',
        'O' => '#120a1a', 'o' => '#2a1d3a', 'q' => '#05030a', 'X' => '#05030a',
        'N' => '#c58aff', 'n' => '#6a4a8a', 'h' => '#05030a', // katana violet du double maléfique
        'k' => '#05030a', 'r' => '#c53cff', 'T' => '#3a2a52', 'W' => '#8a7a9a',
    ],
    'frames' => $hero['frames'],
];

// 04 Beer Church : soutane, col blanc, nez rouge
$bosses['priest'] = $boss(
    [
        'H' => '#9a9aa2', 'r' => '#e0303e', 'W' => '#f4f4f4', 'g' => '#5a5a66',
        'C' => '#1c1a22', 'c' => '#0e0d12', 'Y' => '#ffb020',
        'A' => '#1c1a22', 'a' => '#0e0d12', 'v' => '#08070a',
        'O' => '#0e0d12', 'o' => '#2a2830', 'q' => '#050506', 'X' => '#050506',
    ],
    ['y' => 6, 'rows' => [
        '............sssSSSs',
        '..........HHsSSSSSSSs',
        '..........HHSSSSSSSSSS',
        '..........HSSSSSSSSSSSS',
        '..........HSSSSKKKSSKKS',
        '..........SssSSSSSSKSSK',
        '..........SssSSSSSSSSSrr',
        '..........sSsSSSSSSSSSr',
        '...........sSSSSSSKKKKs',
        '............ssSSSSSSS',
    ]],
    ['y' => 16, 'rows' => [
        '..............WWWWW',
        '............cCCCWWWCCCc',
        '............cCCCCgCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCgCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCgCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCgCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCgCCCCc',
        '............cCCCCCCCCCc',
    ]],
    ['H' => 'C', 'h' => 'c'],
    ['weapon' => $weapons['mug'], 'over' => [
        // gros ventre de buveur
        ['x' => 10, 'y' => 20, 'rows' => [
            '...CCCCCCC',
            '..CCCCCCCCC',
            '.CCCCCCCCCCC',
            '.CCCCCgCCCCCc',
            '.CCCCCCCCCCCc',
            '..CCCCgCCCCc',
            '...cccccccc',
        ]],
    ]]
);

// 05 Black Belt : combattant torse nu, gants rouges
$bosses['champion'] = $boss(
    [
        'H' => '#1a1210', 'W' => '#f4f4f4', 'R' => '#d01c32', 'r' => '#8a0f1f',
        'A' => '#d01c32', 'a' => '#8a0f1f', 'v' => '#5a0814',
        'O' => '#f0f0f0', 'o' => '#c0c0c8', 'q' => '#8a8a94', 'X' => '#d01c32',
    ],
    ['y' => 6, 'rows' => [
        '...........HHHHHHH',
        '..........HHHHHHHHHH',
        '..........HsSSSSSSSSS',
        '..........sSSSSSSSSSSSS',
        '..........sSSSSKKKSSKKS',
        '..........SssSSSSSSKSSK',
        '..........SssSSSSSSSSSSS',
        '..........sSsSSSSSSSSSs',
        '...........sSSSSSSKKKKs',
        '............ssSSSSSSS',
    ]],
    ['y' => 16, 'rows' => [
        '..............sssss',
        '............sSSSSSSSSSs',
        '............sSSsSSsSSSs',
        '............sSSSsSSSSSs',
        '............sSSSSsSSSSs',
        '............sSSsSsSsSSs',
        '............sSSSSsSSSSs',
        '............sSSsSsSsSSs',
        '............sSSSSSSSSSs',
        '............ssSSSSSSSss',
        '.............WWWWWWWWW',
        '.............AAAAAAAAa',
    ]],
    ['H' => 'S', 'h' => 's', 'S' => 'R', 's' => 'r'],
    ['over' => [
        // ceinture de champion
        ['x' => 11, 'y' => 25, 'rows' => ['jjjjjjjjjjjj', 'jjjjMMMMjjjj', 'jjjjjjjjjjjj']],
    ]]
);

// 06 I'm Bored : sweat à capuche, visage éclairé par l'écran
$bosses['troll'] = $boss(
    [
        'S' => '#b8c8e0', 's' => '#7f90ac',
        'C' => '#5a5a64', 'c' => '#3e3e46', 'W' => '#e8e8e8',
        'A' => '#2c3a52', 'a' => '#1e2a3c', 'v' => '#121a26',
        'O' => '#e8e8ee', 'o' => '#b0b0bc', 'q' => '#7a7a86', 'X' => '#4da6ff',
    ],
    ['y' => 4, 'rows' => [
        '............CCCCCC',
        '..........CCCCCCCCC',
        '.........CCCCCCCCCCC',
        '.........CCCCcccccCCC',
        '.........CCCcSSSSSSSC',
        '.........CCcSSSSSSSSSS',
        '.........CCcSSSKKKSSKKS',
        '.........CCcSSSSSSSKSSK',
        '.........CCcSSSSSSSSSSSS',
        '.........CCcsSSSSSSSSSs',
        '..........CCcSSSSSKKKKs',
        '...........CCcsSSSSSS',
    ]],
    ['y' => 16, 'rows' => [
        '.............CsssssC',
        '............cCCCWCWCCCc',
        '............cCCCWCWCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCCCCCCc',
        '............cCCcccccCCc',
        '............cCCcCCCcCCc',
        '............cCCcccccCCc',
        '............cCCCCCCCCCc',
        '............ccCCCCCCCcc',
        '.............AAAAAAAAa',
        '.............AAAAAAAAa',
    ]],
    ['H' => 'C', 'h' => 'c'],
    ['weapon' => $weapons['phone'], 'over' => [
        // énorme casque audio
        ['y' => 2, 'rows' => [
            '...........PPPPPPPP',
            '.........PP........P',
        ]],
        ['y' => 9, 'rows' => ['........PPP', '.......PPpP', '.......PPpP', '........PPP']],
    ]]
);

// 07 Skate is a Drug : casque à visière, uniforme
$bosses['cop'] = $boss(
    [
        'B' => '#1c2a4a', 'g' => '#6a8ab0',
        'C' => '#24345a', 'c' => '#16223e', 'Y' => '#ffd23f', 'D' => '#0c0c10',
        'A' => '#1c2a4a', 'a' => '#121c32', 'v' => '#0a1020',
        'O' => '#0c0c10', 'o' => '#2a2a34', 'q' => '#050506', 'X' => '#050506',
    ],
    ['y' => 4, 'rows' => [
        '............BBBBBBB',
        '..........BBBBBBBBBB',
        '..........BBBBBBBBBBBB',
        '..........BBBBBBBBBBBBB',
        '..........BBBBgggggggggg',
        '..........BBBBgSSSSSSSgg',
        '..........BBBBgSKKKSSKKg',
        '..........BBBsgSSSSSSKSg',
        '..........BBBsgSSSSSSSSSg',
        '..........sBBsgggggggggg',
        '...........sSSSSSSKKKKs',
        '............ssSSSSSSS',
    ]],
    ['y' => 16, 'rows' => [
        '..............sssss',
        '............cCCCCCCCCCc',
        '............cCCCCCCYCCc',
        '............cCCCCCCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCCCCCCc',
        '............ccCCCCCCCcc',
        '.............DDDDYDDDD',
        '.............AAAAAAAAa',
    ]],
    ['H' => 'C', 'h' => 'c', 'S' => 'D', 's' => 'D'],
    ['weapon' => $weapons['baton'], 'front' => [
        // bouclier anti-émeute
        ['x' => 21, 'y' => 13, 'rows' => [
            'mmmmmmm',
            'mpppppm',
            'mpppppm',
            'mpMpppm',
            'mpMpppm',
            'mpppppm',
            'mLLLLLm',
            'mLMLMLm',
            'mLLLLLm',
            'mpppppm',
            'mpppppm',
            'mpppppm',
            'mpppppm',
            'mpppppm',
            'mpppppm',
            'mmmmmmm',
        ]],
    ]]
);

// 08 Junkie Heart : manteau de fourrure, lunettes roses, dents en or
$bosses['dealer'] = $boss(
    [
        'S' => '#a8704a', 's' => '#7a4c30',
        'H' => '#120c0a', 'G' => '#ff3ea5', 'g' => '#ffc2e2', 'Y' => '#ffd23f',
        'C' => '#5a2a7a', 'c' => '#3c1a52', 'F' => '#ff8ac8', 'W' => '#f4f4f4',
        'A' => '#2a1a3a', 'a' => '#1c1028', 'v' => '#100818',
        'O' => '#f4f4f4', 'o' => '#c8c8d0', 'q' => '#8a8a94', 'X' => '#ffd23f',
    ],
    ['y' => 4, 'rows' => [
        '...........HHHHHHH',
        '.........HHHHHHHHHHH',
        '.........HHHHHHHHHHHH',
        '.........HHHHHHHHHHHHH',
        '..........HHSSSSSSSSSS',
        '..........HSSSSSSSSSSSS',
        '..........HSSSGGGGGGGGG',
        '..........SssSGgGGGgGGS',
        '..........SssSSSSSSSSSSS',
        '..........sSsSSSSSSSSSs',
        '...........sSSSSSSYYYYs',
        '............ssSSSSSSS',
    ]],
    ['y' => 16, 'rows' => [
        '............FFsssssFF',
        '...........FFFCWWWWCFFF',
        '............FCCWYYWWCCF',
        '............CCCWWYWWCCc',
        '............CCCWWWYWCCc',
        '............CCCWWWWWCCc',
        '............CCCCWWWCCCc',
        '............CCCCCCCCCCc',
        '............CCCCCCCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCCCCCCc',
    ]],
    ['H' => 'C', 'h' => 'c'],
    ['weapon' => $weapons['cane'], 'over' => [
        // chapeau à plume
        ['y' => 0, 'rows' => [
            '.............CCCCCC..F',
            '.............CCCCCC.FF',
            '.............YYYYYYFF',
            '.........CCCCCCCCCCCCCC',
        ]],
    ]]
);

// 09 Behind the Mask : le Docteur Mask, blouse de labo, masque blanc aux yeux rouges, cape
$bosses['mask'] = $boss(
    [
        'H' => '#0c0a10', 'W' => '#f2efe6', 'w' => '#b8b4a8', 'R' => '#ff1e3c',
        'C' => '#dcdce2', 'c' => '#9a9aa8', 'T' => '#d01c32', 'D' => '#050506', // blouse blanche, chemise noire
        'A' => '#18161c', 'a' => '#0c0b0e', 'v' => '#050506',
        'O' => '#050506', 'o' => '#2a2830', 'q' => '#020203', 'X' => '#020203',
    ],
    ['y' => 5, 'rows' => [
        '...........HHHHHHHH',
        '..........HHHHHHHHHHH',
        '..........HHHHWWWWWWWW',
        '..........HHHWWWWWWWWW',
        '..........HHWWWWWWWWWWW',
        '..........HHWWWKKKWWKKW',
        '..........HHWWWKRKWWKRW',
        '..........HHWWWWWWWWWWWW',
        '..........HHwWWWWWWWWWw',
        '...........HwWWWKKKKKWw',
        '............wwWWWWWWW',
    ]],
    ['y' => 16, 'rows' => [
        '..............sssss',
        '............cCCDDTDDCCc',
        '............cCCCDTDCCCc',
        '............cCCCCTCCCCc',
        '............cCCCCTCCCCc',
        '............cCCCCTCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCCCCCCc',
        '............cCCCCCCCCCc',
        '............ccCCCCCCCcc',
        '.............DDDDDDDDD',
        '.............AAAAAAAAa',
    ]],
    ['H' => 'C', 'h' => 'c', 'S' => 'W', 's' => 'w'],
    ['weapon' => $weapons['katana'], 'back' => [
        // longue cape noire doublée de rouge
        ['y' => 16, 'rows' => [
            '............RLL',
            '............RLL',
            '............RLL',
            '...........RLLL',
            '...........RLLL',
            '..........RLLLL',
            '..........RLLLL',
            '..........RLLLL',
            '.........RLLLLL',
            '.........RLLLLL',
            '........RLLLLLL',
            '........RLLLLLL',
            '.......RLLLLLLL',
            '.......RLLLLLLL',
            '.......RLLLLLLL',
            '......RLLLLLLLL',
            '......RLLLLLLLL',
            '.....RLLLLLLLLL',
            '.....RLLLLLLLLL',
            '.....RLLLLLLLLL',
            '....RLLLLLLLLLL',
            '....RLLLLLLLLLL',
        ]],
    ]]
);

return [
    'anchor' => ['x' => ANCHOR_X, 'y' => ANCHOR_Y],
    'weaponIcons' => ['palette' => WEAPON_COLORS + ['K' => '#0c0a10'], 'sprites' => $weaponIcons],
    'hero' => $hero,
    'skinhead' => $skinhead,
    'masculinist' => $masculinist,
    'pow' => $pow,
    'batter' => $batter,
    'chainer' => $chainer,
    'hooligan' => $hooligan,
    'knifer' => $knifer,
    'gymbro' => $gymbro,
] + $heroArmed + $bosses;
