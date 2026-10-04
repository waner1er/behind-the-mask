<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Weapon\Weapon;
use Vigilante\Sprite\Weapon\WeaponCatalog;

/** 09 Behind the Mask : le Docteur Mask, blouse de labo, masque blanc aux yeux rouges, cape et katana. */
final class Mask extends BossDesign
{
    protected function palette(): array
    {
        return [
            'H' => '#0c0a10', 'W' => '#f2efe6', 'w' => '#b8b4a8', 'R' => '#ff1e3c',
            'C' => '#dcdce2', 'c' => '#9a9aa8', 'T' => '#d01c32', 'D' => '#050506',
            'A' => '#18161c', 'a' => '#0c0b0e', 'v' => '#050506',
            'O' => '#050506', 'o' => '#2a2830', 'q' => '#020203', 'X' => '#020203',
        ];
    }

    protected function head(): Layer
    {
        return new Layer([
            '...........HHHHHHHH',
            '..........HHHHHHHHHHH',
            '..........HHHHWWWWWWWW',
            '..........HHHWWWWWWWWW',
            '..........HHWWWWWWWWWWW',
            '..........HHWWWKKKWWKKW',
            '..........HHWWWKRKWWKRW',
            '..........HHWWWWWWWWWWWW',
            '..........HHwWWWWWWWWWw',
            '...........HwWWWKKKKKWw',
            '............wwWWWWWWW',
        ], 0, 5);
    }

    protected function torso(): Layer
    {
        return new Layer([
            '..............sssss',
            '............cCCDDTDDCCc',
            '............cCCCDTDCCCc',
            '............cCCCCTCCCCc',
            '............cCCCCTCCCCc',
            '............cCCCCTCCCCc',
            '............cCCCCCCCCCc',
            '............cCCCCCCCCCc',
            '............cCCCCCCCCCc',
            '............ccCCCCCCCcc',
            '.............DDDDDDDDD',
            '.............AAAAAAAAa',
        ], 0, 16);
    }

    /** Gants blancs. */
    protected function armColors(): array
    {
        return ['H' => 'C', 'h' => 'c', 'S' => 'W', 's' => 'w'];
    }

    protected function weapon(): Weapon
    {
        return WeaponCatalog::get('katana');
    }

    /** Longue cape noire doublée de rouge. */
    protected function back(): array
    {
        return [new Layer([
            '............RLL',
            '............RLL',
            '............RLL',
            '...........RLLL',
            '...........RLLL',
            '..........RLLLL',
            '..........RLLLL',
            '..........RLLLL',
            '.........RLLLLL',
            '.........RLLLLL',
            '........RLLLLLL',
            '........RLLLLLL',
            '.......RLLLLLLL',
            '.......RLLLLLLL',
            '.......RLLLLLLL',
            '......RLLLLLLLL',
            '......RLLLLLLLL',
            '.....RLLLLLLLLL',
            '.....RLLLLLLLLL',
            '.....RLLLLLLLLL',
            '....RLLLLLLLLLL',
            '....RLLLLLLLLLL',
        ], 0, 16)];
    }
}
