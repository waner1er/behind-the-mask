<?php

declare(strict_types=1);

namespace Vigilante\Scene;

use Vigilante\Scene\Layer\BackgroundLayer;
use Vigilante\Scene\Layer\BuildingsLayer;
use Vigilante\Scene\Layer\HazeLayer;
use Vigilante\Scene\Layer\SceneLayer;
use Vigilante\Scene\Layer\SkylineLayer;
use Vigilante\Scene\Layer\StreetLayer;
use Vigilante\Sprite\PropCatalog;

/**
 * Décor urbain façon BD à l'encre (comme la pochette de l'album), entièrement procédural.
 *
 * Chaque plan qui défile fait exactement un écran de large et il est rendu deux fois
 * côte à côte : le JavaScript le décale selon la caméra (data-factor = vitesse de parallaxe).
 */
final readonly class SceneRenderer
{
    private const DEFAULT_TAGS = ['VIGILANTE'];

    public function __construct(private PropCatalog $props)
    {
    }

    /** @param list<string> $tags graffitis (paroles en majuscules) */
    public function render(Theme $theme, array $tags): string
    {
        /** @var list<array{float, SceneLayer}> $parallax */
        $parallax = [
            [0.25, new SkylineLayer($theme)],
            [0.6, new BuildingsLayer($theme, $tags ?: self::DEFAULT_TAGS)],
            [1.0, new StreetLayer($theme, $this->props)],
        ];

        $svg = (new BackgroundLayer($theme))->render();
        foreach ($parallax as $i => [$factor, $layer]) {
            $svg .= $this->scrolling($factor, $layer->render());
            if ($i === 0) {
                $svg .= (new HazeLayer($theme))->render();
            }
        }

        return $svg;
    }

    private function scrolling(float $factor, string $content): string
    {
        return sprintf(
            '<g class="layer" data-factor="%s">%s<g transform="translate(%d 0)">%s</g></g>',
            $factor,
            $content,
            Screen::WIDTH,
            $content,
        );
    }
}
