<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\PixelArt\Layer;
use Vigilante\PixelArt\Line;

/** VigiBapt, le joueur 2 : grand, cheveux courts, sourire qui scintille, chemise à carreaux et guitare. */
final class BaptParts implements HeroLook
{
    public const PALETTE = [
        'K' => '#0c0a10',
        'Z' => '#3a2418', 'z' => '#5e3c26',                    // cheveux
        'S' => '#f2b48a', 's' => '#c27a52',                    // peau
        'E' => '#141018',                                      // yeux
        'T' => '#fffdf2', 'W' => '#ffffff', 'Y' => '#fff27a',  // dents, éclat du sourire
        'C' => '#d02030', 'c' => '#86121e', 'B' => '#18121c',  // chemise à carreaux
        'D' => '#2a1a14', 'G' => '#d8d8e0',                    // ceinture, boucle
        'A' => '#3c5f96', 'a' => '#2a4672', 'v' => '#1c3050',  // jean
        'O' => '#1a1820', 'o' => '#3a3644', 'q' => '#100e14',  // baskets en toile
        'X' => '#ece8dc',                                      // semelle blanche
        'R' => '#ffa526', 'r' => '#b8301a', 'b' => '#18121c',  // guitare : caisse sunburst, plaque
        'N' => '#c8955a', 'n' => '#8a5a30',                    // manche
        'k' => '#1a1a1e', 'y' => '#ffd23f',                    // skate
        '1' => '#e4e4ec', '2' => '#8a8a98', '3' => '#1c1a20', '4' => '#ffc62a', // armes ramassées
    ];

    /** F = tissu à carreaux, remplacé selon la position (voir plaid()). */
    private const HEAD = [
        '............ZZZZzZ',
        '..........ZZZZZZZZZz',
        '.........ZZZZZZZZZZZZ',
        '.........ZZZZsSSSSSSZ',
        '.........ZZZssSSSSSSSS',
        '.........ZZsSSSEESSEES',
        '..........ZssSSSSSSSSSS',
        '..........sSSSsSSSSSSS',
        '..........sSSSTTTTTTSS',
        '...........sSSsTTTTsS',
        '............sSSSSSSS',
    ];

    /** Éclat du sourire, sur l'image d'attente « brillante ». */
    private const TWINKLE = [
        '...............................Y',
        '..............................YWY',
        '..............W................Y',
    ];

    private const TORSO = [
        '..............sSSss',
        '...........FFFFsSsFFFF',
        '...........FFFFFsSFFFFF',
        '...........FFFFFFsFFFFF',
        '...........FFFFFFFFFFFF',
        '...........FFFFFFFFFFFF',
        '...........FFFFFFFFFFFF',
        '...........FFFFFFFFFFFF',
        '...........FFFFFFFFFFFF',
        '...........FFFFFFFFFFFF',
        '............FFFFFFFFFF',
        '............DDDDDGDDDD',
        '............AAAAAAAAAA',
        '............AAAAaAAAAA',
    ];

    /** Caisse de la guitare, manche vers la droite. */
    private const BODY = [
        '..rrrrr......',
        '.rRRRRRr.....',
        'rRRRRRRRr....',
        'rRRRRRRRRrrr.',
        'rRbbbRRRRRRrr',
        'rRRbbbRRRRrr.',
        'rRRRRRRRRr...',
        'rRRRRRRRRRr..',
        '.rRRRRRRRRr..',
        '..rrrrrrrr...',
    ];

    private const HEADSTOCK = ['WnnW', 'nnnn', 'WnnW'];

    /** Bras de devant et du fond pour chaque garde : [y, lignes]. Mêmes mains que Pete. */
    private const ARMS = [
        'guard' => [
            'near' => [18, [
                '...............FFF',
                '................FFF',
                '................FFFF',
                '.................FFF',
                '..................SSs',
                '..................SSs',
            ]],
            'far' => [17, [
                '....................FFF',
                '.....................FFF',
                '.......................SSs',
                '.......................SSs',
            ]],
        ],
        'windup' => [
            'near' => [18, [
                '...............FFF',
                '..............FFF',
                '.............FFF',
                '............SSs',
                '............SSs',
            ]],
            'far' => [18, [
                '..................FFF',
                '..................FFF',
                '..................FFF',
                '..................SSs',
                '..................SSs',
            ]],
        ],
        'strike' => [
            'near' => [18, [
                '...............FFF',
                '................FFFFFF',
                '.................FFFFFSSs',
                '......................SSs',
            ]],
            'far' => [18, [
                '..................FFF',
                '...................FFFFFFFF',
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

    /** Pas de skate dans le dos : il ne sort sa planche que pour la glisse. */
    public function back(bool $riding): ?Layer
    {
        return $riding ? new Layer(HeroParts::BOARD_UNDER, 0, 36, ['r' => 'y']) : null;
    }

    public function head(bool $twinkle): Layer
    {
        $rows = self::HEAD;
        if ($twinkle) {
            foreach (self::TWINKLE as $i => $sparkle) {
                $rows[7 + $i] = self::overlay($rows[7 + $i], $sparkle);
            }
        }

        return new Layer($rows, 0, 2);
    }

    public function torso(): Layer
    {
        return new Layer(self::plaid(self::TORSO, 14), 0, 14);
    }

    /** @param 'near'|'far' $side */
    public function arm(Stance $stance, string $side): Layer
    {
        [$y, $rows] = self::ARMS[$stance->value][$side];

        return new Layer(self::plaid($rows, $y), 0, $y);
    }

    /** La guitare : jouée en marchant, levée au-dessus de la tête, puis abattue comme une massue. */
    public function signature(Stance $stance): array
    {
        $body = self::BODY;
        $mirrored = array_map('strrev', self::BODY);

        return match ($stance) {
            Stance::Guard => [
                Layer::at(11, 21, $body),
                Line::between(24, 25, 35, 15, 'N', 'n'),
                Layer::at(35, 12, self::HEADSTOCK),
            ],
            Stance::Windup => [
                Line::between(12, 21, 8, 8, 'N', 'n'),
                Layer::at(0, 0, $body),
                Layer::at(11, 21, self::HEADSTOCK),
            ],
            Stance::Strike => [
                Layer::at(16, 19, self::HEADSTOCK),
                Line::between(20, 20, 39, 20, 'N', 'n'),
                Layer::at(38, 15, $mirrored),
            ],
        };
    }

    /**
     * Carreaux « bûcheron » de 2 px : rouge, rouge sombre au croisement d'une bande noire, noir.
     *
     * @param list<string> $rows
     * @return list<string>
     */
    private static function plaid(array $rows, int $top): array
    {
        foreach ($rows as $y => $row) {
            $dark = intdiv($top + $y, 2) % 2;
            for ($x = 0, $length = strlen($row); $x < $length; $x++) {
                if ($row[$x] === 'F') {
                    $rows[$y][$x] = ['C', 'c', 'B'][$dark + intdiv($x, 2) % 2];
                }
            }
        }

        return $rows;
    }

    /** Pose les pixels non transparents de $over sur $row. */
    private static function overlay(string $row, string $over): string
    {
        $row = str_pad($row, strlen($over), '.');
        for ($x = 0, $length = strlen($over); $x < $length; $x++) {
            if ($over[$x] !== '.') {
                $row[$x] = $over[$x];
            }
        }

        return $row;
    }
}
