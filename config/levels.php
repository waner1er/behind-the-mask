<?php

/**
 * Un niveau par morceau de l'album, indexé par le numéro de piste de paroles.md.
 *
 * theme : ambiance du décor (voir Vigilante\Scene\Theme). chaos (0 à 1) : la ville part
 *         en ruine au fil de l'album (incendies, fumée, épaves...).
 * tags  : graffitis prioritaires (le reste est pioché dans les paroles).
 * boss  : sprite (voir Vigilante\Sprite\Boss\BossRoster) et comportement.
 *         scale = taille, multiple de 0.5 pour garder des pixels nets.
 *         special = summon (appelle des sbires), throw (lance des projectiles),
 *                   charge (fonce à travers l'écran), teleport (réapparaît dans ton dos),
 *                   déclenché toutes les « every » images.
 *
 * Options d'un niveau plus long (sinon : 5 écrans, 3 vagues tirées au sort puis le boss) :
 * length   : longueur de la rue, en écrans.
 * stages   : les vagues dans l'ordre, réparties le long de la rue, puis le boss au bout.
 *            enemies = nombre d'ennemis tirés au sort, extra = ennemis imposés en plus
 *            (même pas encore débloqués), reinforcements = renforts quand il n'en reste
 *            qu'un debout, pincer = les attaquants se placent de part et d'autre du héros.
 *            'midboss' = le boss de mi-niveau.
 * midboss  : un boss comme les autres (score = points rapportés, 5000 par défaut).
 *
 * bonus    : le niveau est une casse façon Street Fighter II (pas de vagues ni de boss) : casser un objet
 *            avant la fin du temps (title, target = nom affiché, hp, time en secondes, score = prime,
 *            line = réplique de Pete).
 * race     : le niveau commence par une descente en skate vue de dos (façon Out Run) ;
 *            le boss attend au bout (voir le niveau 7 et docs/content.md).
 *
 * Options d'un boss : special peut être une liste (attaques en alternance) ;
 *   static = onde de neige télé qui étourdit à courte portée,
 *   components = salve de trois composants électroniques qui explosent à l'atterrissage.
 * entrance = door : il sort du décor en défonçant une porte (sinon il entre par la droite).
 * guard = garde : au bout de « hits » coups encaissés en « window » images, il ne bronche plus
 *         et repousse le héros d'un grand coup (push = élan du recul, line = sa réplique).
 *
 * Voir docs/content.md pour ajouter ou modifier un niveau.
 */

