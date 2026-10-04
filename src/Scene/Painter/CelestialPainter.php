<?php

declare(strict_types=1);

namespace Vigilante\Scene\Painter;

use Vigilante\PixelArt\SvgRenderer;
use Vigilante\Scene\Palette;
use Vigilante\Scene\Svg;
use Vigilante\Scene\Theme;

/** Le soleil et la lune, dessinés pixel par pixel. */
final readonly class CelestialPainter
{
    private const CRATERS = [[-4, -3, 2], [3, 4, 3], [5, -5, 1], [-6, 5, 1]];

    public function __construct(private Theme $theme)
    {
    }

    /** Soleil couleur d'accent cerclé d'encre, rayé en bas façon affiche. */
    public function sun(int $cx = 258, int $cy = 46, int $radius = 16): string
    {
        $rows = $this->disc($radius, fn(int $x, int $y, float $d) => match (true) {
            $d <= $radius - 1 => ($y > 4 && $y % 3 === 0) ? 'p' : 'r',
            $d <= $radius + 0.5 => 'k',
            default => '.',
        });
        $palette = ['r' => $this->theme->accent, 'p' => $this->theme->sky->background(), 'k' => Palette::INK];

        return SvgRenderer::render($rows, $palette, $cx - $radius - 1, $cy - $radius - 1);
    }

    /** Lune blanche avec cratères et halo. */
    public function moon(int $cx = 258, int $cy = 40, int $radius = 14): string
    {
        $rows = $this->disc($radius, function (int $x, int $y, float $d) use ($radius) {
            if ($d > $radius) {
                return '.';
            }
            foreach (self::CRATERS as [$kx, $ky, $kr]) {
                if (($x - $kx) ** 2 + ($y - $ky) ** 2 <= $kr * $kr) {
                    return 'c';
                }
            }

            return $x + $y > $radius * 0.7 ? 'b' : 'a';
        });
        $palette = ['a' => Palette::PAPER, 'b' => '#c9c3b2', 'c' => '#a8a290'];

        return $this->halo($cx, $cy, $radius) . SvgRenderer::render($rows, $palette, $cx - $radius - 1, $cy - $radius - 1);
    }

    private function halo(int $cx, int $cy, int $radius): string
    {
        $svg = '';
        foreach ([[$radius + 8, 0.06], [$radius + 4, 0.1]] as [$r, $opacity]) {
            for ($y = -$r; $y <= $r; $y++) {
                $half = (int) floor(sqrt($r * $r - $y * $y));
                $svg .= Svg::rect($cx - $half, $cy + $y, $half * 2 + 1, 1, $this->theme->accent, Svg::opacity($opacity));
            }
        }

        return $svg;
    }

    /**
     * Grille d'un disque : $pixel(x, y, distance au centre) choisit le caractère de chaque pixel.
     *
     * @param callable(int, int, float): string $pixel
     * @return list<string>
     */
    private function disc(int $radius, callable $pixel): array
    {
        $rows = [];
        for ($y = -$radius - 1; $y <= $radius + 1; $y++) {
            $row = '';
            for ($x = -$radius - 1; $x <= $radius + 1; $x++) {
                $row .= $pixel($x, $y, sqrt($x * $x + $y * $y));
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
