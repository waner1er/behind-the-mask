<?php

declare(strict_types=1);

namespace Vigilante\Scene\Layer;

use Vigilante\Scene\Palette;
use Vigilante\Scene\Screen;
use Vigilante\Scene\Svg;
use Vigilante\Scene\Theme;
use Vigilante\Support\SeededRandom;

/** Arrière-plan lointain : silhouettes de gratte-ciels et colonnes de fumée. */
final readonly class SkylineLayer implements SceneLayer
{
    private SeededRandom $random;

    public function __construct(private Theme $theme)
    {
        $this->random = new SeededRandom($theme->seed + 1);
    }

    public function render(): string
    {
        $svg = '';
        for ($x = 0; $x < Screen::WIDTH; $x += $w) {
            $w = $this->random->int(10, 26);
            if (Screen::WIDTH - ($x + $w) < 10) {
                $w = Screen::WIDTH - $x;
            }
            $svg .= $this->tower($x, $w);
        }

        return $svg . $this->smoke();
    }

    private function tower(int $x, int $w): string
    {
        $color = $this->theme->sky->skyline();
        $top = Screen::GROUND - $this->random->int(40, 100);
        $svg = Svg::rect($x, $top, $w, Screen::GROUND - $top, $color);

        for ($wy = $top + 4; $wy < Screen::GROUND - 4; $wy += 4) {
            for ($wx = $x + 2; $wx < $x + $w - 2; $wx += 3) {
                if ($this->random->chance(16)) {
                    $svg .= Svg::rect($wx, $wy, 1, 2, $this->theme->sky->windows());
                }
            }
        }

        if ($w > 14 && $this->random->oneIn(3)) {
            $step = $this->random->int(4, 10);
            $top -= $step;
            $svg .= Svg::rect($x + 3, $top, $w - 6, $step, $color);
        }

        if ($this->random->oneIn(4)) {
            $height = $this->random->int(6, 14);
            $ax = $x + intdiv($w, 2);
            $svg .= Svg::rect($ax, $top - $height, 1, $height, $color);
            $svg .= Svg::rect($ax, $top - $height - 1, 1, 1, $this->theme->accent, Svg::blink($this->random->tenths(20)));
        }

        return $svg;
    }

    /** Fumée des incendies, d'autant plus présente que le chaos monte. */
    private function smoke(): string
    {
        $svg = '';
        for ($i = 0, $count = (int) round($this->theme->chaos * 4); $i < $count; $i++) {
            $x = $this->random->int(10, Screen::WIDTH - 30);
            $base = Screen::GROUND - $this->random->int(40, 70);
            $plume = '';
            for ($j = 0; $j < 12; $j++) {
                $width = 6 + (int) ($j * 1.6);
                $sway = (int) round(sin($j * 0.7 + $i) * 3) + $j;
                $plume .= Svg::rect($x + $sway - intdiv($width, 2), $base - $j * 7, $width, 8, Palette::SMOKE, Svg::opacity(0.55 - $j * 0.04));
            }
            $svg .= sprintf('<g class="smoke" style="animation-delay:-%ds">%s</g>', $this->random->int(0, 8), $plume);
        }

        return $svg;
    }
}
