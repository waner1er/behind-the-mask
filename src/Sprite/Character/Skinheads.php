<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Weapon\Weapon;

/** Crâne rasé, bomber, jean délavé, bretelles et coquées : la base de tous les skins. */
final class Skinheads
{
    private const PALETTE = [
        'K' => '#0c0a10',
        'S' => '#e9b08a', 's' => '#b9785a', 'r' => '#c0303a',  // peau, cicatrice
        'C' => '#4f6b38', 'c' => '#33482a', 'R' => '#e07a22',  // bomber, doublure orange
        'z' => '#c0c0c8', 'D' => '#24301a',
        'A' => '#7a98c2', 'a' => '#53719c', 'v' => '#34507a',  // jean délavé
        'U' => '#b0202a',                                      // bretelles
        'O' => '#6a1a24', 'o' => '#9a2a36', 'q' => '#3a0e14',  // coquées bordeaux
        'X' => '#e8c84a',                                      // semelle jaune
    ];

    private const HEAD = [
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
    ];

    private const TORSO = [
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
    ];

    private const BEANIE = [
        '.............UUUU',
        '...........UUUUUUUU',
        '..........UUUUUUUUUUU',
        '..........RRRRRRRRRRRR',
    ];

    /**
     * @param array<string, string> $colors couleurs qui remplacent celles de la palette de base
     * @param list<Layer> $over
     */
    public static function look(array $colors = [], ?Weapon $weapon = null, array $over = []): EnemyLook
    {
        return new EnemyLook(
            $colors + self::PALETTE,
            new Layer(self::HEAD, 0, 6),
            new Layer(self::TORSO, 0, 16),
            ['H' => 'C', 'h' => 'c'],
            $weapon,
            over: $over,
        );
    }

    public static function beanie(): Layer
    {
        return new Layer(self::BEANIE, 0, 4);
    }
}
