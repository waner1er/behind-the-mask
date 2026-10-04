<?php

declare(strict_types=1);

namespace Vigilante\Scene\Painter;

use Vigilante\Scene\Palette;
use Vigilante\Scene\Svg;

/** Ce qui dépasse des toits, dessiné au trait blanc. */
final class RooftopPainter
{
    public static function waterTower(int $x, int $roof): string
    {
        $svg = Svg::rect($x + 2, $roof - 5, 1, 5, Palette::INK) . Svg::rect($x + 10, $roof - 5, 1, 5, Palette::INK)
            . Svg::rect($x + 2, $roof - 3, 9, 1, Palette::INK)
            . Svg::rect($x, $roof - 17, 13, 12, Palette::INK)
            . Svg::rect($x, $roof - 17, 1, 12, Palette::PAPER) . Svg::rect($x + 12, $roof - 17, 1, 12, Palette::PAPER);
        for ($y = $roof - 15; $y < $roof - 5; $y += 3) {
            $svg .= Svg::rect($x + 1, $y, 11, 1, Palette::CONCRETE);
        }

        return $svg
            . Svg::rect($x + 1, $roof - 19, 11, 2, Palette::INK)
            . Svg::rect($x + 1, $roof - 19, 11, 1, Palette::PAPER)
            . Svg::rect($x + 4, $roof - 20, 5, 1, Palette::INK);
    }

    public static function airConditioner(int $x, int $roof): string
    {
        return Svg::rect($x + 6, $roof - 5, 10, 5, Palette::INK)
            . Svg::rect($x + 6, $roof - 5, 10, 1, Palette::PAPER)
            . Svg::rect($x + 8, $roof - 3, 6, 1, Palette::CONCRETE);
    }
}
