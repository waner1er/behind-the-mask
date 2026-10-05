<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\PixelArt\Layer;
use Vigilante\PixelArt\Line;

/** Pete, le Vigilante : kimono, ceinture noire, casquette de skateur, masque et katana. */
final class HeroParts implements HeroLook
{
    public const PALETTE = [
        'K' => '#0c0a10',
        'M' => '#e8203a', 'm' => '#9a1224', 'P' => '#ff7a8a',  // casquette
        'Z' => '#3a2418',                                      // cheveux
        'S' => '#f2b48a', 's' => '#c27a52',                    // peau
        'G' => '#141018', 'w' => '#ffffff',                    // masque
        'Y' => '#ffd23f',                                      // boucle d'oreille, garde du katana
        'C' => '#f4f2ec', 'c' => '#bdbacb', 'e' => '#8d8a9e',  // kimono
        'D' => '#141018',                                      // ceinture noire
        'A' => '#ecebe4', 'a' => '#b5b2c4', 'v' => '#8a879c',  // pantalon
        'O' => '#2a2430', 'o' => '#4a4256', 'q' => '#18141e',  // rangers
        'X' => '#1e1a24',
        'N' => '#eef0f6', 'n' => '#8a8c9a', 'h' => '#2a1a20',  // katana : lame, reflet, poignée
        'k' => '#1a1a1e', 'r' => '#ffd23f', 'T' => '#9a9aa6', 'W' => '#f4f4f4', // skate
        '1' => '#e4e4ec', '2' => '#8a8a98', '3' => '#1c1a20', '4' => '#ffc62a', // armes ramassées
    ];

    /** Les couleurs des armes (M, m, L, j) sont déjà prises par la casquette : on les renomme. */
    public const WEAPON_MAP = ['M' => '1', 'm' => '2', 'L' => '3', 'j' => '4'];

    public const HEAD = [
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
    ];

    public const TORSO = [
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
    ];

    public const BOARD_ON_BACK = [
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
    ];

    public const BOARD_UNDER = [
        '.......k....................k',
        '........kkkkkkkkkkkkkkkkkkkk',
        '.........rrrrrrrrrrrrrrrrrr',
        '..........WW...........WW',
    ];

    /** Bras de devant et du fond pour chaque garde : [y, lignes]. */
    private const ARMS = [
        'guard' => [
            'near' => [18, [
                '...............cCC',
                '................cCC',
                '................cCCC',
                '.................cCC',
                '..................SSs',
                '..................SSs',
            ]],
            'far' => [17, [
                '....................cCC',
                '.....................cCC',
                '.......................SSs',
                '.......................SSs',
            ]],
        ],
        'windup' => [
            'near' => [18, [
                '...............cCC',
                '..............cCC',
                '.............cCC',
                '............SSs',
                '............SSs',
            ]],
            'far' => [18, [
                '..................cCC',
                '..................cCC',
                '..................cCC',
                '..................SSs',
                '..................SSs',
            ]],
        ],
        'strike' => [
            'near' => [18, [
                '...............cCC',
                '................cCCCCC',
                '.................cCCCCSSs',
                '......................SSs',
            ]],
            'far' => [18, [
                '..................cCC',
                '...................cCCCCCCC',
                '............................SSs',
                '............................SSs',
            ]],
        ],
    ];

    /** @return array<array-key, string> */
    public function palette(): array
    {
        return self::PALETTE;
    }

    public function head(bool $twinkle): Layer
    {
        return new Layer(self::HEAD, 0, 4);
    }

    public function torso(): Layer
    {
        return new Layer(self::TORSO, 0, 16);
    }

    /** Le skate porté dans le dos, ou sous les pieds pendant l'attaque en glisse. */
    public function back(bool $riding): Layer
    {
        return $riding ? new Layer(self::BOARD_UNDER, 0, 36) : new Layer(self::BOARD_ON_BACK, 0, 14);
    }

    /** @param 'near'|'far' $side */
    public function arm(Stance $stance, string $side): Layer
    {
        [$y, $rows] = self::ARMS[$stance->value][$side];

        return new Layer($rows, 0, $y);
    }

    /** Le katana : poignée, garde dorée, lame. */
    public function signature(Stance $stance): array
    {
        return match ($stance) {
            Stance::Guard => [
                Line::between(16, 26, 23, 19, 'h', 'h'),
                Layer::at(24, 16, ['YY', 'YY']),
                Line::between(26, 16, 38, 4, 'N', 'n'),
            ],
            Stance::Windup => [
                Line::between(11, 22, 20, 21, 'h', 'h'),
                Layer::at(9, 21, ['Y', 'Y', 'Y']),
                Line::between(0, 23, 8, 22, 'N', 'n'),
            ],
            Stance::Strike => [
                Line::between(19, 20, 29, 20, 'h', 'h'),
                Layer::at(30, 19, ['Y', 'Y', 'Y', 'Y']),
                Line::between(31, 20, 52, 20, 'N', 'n'),
            ],
        };
    }
}
