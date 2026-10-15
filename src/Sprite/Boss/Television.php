<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Layer;

/**
 * Boss de mi-niveau de 01 Walk Straight (« watching TV / now forget it ») :
 * une télé cathodique à l'écran brisé, bras nus, treillis et rangers rouges.
 * Le bras de devant pend sur le panneau des boutons, sous l'écran.
 */
final class Television extends BossDesign
{
    protected function palette(): array
    {
        return [
            'W' => '#6a4428', 'w' => '#3e2616', 'B' => '#26262e', 'b' => '#14141a',
            'G' => '#1e3440', 'g' => '#5a8a9a', 'Z' => '#f2f2f2', 'E' => '#c8c8d0', 'R' => '#e8203a',
            'Y' => '#9a9aa6', 'D' => '#141418',
            'A' => '#56602c', 'a' => '#3a421c', 'v' => '#262c12',
            'O' => '#c81e2a', 'o' => '#8a1018', 'q' => '#5a0a10', 'X' => '#140a0a',
        ];
    }

    protected function armColors(): array
    {
        return ['H' => 'S', 'h' => 's'];
    }

    /** Antennes et écran fissuré. */
    protected function head(): Layer
    {
        return new Layer([
            '...Y..........Y',
            '....Y........Y',
            '.....Y......Y',
            '......YY..YY',
            '.wwwWWWWWWWWWWWWWWWWWWW',
            'wwwWWWWWWWWWWWWWWWWWWWWW',
            'wwwWBBBBBBBBBBBBBBBBBBWW',
            'wwwWBGGGGGGGGGGGGGGGGBWW',
            'wwwWBGgggGGGZGGGGGGGGBWW',
            'wwwWBGggGGGGZGGGGZGGGBWW',
            'wwwWBGgGGGGZGZGGZGGGGBWW',
            'wwwWBGGGZZZGGGZZGGGGGBWW',
            'wwwWBGGGGGGZZZZGGGGGGBWW',
            'wwwWBGGGGGZGGZGZZZGGGBWW',
            'wwwWBGGGGZGGGZGGGGZGGBWW',
            'wwwWBGGGZGGGGZGGGGGGGBWW',
            'wwwWBGGGGGGGGZGGGGGGGBWW',
            'wwwWBBBBBBBBBBBBBBBBBBWW',
        ], 6, 0);
    }

    /** Boutons, haut-parleur et ceinture du treillis. */
    protected function torso(): Layer
    {
        return new Layer([
            'wwwWWWWWWWWWWWWWWWWWWWWW',
            'wwwWWEWWEWWWWWWWWWWRWWWW',
            'wwwWEEEWEEEWWbbbbbbbbbWW',
            'wwwWWEWWEWWWWbWbWbWbWbWW',
            'wwwWWWWWWWWWWbbbbbbbbbWW',
            'wwwWWWWWWWWWWWWWWWWWWWWW',
            '.wwwWWWWWWWWWWWWWWWWWWW',
            '.........DDDDDDDDD',
            '.........AAAAAAAAa',
            '.........AAAAAAAAa',
        ], 6, 18);
    }
}
