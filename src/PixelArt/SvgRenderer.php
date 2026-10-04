<?php

declare(strict_types=1);

namespace Vigilante\PixelArt;

/** Convertit une grille en <rect> SVG, en fusionnant les pixels identiques côte à côte. */
final class SvgRenderer
{
    /**
     * @param list<string> $grid
     * @param array<string, string> $palette caractère => couleur CSS
     */
    public static function render(array $grid, array $palette, int $x = 0, int $y = 0): string
    {
        $svg = '';

        foreach ($grid as $dy => $row) {
            $length = strlen($row);
            for ($dx = 0; $dx < $length; $dx += $run) {
                $char = $row[$dx];
                $run = 1;
                while ($dx + $run < $length && $row[$dx + $run] === $char) {
                    $run++;
                }
                if ($char !== Grid::EMPTY && isset($palette[$char])) {
                    $svg .= sprintf(
                        '<rect x="%d" y="%d" width="%d" height="1" fill="%s"/>',
                        $x + $dx,
                        $y + $dy,
                        $run,
                        $palette[$char],
                    );
                }
            }
        }

        return $svg;
    }
}
