<?php

declare(strict_types=1);

namespace Vigilante\Scene\Layer;

use Vigilante\Scene\Painter\BuildingPainter;
use Vigilante\Scene\Screen;
use Vigilante\Scene\Theme;
use Vigilante\Support\SeededRandom;

/** Plan intermédiaire : une rangée d'immeubles de largeurs variées. */
final readonly class BuildingsLayer implements SceneLayer
{
    private const MIN_WIDTH = 46;

    /** @param list<string> $tags */
    public function __construct(private Theme $theme, private array $tags)
    {
    }

    public function render(): string
    {
        $random = new SeededRandom($this->theme->seed + 2);
        $painter = new BuildingPainter($this->theme, $this->tags, $random);
        $svg = '';
        $index = 0;

        for ($x = 0; $x < Screen::WIDTH; $x += $w) {
            $w = $random->int(self::MIN_WIDTH, 70);
            if (Screen::WIDTH - ($x + $w) < self::MIN_WIDTH) {
                $w = Screen::WIDTH - $x;
            }
            $svg .= $painter->draw($x, $w, $random->int(50, 84), $index++);
        }

        return $svg;
    }
}
