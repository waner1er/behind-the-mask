<?php

declare(strict_types=1);

namespace Vigilante\Scene\Painter;

use Vigilante\Scene\Palette;
use Vigilante\Scene\Screen;
use Vigilante\Scene\Svg;
use Vigilante\Scene\Theme;

/** Détails de façade : rez-de-chaussée, affiches, escaliers de secours, enseignes et graffitis. */
final readonly class FacadePainter
{
    /** @param list<string> $tags graffitis tirés des paroles */
    public function __construct(private Theme $theme, private array $tags)
    {
    }

    /** Bandeau, porte, vitrine et affiche au pied de l'immeuble. */
    public function groundFloor(int $x, int $w, int $index): string
    {
        $ground = Screen::GROUND;
        $base = $ground - 22;

        return Svg::rect($x + 1, $base, $w - 1, 1, Palette::PAPER) . Svg::rect($x + 1, $base + 1, $w - 1, 2, Palette::INK)
            . Svg::rect($x + 6, $ground - 12, 8, 12, Palette::PAPER) . Svg::rect($x + 7, $ground - 11, 6, 11, Palette::INK)
            . Svg::rect($x + 17, $ground - 11, $w - 23, 8, Palette::PAPER) . Svg::rect($x + 18, $ground - 10, $w - 25, 6, '#2a2824')
            . $this->poster($x + $w - 12, $ground - 20, $index);
    }

    /** Enseigne lumineuse (deux immeubles sur trois) ou graffiti tiré des paroles. */
    public function signOrGraffiti(int $center, int $w, int $index): string
    {
        $base = Screen::GROUND - 22;

        if ($index % 3 !== 2) {
            $label = $this->theme->signs[$index % count($this->theme->signs)];
            $color = $index % 2 ? Palette::PAPER : $this->theme->accent;
            $width = strlen($label) * 6 + 6;
            $flicker = $index % 4 === 1 ? ' neon--flicker' : '';

            return Svg::rect($center - intdiv($width, 2) - 1, $base - 12, $width + 2, 12, Palette::PAPER)
                . Svg::rect($center - intdiv($width, 2), $base - 11, $width, 10, Palette::INK)
                . Svg::text($center, $base - 3, $label, $color, 6, 'neon' . $flicker);
        }

        $label = $this->tags[intdiv($index, 3) % count($this->tags)];
        $size = strlen($label) * 5 > $w ? 4 : 5;
        $color = intdiv($index, 3) % 2 ? $this->theme->accent : Palette::PAPER;
        $rotate = sprintf('transform="rotate(-5 %d %d)"', $center, $base - 6);

        return Svg::text($center, $base - 4, $label, $color, $size, 'graffiti', $rotate);
    }

    /** @param list<int> $floors ordonnée de chaque étage */
    public function fireEscape(int $x, array $floors): string
    {
        $svg = '';
        foreach ($floors as $i => $y) {
            $level = $y + 8;
            $svg .= Svg::rect($x, $level, 18, 1, Palette::PAPER) . Svg::rect($x, $level - 4, 18, 1, Palette::PAPER);
            for ($p = 0; $p < 18; $p += 4) {
                $svg .= Svg::rect($x + $p, $level - 4, 1, 4, Palette::PAPER);
            }
            if (isset($floors[$i + 1])) {
                for ($k = 0; $k < 12; $k++) {
                    $svg .= Svg::rect($x + 3 + $k, $level + 1 + $k, 1, 1, Palette::PAPER);
                }
            }
        }

        return $svg;
    }

    /** Affiche de concert collée sur le mur (avec le masque). */
    private function poster(int $x, int $y, int $index): string
    {
        $paper = $index % 2 ? Palette::LIT_WINDOW : $this->theme->accent;
        $ink = $index % 2 ? $this->theme->accent : Palette::INK;

        return Svg::rect($x, $y, 8, 11, $paper)
            . Svg::rect($x + 1, $y + 1, 6, 1, $ink)
            . Svg::rect($x + 2, $y + 3, 4, 4, Palette::INK)
            . Svg::rect($x + 3, $y + 4, 1, 1, $paper) . Svg::rect($x + 5, $y + 4, 1, 1, $paper)
            . Svg::rect($x + 1, $y + 8, 6, 1, $ink)
            . Svg::rect($x + 1, $y + 10, 4, 1, $ink);
    }
}
