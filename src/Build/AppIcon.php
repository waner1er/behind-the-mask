<?php

declare(strict_types=1);

namespace Vigilante\Build;

use Vigilante\Export\OpenBor\IndexedImage;
use Vigilante\Export\OpenBor\PngEncoder;
use Vigilante\PixelArt\Grid;
use Vigilante\Sprite\Character\HeroBuilder;

/**
 * L'icône de l'app iOS, en pixel art comme le jeu : Pete en garde devant le soleil rouge
 * de l'écran titre, sur la nuit. Dessinée sur 64 × 64 pixels, agrandie × 16 (1024 × 1024).
 */
final class AppIcon
{
    public const SIZE = 64;
    public const SCALE = 16;

    /** Lettres libres dans la palette du héros. */
    private const PALETTE = [
        'B' => '#0c0a10', // nuit (INK de js/config.js)
        'R' => '#e8203a', // soleil
        'L' => '#ece8dc', // reflets du soleil, comme à l'écran titre
        'U' => '#2a2826', // trottoir
    ];

    private const SUN_X = 32;
    private const SUN_Y = 27;
    private const SUN_RADIUS = 22;
    private const GROUND = 55;

    /** Le katana allonge le sprite à droite : on décale pour centrer le corps. */
    private const HERO_SHIFT = 4;

    /** PNG opaque 1024 × 1024 : l'App Store refuse toute transparence. */
    public static function png(): string
    {
        $grid = self::grid();
        $palette = self::PALETTE + HeroBuilder::build()->palette;

        $chars = [];
        $big = [];
        foreach ($grid as $row) {
            $chars += array_fill_keys(str_split($row), true);
            $line = '';
            foreach (str_split($row) as $char) {
                $line .= str_repeat($char, self::SCALE);
            }
            array_push($big, ...array_fill(0, self::SCALE, $line));
        }
        $chars = array_map('strval', array_keys($chars));

        return PngEncoder::encode(IndexedImage::fromGrid($big, $chars, $palette), opaque: true);
    }

    /** @return list<string> */
    public static function grid(): array
    {
        $grid = [];
        for ($y = 0; $y < self::SIZE; $y++) {
            $row = '';
            for ($x = 0; $x < self::SIZE; $x++) {
                $row .= $y >= self::GROUND ? self::street($x, $y) : self::sky($x, $y);
            }
            $grid[] = $row;
        }

        return self::paste($grid, self::hero(), self::GROUND + 1);
    }

    /** Le soleil détouré, barré de deux reflets en bas, comme à l'écran titre. */
    private static function sky(int $x, int $y): string
    {
        $distance = hypot($x - self::SUN_X + 0.5, $y - self::SUN_Y + 0.5);
        if ($distance > self::SUN_RADIUS) {
            return 'B';
        }
        if ($distance > self::SUN_RADIUS - 1) {
            return 'K';
        }
        $stripe = $y - self::SUN_Y;

        return in_array($stripe, [10, 11, 15], true) ? 'L' : 'R';
    }

    /** Le trottoir et les pointillés de la route. */
    private static function street(int $x, int $y): string
    {
        return $y >= self::GROUND + 4 && $y <= self::GROUND + 5 && ($x + 2) % 10 < 6 ? 'L' : 'U';
    }

    /**
     * Pete en garde, rogné au plus près.
     *
     * @return list<string>
     */
    private static function hero(): array
    {
        $rows = array_values(array_filter(
            HeroBuilder::build()->frames['idle'][1],
            static fn (string $row): bool => trim($row, Grid::EMPTY) !== '',
        ));

        return Grid::trimColumns($rows);
    }

    /**
     * Pose le sprite centré, les pieds sur la ligne $feet.
     *
     * @param list<string> $grid
     * @param list<string> $sprite
     * @return list<string>
     */
    private static function paste(array $grid, array $sprite, int $feet): array
    {
        $left = intdiv(self::SIZE - strlen($sprite[0]), 2) + self::HERO_SHIFT;
        $top = $feet - count($sprite);
        foreach ($sprite as $dy => $row) {
            for ($dx = 0, $length = strlen($row); $dx < $length; $dx++) {
                if ($row[$dx] !== Grid::EMPTY) {
                    $grid[$top + $dy][$left + $dx] = $row[$dx];
                }
            }
        }

        return $grid;
    }
}