return [
    // 01 Walk Straight
    1 => [
        'theme' => [
            'chaos' => 0,
            'seed' => 101,
            'sky' => 'paper',
            'celestial' => 'sun',
            'accent' => '#e8203a',
            'signs' => ['BANK', 'MALL', 'TV', 'JOBS', 'SALE', 'OFFICE'],
        ],
        'tags' => ['WALK STRAIGHT!', 'KEEP SILENCE!'],
        // la difficulté monte en deux temps : la télé coupe la rue en deux
        'length' => 9,
        'stages' => [
            ['enemies' => 2],
            ['enemies' => 3],
            ['enemies' => 3, 'reinforcements' => 2],
            'midboss',
            ['enemies' => 3, 'pincer' => true],
            ['enemies' => 3, 'extra' => ['knifer'], 'pincer' => true, 'reinforcements' => 2],
            ['enemies' => 4, 'extra' => ['knifer'], 'pincer' => true, 'reinforcements' => 3],
        ],
        'midboss' => [
            'scale' => 1.5,
            'sprite' => 'tv',
            'name' => 'WALK STRAIGHT',
            // il défonce une porte du décor et vient vers nous au lieu d'entrer par la droite
            'entrance' => 'door',
            'hp' => 30,
            'speed' => 0.55,
            'damage' => 12,
            'reach' => 28,
            // en alternance : neige télé, puis une salve de composants qui explosent (PLING!)
            'special' => ['static', 'components'],
            'every' => 180,
            'score' => 2500,
            'line' => 'KEEP SILENCE!',
            'staticLine' => 'WATCHING TV... NOW FORGET IT!',
        ],
        'boss' => [
            'scale' => 1.5,
            'sprite' => 'manager',
            'name' => 'THE MANAGER',
            'hp' => 30,
            'speed' => 0.6,
            'damage' => 12,
            'special' => 'summon',
            'every' => 360,
            'line' => 'WORK TO SURVIVE!',
            // on ne le bat plus en martelant le même coup : il encaisse et repousse
            'guard' => ['hits' => 3, 'window' => 90, 'push' => 9, 'line' => 'BACK TO WORK!'],
        ],
    ],
    // 02 Metal Slug
    2 => [
        'theme' => [
            'chaos' => 0.1,
            'seed' => 202,
            'sky' => 'paper',
            'celestial' => 'sun',
            'accent' => '#ff8a1e',
            'signs' => ['ARCADE', 'GAMES', 'CHIPS', 'BEER', 'NEOGEO'],
        ],
        'tags' => ['INSERT COIN', 'SHOOT THEM ALL', 'PEACE AND LOVE'],
        'boss' => [
            'scale' => 1.5,
            'sprite' => 'general',
            'name' => 'THE GENERAL',
            'hp' => 36,
            'speed' => 0.55,
            'damage' => 13,
            'special' => 'throw',
            'projectile' => 'grenade',
            'every' => 130,
            'line' => "I'LL DESTROY THE FINAL BOSS",
        ],
    ],
    // 03 Nightmare
    3 => [
        'theme' => [
            'chaos' => 0.25,
            'seed' => 303,
            'sky' => 'night',
            'celestial' => 'moon',
            'accent' => '#9b4dff',
            'signs' => ['MOTEL', '24H', 'DINER', 'BAR'],
            'fog' => true,
        ],
        'tags' => ['WAKE UP', 'TOO LATE', 'NIGHTMARE'],
        'boss' => [
            'scale' => 2,
            'sprite' => 'nightmare',
            'name' => 'THE NIGHTMARE',
            'hp' => 42,
            'speed' => 0.9,
            'damage' => 14,
            'special' => 'teleport',
            'every' => 200,
            'reach' => 36,
            'line' => 'WE WERE BROTHERS IN THE SAME FIGHT',
        ],
    ],
    // 04 Beer Church
    4 => [
        'theme' => [
            'chaos' => 0.35,
            'seed' => 404,
            'sky' => 'dusk',
            'celestial' => 'sun',
            'accent' => '#ffb020',
            'signs' => ['BAR', 'PUB', 'LIQUOR', 'CHURCH', 'BEER'],
        ],
        'tags' => ['GOD BLESS BEER', 'MORE AND MORE'],
        // tout le niveau est une casse, comme la voiture de Street Fighter II : Pete, ivre, titube
        // et a « time » secondes pour casser le conteneur à verre ; score = prime s'il y arrive.
        // (le boss ci-dessous ne sert qu'à la borne OpenBOR, qui garde le niveau en vue de côté)
        'bonus' => [
            'title' => 'HAPPY HOUR!',
            'target' => 'BOTTLE BANK',
            // costaud : on le casse vers la fin du morceau (1 h 45), même en tapant sans arrêt
            'hp' => 240,
            'time' => 100,
            'score' => 10000,
            'line' => 'HIC! MORE AND MORE...',
            // des ennemis débarquent toutes les « every » secondes pour embêter Pete (au plus « max » debout) :
            // d'abord « first » (le flic et le CRS, qui le repoussent loin du conteneur), puis tirés dans « pool »
            'hecklers' => [
                'every' => 9,
                'max' => 3,
                'first' => ['cop', 'riot'],
                'pool' => ['hooligan', 'skinhead', 'batter', 'cop', 'riot'],
            ],
        ],
        'boss' => [
            'scale' => 2,
            'sprite' => 'priest',
            'name' => 'FATHER BOOZE',
            'hp' => 48,
            'speed' => 0.45,
            'damage' => 15,
            'special' => 'throw',
            'projectile' => 'bottle',
            'every' => 95,
            'line' => 'THE MESSAGE IS IN THE BOTTLE',
        ],
    ],
    // 05 Black Belt
    5 => [
        'theme' => [
            'chaos' => 0.45,
            'seed' => 505,
            'sky' => 'paper',
            'celestial' => 'sun',
            'accent' => '#e8203a',
            'signs' => ['DOJO', 'KARATE', 'GYM', 'BOXING'],
        ],
        'tags' => ['NEVER GIVE UP', 'STRENGTH!'],
        'boss' => [
            'scale' => 2,
            'sprite' => 'champion',
            'name' => 'THE CHAMPION',
            'hp' => 54,
            'speed' => 0.8,
            'damage' => 17,
            'special' => 'charge',
            'every' => 160,
            'line' => "CAN'T BRING ME DOWN",
        ],
    ],
    // 06 I'm Bored
    6 => [
        'theme' => [
            'chaos' => 0.6,
            'seed' => 606,
            'sky' => 'grey',
            'celestial' => 'none',
            'accent' => '#4da6ff',
            'signs' => ['LAUNDRY', 'TV', 'CLOSED', 'VIDEO'],
            'rain' => true,
        ],
        'tags' => ["I'M BORED", 'I MISS YOU'],
        'boss' => [
            'scale' => 2.5,
            'sprite' => 'troll',
            'name' => 'THE TROLL',
            'hp' => 58,
            'speed' => 0.6,
            'damage' => 15,
            'special' => 'throw',
            'projectile' => 'phone',
            'every' => 75,
            'line' => "I'M SO ALONE WITHOUT YOU",
        ],
    ],
    // 07 Skate is a Drug
    7 => [
        'theme' => [
            'chaos' => 0.7,
            'seed' => 707,
            'sky' => 'paper',
            'celestial' => 'sun',
            'accent' => '#22d3ee',
            'signs' => ['SKATE', 'POLICE', 'SHOP', 'DONUTS'],
        ],
        'tags' => ['MAKE AN OLLIE', 'SKATE IS A DRUG', 'NEVERMIND'],
        // la descente en skate, vue de dos ; le boss attend au skatepark, en bas de la rue
        'race' => [
            'seed' => 717,
            // [longueur en segments, virage (-6 gauche à 6 droite), dénivelé en segments (négatif = ça descend)]
            'sections' => [
                [100, 0, 0],
                [150, 2, -20],
                [150, -3, -30],
                [100, 0, 15],
                [200, 4, -40],
                [150, 0, -10],
                [150, -4, 25],
                [200, 2, -50],
                [150, -2, 0],
                [200, 5, -30],
                [150, 0, 10],
                [150, -3, -40],
                [120, 0, 0],
            ],
            // les ennemis qui débarquent quand Pete atteint le segment « at » (riders : config/game.php)
            'traffic' => [
                ['at' => 60, 'riders' => ['roller', 'roller']],
                ['at' => 200, 'riders' => ['roller', 'roller', 'roller']],
                ['at' => 350, 'riders' => ['bmx']],
                ['at' => 480, 'riders' => ['moto']],
                ['at' => 600, 'riders' => ['roller', 'bmx']],
                ['at' => 750, 'riders' => ['moto', 'moto']],
                ['at' => 880, 'riders' => ['roller', 'roller', 'roller']],
                ['at' => 1000, 'riders' => ['bmx', 'roller']],
                ['at' => 1150, 'riders' => ['moto', 'roller']],
                ['at' => 1280, 'riders' => ['bmx', 'bmx']],
                ['at' => 1420, 'riders' => ['moto', 'moto', 'roller']],
                ['at' => 1560, 'riders' => ['roller', 'roller', 'bmx']],
                ['at' => 1700, 'riders' => ['moto', 'bmx', 'moto']],
            ],
            // un obstacle sur la chaussée tous les « every » segments environ : on l'évite ou on fait un ollie
            'obstacles' => ['every' => 38, 'pool' => ['cone', 'cone', 'barrier', 'trash']],
        ],
        'boss' => [
            'scale' => 2.5,
            'sprite' => 'bonebreaker',
            'name' => 'BONEBREAKER',
            'hp' => 64,
            'speed' => 0.8,
            'damage' => 18,
            'special' => 'charge',
            'every' => 140,
            'line' => "I'M NOT AFRAID TO BREAK MY BONES",
        ],
    ],
    // 08 Junkie Heart
    8 => [
        'theme' => [
            'chaos' => 0.85,
            'seed' => 808,
            'sky' => 'night',
            'celestial' => 'moon',
            'accent' => '#ff3ea5',
            'signs' => ['LOVE', 'HOTEL', 'BAR', 'XOXO', 'CLUB'],
        ],
        'tags' => ['TRUE LOVE', 'JUNKIE HEART', 'DEJA VU'],
        'boss' => [
            'scale' => 2.5,
            'sprite' => 'dealer',
            'name' => 'THE DEALER',
            'hp' => 70,
            'speed' => 0.65,
            'damage' => 16,
            'special' => 'throw',
            'projectile' => 'heart',
            'every' => 65,
            'line' => 'MY HEART IS A JUNKIE',
        ],
    ],
    // 09 Behind the Mask
    9 => [
        'theme' => [
            'chaos' => 1,
            'seed' => 909,
            'sky' => 'night',
            'celestial' => 'moon',
            'accent' => '#e8203a',
            'signs' => ['DATA', 'IA', 'CLOUD', 'SERVER', 'MASK'],
            'datacenter' => true,
        ],
        'tags' => ['WHO?', 'BEHIND THE MASK', 'STOP IA', 'NO SURRENDER'],
        'boss' => [
            'scale' => 3,
            'sprite' => 'mask',
            'name' => 'DOCTEUR MASK',
            'hp' => 90,
            'speed' => 0.75,
            'damage' => 20,
            'special' => 'summon',
            'every' => 280,
            'line' => "L'IA A DÉJÀ GAGNÉ !",
        ],
    ],
];
