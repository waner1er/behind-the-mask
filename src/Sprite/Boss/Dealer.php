<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Weapon\Weapon;
use Vigilante\Sprite\Weapon\WeaponCatalog;

/** 08 Junkie Heart : manteau de fourrure, lunettes roses, dents en or, chapeau à plume et canne. */
final class Dealer extends BossDesign
{
    protected function palette(): array
    {
        return [
            'S' => '#a8704a', 's' => '#7a4c30',
            'H' => '#120c0a', 'G' => '#ff3ea5', 'g' => '#ffc2e2', 'Y' => '#ffd23f',
            'C' => '#5a2a7a', 'c' => '#3c1a52', 'F' => '#ff8ac8', 'W' => '#f4f4f4',
            'A' => '#2a1a3a', 'a' => '#1c1028', 'v' => '#100818',
            'O' => '#f4f4f4', 'o' => '#c8c8d0', 'q' => '#8a8a94', 'X' => '#ffd23f',
        ];
    }

    protected function head(): Layer
    {
        return new Layer([
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
        ], 0, 4);
    }

    protected function torso(): Layer
    {
        return new Layer([
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
        ], 0, 16);
    }

    protected function weapon(): Weapon
    {
        return WeaponCatalog::get('cane');
    }

    /** Chapeau à plume. */
    protected function over(): array
    {
        return [new Layer([
            '.............CCCCCC..F',
            '.............CCCCCC.FF',
            '.............YYYYYYFF',
            '.........CCCCCCCCCCCCCC',
        ])];
    }
}
