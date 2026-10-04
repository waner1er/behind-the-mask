<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Layer;

/** 05 Black Belt : combattant torse nu, gants rouges et ceinture de champion. */
final class Champion extends BossDesign
{
    protected function palette(): array
    {
        return [
            'H' => '#1a1210', 'W' => '#f4f4f4', 'R' => '#d01c32', 'r' => '#8a0f1f',
            'A' => '#d01c32', 'a' => '#8a0f1f', 'v' => '#5a0814',
            'O' => '#f0f0f0', 'o' => '#c0c0c8', 'q' => '#8a8a94', 'X' => '#d01c32',
        ];
    }

    protected function head(): Layer
    {
        return new Layer([
            '...........HHHHHHH',
            '..........HHHHHHHHHH',
            '..........HsSSSSSSSSS',
            '..........sSSSSSSSSSSSS',
            '..........sSSSSKKKSSKKS',
            '..........SssSSSSSSKSSK',
            '..........SssSSSSSSSSSSS',
            '..........sSsSSSSSSSSSs',
            '...........sSSSSSSKKKKs',
            '............ssSSSSSSS',
        ], 0, 6);
    }

    protected function torso(): Layer
    {
        return new Layer([
            '..............sssss',
            '............sSSSSSSSSSs',
            '............sSSsSSsSSSs',
            '............sSSSsSSSSSs',
            '............sSSSSsSSSSs',
            '............sSSsSsSsSSs',
            '............sSSSSsSSSSs',
            '............sSSsSsSsSSs',
            '............sSSSSSSSSSs',
            '............ssSSSSSSSss',
            '.............WWWWWWWWW',
            '.............AAAAAAAAa',
        ], 0, 16);
    }

    /** Bras nus et gants de boxe rouges. */
    protected function armColors(): array
    {
        return ['H' => 'S', 'h' => 's', 'S' => 'R', 's' => 'r'];
    }

    /** Ceinture de champion. */
    protected function over(): array
    {
        return [Layer::at(11, 25, ['jjjjjjjjjjjj', 'jjjjMMMMjjjj', 'jjjjjjjjjjjj'])];
    }
}
