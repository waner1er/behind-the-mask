<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Weapon\Weapon;
use Vigilante\Sprite\Weapon\WeaponCatalog;

/** 01 Walk Straight : costard, lunettes, cravate rouge, mallette. */
final class Manager extends BossDesign
{
    protected function palette(): array
    {
        return [
            'H' => '#3a2a20', 'G' => '#0c0a10', 'g' => '#9fd8ff',
            'C' => '#3a3a44', 'c' => '#26262e', 'W' => '#f2f2f2', 'R' => '#d01c32', 'Y' => '#c8c8d0', 'D' => '#141418',
            'A' => '#3a3a44', 'a' => '#26262e', 'v' => '#1a1a20',
            'O' => '#141418', 'o' => '#3a3a44', 'q' => '#0a0a0c', 'X' => '#0a0a0c',
        ];
    }

    protected function head(): Layer
    {
        return new Layer([
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
        ], 0, 5);
    }

    protected function torso(): Layer
    {
        return new Layer([
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
        ], 0, 16);
    }

    protected function weapon(): Weapon
    {
        return WeaponCatalog::get('briefcase');
    }
}
