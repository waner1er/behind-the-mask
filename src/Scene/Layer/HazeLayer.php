<?php

declare(strict_types=1);

namespace Vigilante\Scene\Layer;

use Vigilante\Scene\Screen;
use Vigilante\Scene\Svg;
use Vigilante\Scene\Theme;

/** Voile de la couleur du ciel qui éclaircit la skyline (perspective atmosphérique). */
final readonly class HazeLayer implements SceneLayer
{
    public function __construct(private Theme $theme)
    {
    }

    public function render(): string
    {
        $svg = '';
        foreach ([[96, 0.15], [112, 0.2], [124, 0.25]] as [$y, $opacity]) {
            $svg .= Svg::rect(0, $y, Screen::WIDTH, Screen::GROUND - $y, $this->theme->sky->background(), Svg::opacity($opacity));
        }

        return $svg;
    }
}
