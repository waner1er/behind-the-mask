<?php

declare(strict_types=1);

namespace Vigilante\PixelArt;

/**
 * Empile des calques dans une grille. Chaque calque reçoit un contour sombre
 * d'un pixel : c'est ce qui donne le look « Metal Slug ».
 */
final class Compositor
{
    /**
     * @param list<Layer> $layers du fond vers l'avant
     * @param string|null $outline caractère du contour, null pour ne pas détourer
     * @param int $pad marge autour de la grille (pour que le contour ne soit pas coupé)
     * @return list<string>
     */
    public static function compose(int $width, int $height, array $layers, ?string $outline = 'K', int $pad = 1): array
    {
        $width += $pad * 2;
        $height += $pad * 2;
        $grid = Grid::blank($width, $height);

        foreach ($layers as $layer) {
            $mask = self::place($width, $height, $layer->shift($pad, $pad));

            if ($outline !== null) {
                $grid = self::paint($grid, self::outline($mask, $outline));
            }
            $grid = self::paint($grid, $mask);
        }

        return $grid;
    }

    /** @return list<string> */
    private static function place(int $width, int $height, Layer $layer): array
    {
        $mask = Grid::blank($width, $height);

        foreach ($layer->rows as $dy => $row) {
            $y = $layer->y + $dy;
            if ($y < 0 || $y >= $height) {
                continue;
            }
            for ($dx = 0, $length = strlen($row); $dx < $length; $dx++) {
                $x = $layer->x + $dx;
                if ($row[$dx] === Grid::EMPTY || $x < 0 || $x >= $width) {
                    continue;
                }
                $mask[$y][$x] = $layer->map[$row[$dx]] ?? $row[$dx];
            }
        }

        return $mask;
    }

    /**
     * Tout pixel vide qui touche (en croix) un pixel plein.
     *
     * @param list<string> $mask
     * @return list<string>
     */
    private static function outline(array $mask, string $color): array
    {
        $height = count($mask);
        $width = strlen($mask[0]);
        $result = Grid::blank($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($mask[$y][$x] === Grid::EMPTY && self::touchesPixel($mask, $x, $y, $width, $height)) {
                    $result[$y][$x] = $color;
                }
            }
        }

        return $result;
    }

    /** @param list<string> $mask */
    private static function touchesPixel(array $mask, int $x, int $y, int $width, int $height): bool
    {
        foreach ([[0, -1], [0, 1], [-1, 0], [1, 0]] as [$dx, $dy]) {
            $nx = $x + $dx;
            $ny = $y + $dy;
            // bornes explicites : $chaine[-1] est valide en PHP (dernier caractère)
            if ($nx >= 0 && $ny >= 0 && $nx < $width && $ny < $height && $mask[$ny][$nx] !== Grid::EMPTY) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copie les pixels non vides de $layer par-dessus $grid.
     *
     * @param list<string> $grid
     * @param list<string> $layer
     * @return list<string>
     */
    private static function paint(array $grid, array $layer): array
    {
        foreach ($layer as $y => $row) {
            for ($x = 0, $length = strlen($row); $x < $length; $x++) {
                if ($row[$x] !== Grid::EMPTY) {
                    $grid[$y][$x] = $row[$x];
                }
            }
        }

        return $grid;
    }
}
