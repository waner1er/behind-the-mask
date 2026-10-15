<?php

declare(strict_types=1);

namespace Vigilante\Sprite;

use Vigilante\PixelArt\PixelFont;

/**
 * Le conteneur à verre du niveau bonus (Beer Church) : une colonne verte à dôme, deux trous
 * pour les bouteilles et une étiquette. Quatre états, du neuf au tas de verre : « bottlebank0 » à « bottlebank3 ».
 * Couleurs de PropCatalog::PALETTE.
 */
final class BottleBank
{
    public const STAGES = 4;

    private const WIDTH = 56;
    private const HEIGHT = 58;

    /** Les trous à bouteilles (centre, rayon) et l'étiquette (lignes de début et de fin). */
    private const HOLES = [[17, 17], [38, 17]];
    private const HOLE_RADIUS = 4;
    private const LABEL = [27, 39];

    /** Fissures (K) et éclats manquants (.) ajoutés à chaque état : [x, y, longueur, direction]. */
    private const CRACKS = [
        1 => [[12, 20, 8, 1], [40, 44, 7, -1], [27, 50, 6, 1]],
        2 => [[42, 14, 11, 1], [9, 41, 10, -1], [22, 26, 8, 1], [33, 34, 12, 1], [14, 48, 8, 1]],
    ];

    /** @return array<string, list<string>> */
    public static function stages(): array
    {
        $grid = self::intact();
        $stages = ['bottlebank0' => $grid];
        foreach (self::CRACKS as $stage => $cracks) {
            foreach ($cracks as [$x, $y, $length, $dir]) {
                $grid = self::crack($grid, $x, $y, $length, $dir);
            }
            $stages["bottlebank$stage"] = $grid;
        }
        $stages['bottlebank3'] = self::smashed($grid);

        return $stages;
    }

    /** @return list<string> */
    private static function intact(): array
    {
        $rows = [];
        $cx = (self::WIDTH - 1) / 2;
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $row = '';
            // dôme arrondi sur les 12 premières lignes, puis colonne droite
            $half = $y < 12 ? (int) round(sqrt(max(0, 144 - (12 - $y) ** 2)) * 2.1) : 26;
            for ($x = 0; $x < self::WIDTH; $x++) {
                $row .= abs($x - $cx) > $half ? '.' : self::bodyPixel($x, $y, $cx);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private static function bodyPixel(int $x, int $y, float $cx): string
    {
        $distance = min(array_map(fn(array $hole) => sqrt(($x - $hole[0]) ** 2 + ($y - $hole[1]) ** 2), self::HOLES));
        [$top, $bottom] = self::LABEL;

        return match (true) {
            $y >= self::HEIGHT - 3 => 'D',                           // socle
            $distance <= self::HOLE_RADIUS - 1 => 'P',                // trous à bouteilles
            $distance <= self::HOLE_RADIUS + 0.5 => 'w',              // joint en caoutchouc
            $y >= $top && $y <= $bottom && abs($x - $cx) < 19 => self::label($x - (int) ($cx - 18), $y - $top),
            $x - $cx < -21 => 'g',                                    // reflet
            $x - $cx > 20 => 'O',                                     // ombre
            default => $y % 11 === 10 ? 'o' : 'G',                    // anneaux moulés
        };
    }

    /** Étiquette blanche bordée de gris : « GLASS » en vert, une bouteille dessous. */
    private static function label(int $x, int $y): string
    {
        static $text = null;
        $text ??= PixelFont::write('GLASS', 'G');
        $tx = $x - 9;
        if ($y === 0 || $y === 12) {
            return 'w';
        }
        if ($y >= 2 && $y < 2 + PixelFont::HEIGHT && $tx >= 0 && $tx < strlen($text[0])) {
            return $text[$y - 2][$tx] === 'G' ? 'G' : 'b';
        }
        $bottle = ($x >= 17 && $x <= 19 && $y >= 8) || ($x === 18 && $y === 7);

        return $bottle && $y <= 11 ? 'G' : 'b';
    }

    /**
     * Une fissure en zigzag ; un pixel sur trois saute (éclat manquant).
     *
     * @param list<string> $grid
     * @return list<string>
     */
    private static function crack(array $grid, int $x, int $y, int $length, int $dir): array
    {
        for ($i = 0; $i < $length; $i++) {
            $px = $x + $i * $dir;
            $py = $y + intdiv($i, 2) + ($i % 2);
            if (isset($grid[$py][$px]) && $grid[$py][$px] !== '.') {
                $grid[$py][$px] = $i % 3 === 2 ? 'P' : 'K';
            }
        }

        return $grid;
    }

    /**
     * Le conteneur éventré : il ne reste que le bas, au bord déchiqueté, et un tas de verre.
     *
     * @param list<string> $grid
     * @return list<string>
     */
    private static function smashed(array $grid): array
    {
        foreach ($grid as $y => $row) {
            for ($x = 0; $x < self::WIDTH; $x++) {
                $edge = 38 + (int) round(2 * sin($x * 0.9) + 2 * sin($x * 0.37));
                if ($y < $edge) {
                    $grid[$y][$x] = '.';
                } elseif ($y >= self::HEIGHT - 5 && $row[$x] === '.' && ($x * 7 + $y) % 3 === 0) {
                    $grid[$y][$x] = ($x + $y) % 2 ? 'g' : 'W'; // éclats de verre par terre
                }
            }
        }

        return $grid;
    }
}
