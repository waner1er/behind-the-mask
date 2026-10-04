<?php

declare(strict_types=1);

namespace Vigilante\PixelArt;

/** Trait de 2 px d'épaisseur (bâton, lame, chaîne...). */
final class Line
{
    /** @param string|null $shade couleur de la 2e rangée de pixels, pour donner du volume */
    public static function between(int $x0, int $y0, int $x1, int $y1, string $main, ?string $shade = null): Layer
    {
        $steps = max(abs($x1 - $x0), abs($y1 - $y0), 1);
        $horizontal = abs($x1 - $x0) >= abs($y1 - $y0);
        $shade ??= $main;
        $rows = [];

        $set = function (int $x, int $y, string $char) use (&$rows): void {
            $rows[$y] = str_pad($rows[$y] ?? '', $x + 1, Grid::EMPTY);
            $rows[$y][$x] = $char;
        };

        for ($i = 0; $i <= $steps; $i++) {
            $x = (int) round($x0 + ($x1 - $x0) * $i / $steps);
            $y = (int) round($y0 + ($y1 - $y0) * $i / $steps);
            $set($x, $y, $main);
            $horizontal ? $set($x, $y + 1, $shade) : $set($x + 1, $y, $shade);
        }

        $top = min(array_keys($rows));
        $bottom = max(array_keys($rows));
        $result = [];
        for ($y = $top; $y <= $bottom; $y++) {
            $result[] = $rows[$y] ?? '';
        }

        return new Layer($result, 0, $top);
    }
}
