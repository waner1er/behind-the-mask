<?php

declare(strict_types=1);

namespace Vigilante\Scene\Painter;

use Vigilante\Scene\Palette;
use Vigilante\Scene\Svg;
use Vigilante\Scene\Theme;
use Vigilante\Support\SeededRandom;

/** Fenêtres des immeubles : éteintes, allumées, à volets, brisées, fleuries... */
final readonly class WindowPainter
{
    private const SERVER_LEDS = ['#3ef0ff', '#7dff5a', '#e8203a', '#3ef0ff'];

    public function __construct(private Theme $theme, private SeededRandom $random)
    {
    }

    public function window(int $x, int $y): string
    {
        $svg = Svg::rect($x - 1, $y - 1, 7, 9, Palette::PAPER);
        $roll = $this->random->int(0, 99);

        if ($this->theme->peace) {
            $svg .= Svg::rect($x, $y, 5, 7, $roll < 70 ? Palette::LIT_WINDOW : '#ffe9a0');

            return $svg . ($this->random->chance(45) ? $this->flowerBox($x, $y) : '');
        }

        if ($this->random->chance($this->theme->chaos * 45)) {
            return $svg . $this->broken($x, $y);
        }

        if ($roll < 55) {
            return $svg . Svg::rect($x, $y, 5, 7, Palette::INK)
                . Svg::rect($x + 3, $y + 1, 1, 1, Palette::PAPER) . Svg::rect($x + 2, $y + 2, 1, 1, Palette::PAPER);
        }

        if ($roll < 85) {
            $svg .= Svg::rect($x, $y, 5, 7, Palette::LIT_WINDOW);
            if ($roll > 76) {
                $svg .= Svg::rect($x + 1, $y + 2, 2, 2, Palette::INK) . Svg::rect($x + 1, $y + 4, 3, 3, Palette::INK);
            }

            return $svg;
        }

        return $svg . Svg::rect($x, $y, 5, 7, $roll > 95 ? $this->theme->accent : Palette::INK)
            . Svg::rect($x, $y + 1, 5, 1, Palette::CONCRETE)
            . Svg::rect($x, $y + 3, 5, 1, Palette::CONCRETE)
            . Svg::rect($x, $y + 5, 5, 1, Palette::CONCRETE);
    }

    /** Baie de serveurs aux LED qui clignotent (data center du Docteur Mask). */
    public function serverRack(int $x, int $y): string
    {
        $svg = Svg::rect($x - 1, $y - 1, 7, 10, '#3a3f4a') . Svg::rect($x, $y, 5, 8, '#0b0e14');
        for ($row = 0; $row < 4; $row++) {
            $svg .= Svg::rect($x, $y + $row * 2, 5, 1, '#1a1f2a');
            $svg .= Svg::rect(
                $x + $this->random->int(0, 3),
                $y + $row * 2,
                1,
                1,
                self::SERVER_LEDS[$this->random->int(0, 3)],
                Svg::blink($this->random->tenths(16)),
            );
        }

        return $svg;
    }

    /** Vitre brisée et traces de suie. */
    private function broken(int $x, int $y): string
    {
        return Svg::rect($x, $y, 5, 7, Palette::INK)
            . Svg::rect($x, $y, 2, 1, Palette::PAPER) . Svg::rect($x, $y + 1, 1, 2, Palette::PAPER)
            . Svg::rect($x + 4, $y + 5, 1, 2, Palette::PAPER) . Svg::rect($x + 3, $y + 6, 1, 1, Palette::PAPER)
            . Svg::rect($x - 1, $y - 4, 7, 3, Palette::SMOKE, 'opacity=".6"');
    }

    /** Jardinière fleurie (la ville libérée). */
    private function flowerBox(int $x, int $y): string
    {
        $svg = Svg::rect($x - 1, $y + 8, 7, 2, Palette::WOOD);
        for ($i = 0; $i < 3; $i++) {
            $svg .= Svg::rect($x + $i * 2, $y + 6, 1, 2, Palette::LEAF)
                . Svg::rect($x + $i * 2, $y + 5, 1, 1, $this->random->pick(DecorSprites::FLOWER_PETALS));
        }

        return Svg::animated('bloom', $this->random->tenths(30), $svg);
    }
}
