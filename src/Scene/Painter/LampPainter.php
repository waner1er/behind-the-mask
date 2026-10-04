<?php

declare(strict_types=1);

namespace Vigilante\Scene\Painter;

use Vigilante\Scene\Palette;
use Vigilante\Scene\Screen;
use Vigilante\Scene\Svg;

/** Lampadaire et son cône de lumière. */
final class LampPainter
{
    private const TOP = 76;

    public static function draw(int $x, bool $flicker): string
    {
        $class = $flicker ? 'lamp-light lamp-light--flicker' : 'lamp-light';
        $ground = Screen::GROUND;
        $height = $ground + 4 - self::TOP;

        return "<g class=\"$class\">" . self::lightCone($x) . '</g>'
            . Svg::rect($x, self::TOP, 2, $height, Palette::INK)
            . Svg::rect($x + 1, self::TOP, 1, $height, Palette::CONCRETE)
            . Svg::rect($x - 1, $ground, 4, 4, Palette::INK)
            . Svg::rect($x, self::TOP - 2, 10, 2, Palette::INK)
            . Svg::rect($x + 6, self::TOP, 8, 2, Palette::INK)
            . Svg::rect($x + 7, self::TOP + 2, 6, 1, Palette::LAMPLIGHT, "class=\"$class\"");
    }

    private static function lightCone(int $x): string
    {
        $svg = '';
        for ($i = 0, $y = self::TOP + 3; $y < Screen::GROUND + 8; $i++, $y += 8) {
            $half = 3 + $i * 3;
            $svg .= Svg::rect($x + 10 - $half, $y, $half * 2, 8, Palette::LAMPLIGHT, 'opacity=".06"');
        }

        return $svg . Svg::rect($x - 10, Screen::GROUND + 3, 40, 6, Palette::LAMPLIGHT, 'opacity=".12"');
    }
}
