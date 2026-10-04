<?php

/**
 * Réglages généraux du jeu, envoyés au JavaScript.
 *
 * Ennemis : reach = portée du coup, projectile = attaque à distance (toutes les « every » images),
 * moves = attaques possibles et leur poids (strike = coup normal, lunge = ruée, throw = lancer d'arme),
 * throws = sprite de l'arme lancée.
 * Armes : celles que le héros ramasse sur un ennemi (staff = son katana, l'arme par défaut).
 */

return [
    'band' => 'Vigilante',
    'title' => 'Behind the Mask',
    'logo' => 'medias/images/logo-vigilante.png',

    'album' => [
        'directory' => 'medias/audio/Vigilante - Behind the Mask',
        // phrases des paroles à ne jamais afficher dans le jeu
        'excluded' => ['NO JUSTICE', 'NO PEACE'],
    ],

    'enemies' => [
        'skinhead' => [
            'name' => 'SKINHEAD',
            'hp' => 3,
            'speed' => 0.7,
            'damage' => 9,
            'reach' => 26,
            'score' => 100,
            'moves' => ['strike' => 3, 'lunge' => 1],
        ],
        'batter' => [
            'name' => 'SKIN À BATTE',
            'hp' => 4,
            'speed' => 0.65,
            'damage' => 14,
            'reach' => 40,
            'weapon' => 'bat',
            'score' => 150,
            'moves' => ['strike' => 3, 'throw' => 1],
            'throws' => 'bat',
        ],
        'chainer' => [
            'name' => 'SKIN À CHAÎNE',
            'hp' => 4,
            'speed' => 0.9,
            'damage' => 11,
            'reach' => 44,
            'weapon' => 'chain',
            'score' => 150,
            'moves' => ['strike' => 2, 'lunge' => 2],
        ],
        'hooligan' => [
            'name' => 'HOOLIGAN',
            'hp' => 3,
            'speed' => 0.65,
            'damage' => 11,
            'reach' => 26,
            'projectile' => 'bottle',
            'every' => 100,
            'score' => 150,
            'moves' => ['throw' => 4],
        ],
        'masculinist' => [
            'name' => 'MASCULINISTE',
            'hp' => 5,
            'speed' => 0.55,
            'damage' => 13,
            'reach' => 26,
            'score' => 150,
            'moves' => ['strike' => 2, 'lunge' => 1],
        ],
        'knifer' => [
            'name' => 'MASCU AU COUTEAU',
            'hp' => 3,
            'speed' => 1.1,
            'damage' => 15,
            'reach' => 32,
            'weapon' => 'knife',
            'score' => 150,
            'moves' => ['strike' => 2, 'lunge' => 2, 'throw' => 1],
            'throws' => 'knife',
        ],
        'gymbro' => [
            'name' => 'GYM BRO',
            'hp' => 9,
            'speed' => 0.45,
            'damage' => 22,
            'reach' => 30,
            'weapon' => 'dumbbell',
            'score' => 250,
            'moves' => ['strike' => 3, 'lunge' => 1, 'throw' => 1],
            'throws' => 'dumbbell',
        ],
    ],

    // durée d'une arme ramassée, en images (60 par seconde)
    'weaponDuration' => 15 * 60,
    'weapons' => [
        'staff' => ['name' => 'KATANA', 'damage' => 1, 'reach' => 40, 'knockback' => 2.6],
        'bat' => ['name' => 'BATTE', 'damage' => 2, 'reach' => 38, 'knockback' => 4],
        'chain' => ['name' => 'CHAÎNE', 'damage' => 1, 'reach' => 50, 'knockback' => 2.2],
        'knife' => ['name' => 'COUTEAU', 'damage' => 2, 'reach' => 28, 'knockback' => 1.6],
        'dumbbell' => ['name' => 'HALTÈRE', 'damage' => 3, 'reach' => 28, 'knockback' => 5],
    ],
];
