<?php

declare(strict_types=1);

namespace Vigilante\PixelArt;

/** Opérations sur une grille de pixels (liste de chaînes de même longueur). */
final class Grid
{
    public const EMPTY = '.';

    /** @return list<string> */
    public static function blank(int $width, int $height): array
    {
        return array_fill(0, $height, str_repeat(self::EMPTY, $width));
    }

    /**
     * Supprime les colonnes vides à gauche et à droite.
     *
     * @param list<string> $grid
     * @return list<string>
     */
    public static function trimColumns(array $grid): array
    {
        $used = array_filter(
            range(0, strlen($grid[0]) - 1),
            fn(int $x) => array_filter($grid, fn(string $row) => $row[$x] !== self::EMPTY) !== [],
        );
        $from = min($used);
        $length = max($used) - $from + 1;

        return array_map(fn(string $row) => substr($row, $from, $length), $grid);
    }
}
