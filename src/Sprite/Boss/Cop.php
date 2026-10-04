<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Weapon\Weapon;
use Vigilante\Sprite\Weapon\WeaponCatalog;

/** 07 Skate is a Drug : CRS en casque à visière, matraque et bouclier anti-émeute. */
final class Cop extends BossDesign
{
    protected function palette(): array
    {
        return [
            'B' => '#1c2a4a', 'g' => '#6a8ab0',
            'C' => '#24345a', 'c' => '#16223e', 'Y' => '#ffd23f', 'D' => '#0c0c10',
            'A' => '#1c2a4a', 'a' => '#121c32', 'v' => '#0a1020',
            'O' => '#0c0c10', 'o' => '#2a2a34', 'q' => '#050506', 'X' => '#050506',
        ];
    }

    protected function head(): Layer
    {
        return new Layer([
            '............BBBBBBB',
            '..........BBBBBBBBBB',
            '..........BBBBBBBBBBBB',
            '..........BBBBBBBBBBBBB',
            '..........BBBBgggggggggg',
            '..........BBBBgSSSSSSSgg',
            '..........BBBBgSKKKSSKKg',
            '..........BBBsgSSSSSSKSg',
            '..........BBBsgSSSSSSSSSg',
            '..........sBBsgggggggggg',
            '...........sSSSSSSKKKKs',
            '............ssSSSSSSS',
        ], 0, 4);
    }

    protected function torso(): Layer
    {
        return new Layer([
            '..............sssss',
            '............cCCCCCCCCCc',
            '............cCCCCCCYCCc',
            '............cCCCCCCCCCc',
            '............cCCCCCCCCCc',
            '............cCCCCCCCCCc',
            '............cCCCCCCCCCc',
            '............cCCCCCCCCCc',
            '............cCCCCCCCCCc',
            '............ccCCCCCCCcc',
            '.............DDDDYDDDD',
            '.............AAAAAAAAa',
        ], 0, 16);
    }

    /** Gants noirs. */
    protected function armColors(): array
    {
        return ['H' => 'C', 'h' => 'c', 'S' => 'D', 's' => 'D'];
    }

    protected function weapon(): Weapon
    {
        return WeaponCatalog::get('baton');
    }

    /** Bouclier anti-émeute. */
    protected function front(): array
    {
        return [Layer::at(21, 13, [
            'mmmmmmm',
            'mpppppm',
            'mpppppm',
            'mpMpppm',
            'mpMpppm',
            'mpppppm',
            'mLLLLLm',
            'mLMLMLm',
            'mLLLLLm',
            'mpppppm',
            'mpppppm',
            'mpppppm',
            'mpppppm',
            'mpppppm',
            'mpppppm',
            'mmmmmmm',
        ])];
    }
}
