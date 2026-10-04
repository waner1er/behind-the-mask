<?php

/**
 * Mini moteur de pixel art.
 *
 * Un sprite est décrit en texte : 1 caractère = 1 pixel, '.' = transparent.
 * Chaque caractère correspond à une couleur de la palette.
 * On empile des calques (jambes, torse, tête, bras...) et chaque calque
 * reçoit automatiquement un contour sombre : c'est ce qui donne le look "Metal Slug".
 */
final class PixelArt
{
    public const EMPTY = '.';

    /**
     * Superpose des calques et renvoie la grille finale (une chaîne par ligne).
     *
     * Un calque : ['rows' => string[], 'x' => int, 'y' => int, 'map' => ['A' => 'B', ...]]
     * 'map' permet de recolorer un calque (ex : la jambe du fond plus sombre).
     *
     * @param array<int, array{rows: string[], x?: int, y?: int, map?: array<string, string>}> $layers
     * @return string[]
     */
    public static function compose(int $width, int $height, array $layers, ?string $outline = 'K', int $pad = 1): array
    {
        $width += $pad * 2;
        $height += $pad * 2;
        $grid = self::blank($width, $height);

        foreach ($layers as $layer) {
            $layer['x'] = ($layer['x'] ?? 0) + $pad;
            $layer['y'] = ($layer['y'] ?? 0) + $pad;
            $mask = self::place($width, $height, $layer);

            if ($outline !== null) {
                $grid = self::paint($grid, self::outline($mask, $outline));
            }
            $grid = self::paint($grid, $mask);
        }

        return $grid;
    }

    /**
     * Convertit une grille en rectangles SVG.
     * Les pixels identiques côte à côte sont fusionnés en un seul <rect>.
     *
     * @param string[] $grid
     * @param array<string, string> $palette
     */
    public static function toSvg(array $grid, array $palette, int $x = 0, int $y = 0): string
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
                if ($char !== self::EMPTY && isset($palette[$char])) {
                    $svg .= sprintf(
                        '<rect x="%d" y="%d" width="%d" height="1" fill="%s"/>',
                        $x + $dx,
                        $y + $dy,
                        $run,
                        $palette[$char]
                    );
                }
            }
        }

        return $svg;
    }

    /**
     * Calque contenant un trait de 2 px d'épaisseur (bâton, arme...).
     * $shade colore la 2e rangée de pixels pour donner du volume.
     */
    public static function line(int $x0, int $y0, int $x1, int $y1, string $main, ?string $shade = null): array
    {
        $steps = max(abs($x1 - $x0), abs($y1 - $y0), 1);
        $horizontal = abs($x1 - $x0) >= abs($y1 - $y0);
        $rows = [];

        $set = function (int $x, int $y, string $char) use (&$rows) {
            $rows[$y] = str_pad($rows[$y] ?? '', $x + 1, self::EMPTY);
            $rows[$y][$x] = $char;
        };

        for ($i = 0; $i <= $steps; $i++) {
            $x = (int) round($x0 + ($x1 - $x0) * $i / $steps);
            $y = (int) round($y0 + ($y1 - $y0) * $i / $steps);
            $set($x, $y, $main);
            $horizontal ? $set($x, $y + 1, $shade ?? $main) : $set($x + 1, $y, $shade ?? $main);
        }

        $top = min(array_keys($rows));
        $result = [];
        for ($y = $top, $bottom = max(array_keys($rows)); $y <= $bottom; $y++) {
            $result[] = $rows[$y] ?? '';
        }

        return ['y' => $top, 'rows' => $result];
    }

    /** @return string[] */
    private static function blank(int $width, int $height): array
    {
        return array_fill(0, $height, str_repeat(self::EMPTY, $width));
    }

    /** Dessine un calque seul dans une grille vide. */
    private static function place(int $width, int $height, array $layer): array
    {
        $mask = self::blank($width, $height);
        $map = $layer['map'] ?? [];

        foreach ($layer['rows'] as $dy => $row) {
            $y = $layer['y'] + $dy;
            if ($y < 0 || $y >= $height) {
                continue;
            }
            for ($dx = 0, $length = strlen($row); $dx < $length; $dx++) {
                $x = $layer['x'] + $dx;
                if ($row[$dx] === self::EMPTY || $x < 0 || $x >= $width) {
                    continue;
                }
                $mask[$y][$x] = $map[$row[$dx]] ?? $row[$dx];
            }
        }

        return $mask;
    }

    /** Calcule le contour : tout pixel vide qui touche un pixel plein. */
    private static function outline(array $mask, string $color): array
    {
        $height = count($mask);
        $width = strlen($mask[0]);
        $result = self::blank($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($mask[$y][$x] !== self::EMPTY) {
                    continue;
                }
                foreach ([[0, -1], [0, 1], [-1, 0], [1, 0]] as [$dx, $dy]) {
                    $nx = $x + $dx;
                    $ny = $y + $dy;
                    // attention : $chaine[-1] est valide en PHP (dernier caractère), d'où les bornes
                    if ($nx < 0 || $ny < 0 || $nx >= $width || $ny >= $height) {
                        continue;
                    }
                    if ($mask[$ny][$nx] !== self::EMPTY) {
                        $result[$y][$x] = $color;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /** Copie les pixels non vides de $layer par-dessus $grid. */
    private static function paint(array $grid, array $layer): array
    {
        foreach ($layer as $y => $row) {
            for ($x = 0, $length = strlen($row); $x < $length; $x++) {
                if ($row[$x] !== self::EMPTY) {
                    $grid[$y][$x] = $row[$x];
                }
            }
        }

        return $grid;
    }
}
