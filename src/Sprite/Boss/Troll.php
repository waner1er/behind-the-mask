<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Weapon\Weapon;
use Vigilante\Sprite\Weapon\WeaponCatalog;

/** 06 I'm Bored : sweat à capuche, énorme casque audio, téléphone à la main. */
final class Troll extends BossDesign
{
    protected function palette(): array
    {
        return [
            'S' => '#b8c8e0', 's' => '#7f90ac',
            'C' => '#5a5a64', 'c' => '#3e3e46', 'W' => '#e8e8e8',
            'A' => '#2c3a52', 'a' => '#1e2a3c', 'v' => '#121a26',
            'O' => '#e8e8ee', 'o' => '#b0b0bc', 'q' => '#7a7a86', 'X' => '#4da6ff',
        ];
    }

    protected function head(): Layer
    {
        return new Layer([
            '............CCCCCC',
            '..........CCCCCCCCC',
            '.........CCCCCCCCCCC',
            '.........CCCCcccccCCC',
            '.........CCCcSSSSSSSC',
            '.........CCcSSSSSSSSSS',
            '.........CCcSSSKKKSSKKS',
            '.........CCcSSSSSSSKSSK',
            '.........CCcSSSSSSSSSSSS',
            '.........CCcsSSSSSSSSSs',
            '..........CCcSSSSSKKKKs',
            '...........CCcsSSSSSS',
        ], 0, 4);
    }

    protected function torso(): Layer
    {
        return new Layer([
            '.............CsssssC',
            '............cCCCWCWCCCc',
            '............cCCCWCWCCCc',
            '............cCCCCCCCCCc',
            '............cCCCCCCCCCc',
            '............cCCcccccCCc',
            '............cCCcCCCcCCc',
            '............cCCcccccCCc',
            '............cCCCCCCCCCc',
            '............ccCCCCCCCcc',
            '.............AAAAAAAAa',
            '.............AAAAAAAAa',
        ], 0, 16);
    }

    protected function weapon(): Weapon
    {
        return WeaponCatalog::get('phone');
    }

    /** Arceau et écouteurs du casque audio. */
    protected function over(): array
    {
        return [
            new Layer(['...........PPPPPPPP', '.........PP........P'], 0, 2),
            new Layer(['........PPP', '.......PPpP', '.......PPpP', '........PPP'], 0, 9),
        ];
    }
}
