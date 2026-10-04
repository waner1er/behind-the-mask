<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Layer;

/** 02 Metal Slug : béret rouge, moustache, médailles, bazooka sur l'épaule. */
final class General extends BossDesign
{
    protected function palette(): array
    {
        return [
            'B' => '#c0202e', 'b' => '#801420', 'E' => '#2a1a12',
            'C' => '#7a7a48', 'c' => '#555530', 'Y' => '#ffd23f', 'R' => '#d01c32', 'D' => '#3a2a18',
            'A' => '#6a6a3c', 'a' => '#4c4c2a', 'v' => '#33331c',
            'O' => '#2a1c14', 'o' => '#4a3424', 'q' => '#160e0a', 'X' => '#0a0a0c',
        ];
    }

    protected function head(): Layer
    {
        return new Layer([
            '...........BBBBBBB',
            '..........BBBBBBBBBB',
            '.........bBBBBBBBBBBB',
            '.........b.sSSSSSSSSS',
            '..........ssSSSSSSSSSS',
            '..........sSSSSSSSSSSSS',
            '..........sSSSSKKKSSKKS',
            '..........SssSSSSSSKSSK',
            '..........SssSSSSSSSSSSS',
            '..........sSsSSSEEEEEEs',
            '...........sSSSSSEEEEEs',
            '............ssSSSSSSS',
        ], 0, 4);
    }

    protected function torso(): Layer
    {
        return new Layer([
            '..............sssss',
            '............cCCCCCCCCCc',
            '............cCYRCCCCCCc',
            '............cCRYCCCCCCc',
            '............cCCCCYCCCCc',
            '............cCCCCCCCCCc',
            '............cCCCCYCCCCc',
            '............cCCCCCCCCCc',
            '............cCCCCYCCCCc',
            '............ccCCCCCCCcc',
            '.............DDDDYDDDD',
            '.............AAAAAAAAa',
        ], 0, 16);
    }

    /** Bazooka porté sur l'épaule, derrière la tête. */
    protected function back(): array
    {
        return [new Layer([
            '.mm.LLLLLLLLLLLLLLLLLLLLLLLLmmm',
            'mmmmLLLLLLLLLLLLLLLLLLLLLLLLmmm',
            '.mm.LLLLLLLLLLLLLLLLLLLLLLLLmmm',
        ], 0, 15)];
    }

    /** Épaulettes dorées. */
    protected function over(): array
    {
        return [new Layer(['...........jjj.......jjj', '...........j.j.......j.j'], 0, 17)];
    }
}
