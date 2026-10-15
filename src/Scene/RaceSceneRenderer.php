<?php

declare(strict_types=1);

namespace Vigilante\Scene;

use Vigilante\Scene\Layer\DowntownLayer;
use Vigilante\Scene\Layer\HillsLayer;
use Vigilante\Scene\Layer\SunsetLayer;

/**
 * Le décor de la descente en skate : seulement ce qui est au-dessus de l'horizon,
 * la route elle-même est dessinée en perspective par le JavaScript (js/race/).
 * Les plans défilent selon les virages (data-factor, comme les décors de rue).
 */
final readonly class RaceSceneRenderer
{
    public function render(Theme $theme): string
    {
        $svg = (new SunsetLayer())->render();
        foreach ([[0.1, new HillsLayer()], [0.3, new DowntownLayer($theme->seed + 5)]] as [$factor, $layer]) {
            $content = $layer->render();
            $svg .= sprintf(
                '<g class="layer" data-factor="%s">%s<g transform="translate(%d 0)">%s</g></g>',
                $factor,
                $content,
                Screen::WIDTH,
                $content,
            );
        }

        return $svg;
    }
}
