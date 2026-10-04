<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Weapon\Weapon;
use Vigilante\Sprite\Weapon\WeaponCatalog;

/** 04 Beer Church : soutane, col blanc, nez rouge, ventre de buveur et chope de bière. */
final class Priest extends BossDesign
{
    protected function palette(): array
    {
        return [
            'H' => '#9a9aa2', 'r' => '#e0303e', 'W' => '#f4f4f4', 'g' => '#5a5a66',
            'C' => '#1c1a22', 'c' => '#0e0d12', 'Y' => '#ffb020',
            'A' => '#1c1a22', 'a' => '#0e0d12', 'v' => '#08070a',
            'O' => '#0e0d12', 'o' => '#2a2830', 'q' => '#050506', 'X' => '#050506',
        ];
    }

    protected function head(): Layer
    {
        return new Layer([
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
        ], 0, 6);
    }

    protected function torso(): Layer
    {
        return new Layer([
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
        ], 0, 16);
    }

    protected function weapon(): Weapon
    {
        return WeaponCatalog::get('mug');
    }

    /** Gros ventre de buveur. */
    protected function over(): array
    {
        return [new Layer([
            '...CCCCCCC',
            '..CCCCCCCCC',
            '.CCCCCCCCCCC',
            '.CCCCCgCCCCCc',
            '.CCCCCCCCCCCc',
            '..CCCCgCCCCc',
            '...cccccccc',
        ], 10, 20)];
    }
}
