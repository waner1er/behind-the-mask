<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Weapon\Weapon;

/** Casquette, lunettes de soleil, barbe, débardeur, chaîne en or et jogging. */
final class Masculinists
{
    private const PALETTE = [
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

    private const HEAD = [
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
    ];

    private const TORSO = [
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
    ];

    /** @param array<string, string> $colors couleurs qui remplacent celles de la palette de base */
    public static function look(array $colors = [], ?Weapon $weapon = null): EnemyLook
    {
        return new EnemyLook(
            $colors + self::PALETTE,
            new Layer(self::HEAD, 0, 4),
            new Layer(self::TORSO, 0, 16),
            ['H' => 'S', 'h' => 's'],
            $weapon,
        );
    }
}
