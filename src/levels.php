<?php

/**
 * Un niveau par morceau de l'album.
 * La clé est le numéro de piste dans paroles.md.
 *
 * - theme : ambiance du décor (voir Scene). 'chaos' (0 à 1) : la ville part en
 *           ruine au fil de l'album (incendies, fumée, épaves...)
 * - tags  : graffitis prioritaires (le reste est pioché dans les paroles)
 * - boss  : identifiant du sprite (src/sprites/characters.php) + son comportement.
 *           'scale' = taille du boss (multiple de 0.5 pour garder des pixels nets),
 *           de plus en plus gros au fil de l'album.
 *
 * Comportements spéciaux des boss :
 *   summon   : appelle des sbires
 *   throw    : lance des projectiles
 *   charge   : fonce à travers l'écran
 *   teleport : disparaît et réapparaît dans ton dos
 */

return [
    1 => [
        'theme' => ['chaos' => 0, 'seed' => 101, 'sky' => 'paper', 'celestial' => 'sun', 'accent' => '#e8203a', 'signs' => ['BANK', 'MALL', 'TV', 'JOBS', 'SALE', 'OFFICE']],
        'tags' => ['WALK STRAIGHT!', 'KEEP SILENCE!'],
        'boss' => ['scale' => 1.5, 'sprite' => 'manager', 'name' => 'THE MANAGER', 'hp' => 38, 'speed' => 0.6, 'damage' => 12, 'special' => 'summon', 'every' => 360, 'line' => 'WORK TO SURVIVE!'],
    ],
    2 => [
        'theme' => ['chaos' => 0.1, 'seed' => 202, 'sky' => 'paper', 'celestial' => 'sun', 'accent' => '#ff8a1e', 'signs' => ['ARCADE', 'GAMES', 'CHIPS', 'BEER', 'NEOGEO']],
        'tags' => ['INSERT COIN', 'SHOOT THEM ALL', 'PEACE AND LOVE'],
        'boss' => ['scale' => 1.5, 'sprite' => 'general', 'name' => 'THE GENERAL', 'hp' => 45, 'speed' => 0.55, 'damage' => 13, 'special' => 'throw', 'projectile' => 'grenade', 'every' => 130, 'line' => "I'LL DESTROY THE FINAL BOSS"],
    ],
    3 => [
        'theme' => ['chaos' => 0.25, 'seed' => 303, 'sky' => 'night', 'celestial' => 'moon', 'accent' => '#9b4dff', 'signs' => ['MOTEL', '24H', 'DINER', 'BAR'], 'fog' => true],
        'tags' => ['WAKE UP', 'TOO LATE', 'NIGHTMARE'],
        'boss' => ['scale' => 2, 'sprite' => 'nightmare', 'name' => 'THE NIGHTMARE', 'hp' => 52, 'speed' => 0.9, 'damage' => 14, 'special' => 'teleport', 'every' => 200, 'reach' => 36, 'line' => 'WE WERE BROTHERS IN THE SAME FIGHT'],
    ],
    4 => [
        'theme' => ['chaos' => 0.35, 'seed' => 404, 'sky' => 'dusk', 'celestial' => 'sun', 'accent' => '#ffb020', 'signs' => ['BAR', 'PUB', 'LIQUOR', 'CHURCH', 'BEER']],
        'tags' => ['GOD BLESS BEER', 'MORE AND MORE'],
        'boss' => ['scale' => 2, 'sprite' => 'priest', 'name' => 'FATHER BOOZE', 'hp' => 60, 'speed' => 0.45, 'damage' => 15, 'special' => 'throw', 'projectile' => 'bottle', 'every' => 95, 'line' => 'THE MESSAGE IS IN THE BOTTLE'],
    ],
    5 => [
        'theme' => ['chaos' => 0.45, 'seed' => 505, 'sky' => 'paper', 'celestial' => 'sun', 'accent' => '#e8203a', 'signs' => ['DOJO', 'KARATE', 'GYM', 'BOXING']],
        'tags' => ['NEVER GIVE UP', 'STRENGTH!'],
        'boss' => ['scale' => 2, 'sprite' => 'champion', 'name' => 'THE CHAMPION', 'hp' => 68, 'speed' => 0.8, 'damage' => 17, 'special' => 'charge', 'every' => 160, 'line' => "CAN'T BRING ME DOWN"],
    ],
    6 => [
        'theme' => ['chaos' => 0.6, 'seed' => 606, 'sky' => 'grey', 'celestial' => 'none', 'accent' => '#4da6ff', 'signs' => ['LAUNDRY', 'TV', 'CLOSED', 'VIDEO'], 'rain' => true],
        'tags' => ["I'M BORED", 'I MISS YOU'],
        'boss' => ['scale' => 2.5, 'sprite' => 'troll', 'name' => 'THE TROLL', 'hp' => 72, 'speed' => 0.6, 'damage' => 15, 'special' => 'throw', 'projectile' => 'phone', 'every' => 75, 'line' => "I'M SO ALONE WITHOUT YOU"],
    ],
    7 => [
        'theme' => ['chaos' => 0.7, 'seed' => 707, 'sky' => 'paper', 'celestial' => 'sun', 'accent' => '#22d3ee', 'signs' => ['SKATE', 'POLICE', 'SHOP', 'DONUTS']],
        'tags' => ['MAKE AN OLLIE', 'SKATE IS A DRUG', 'NEVERMIND'],
        'boss' => ['scale' => 2.5, 'sprite' => 'cop', 'name' => 'RIOT COP', 'hp' => 80, 'speed' => 0.7, 'damage' => 18, 'special' => 'charge', 'every' => 140, 'line' => 'COPS ARE BEHIND'],
    ],
    8 => [
        'theme' => ['chaos' => 0.85, 'seed' => 808, 'sky' => 'night', 'celestial' => 'moon', 'accent' => '#ff3ea5', 'signs' => ['LOVE', 'HOTEL', 'BAR', 'XOXO', 'CLUB']],
        'tags' => ['TRUE LOVE', 'JUNKIE HEART', 'DEJA VU'],
        'boss' => ['scale' => 2.5, 'sprite' => 'dealer', 'name' => 'THE DEALER', 'hp' => 88, 'speed' => 0.65, 'damage' => 16, 'special' => 'throw', 'projectile' => 'heart', 'every' => 65, 'line' => 'MY HEART IS A JUNKIE'],
    ],
    9 => [
        'theme' => ['chaos' => 1, 'seed' => 909, 'sky' => 'night', 'celestial' => 'moon', 'accent' => '#e8203a', 'signs' => ['DATA', 'IA', 'CLOUD', 'SERVER', 'MASK'], 'datacenter' => true],
        'tags' => ['WHO?', 'BEHIND THE MASK', 'STOP IA', 'NO SURRENDER'],
        'boss' => ['scale' => 3, 'sprite' => 'mask', 'name' => 'DOCTEUR MASK', 'hp' => 112, 'speed' => 0.75, 'damage' => 20, 'special' => 'summon', 'every' => 280, 'line' => "L'IA A DÉJÀ GAGNÉ !"],
    ],
];
