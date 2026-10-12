<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Layer;
use Vigilante\Support\SeededRandom;

/**
 * Le vinyle qui explose en notes de musique (Explosions.blast + Ambience du jeu web) :
 * 12 notes partent du point d'impact, ondulent en montant, retombent et clignotent avant
 * de disparaître (70 images). Rendu image par image, avec un tirage fixe.
 */
final class NoteBurst
{
    /** Mêmes notes et mêmes couleurs que js/render/SpriteBank.js (NOTE_GRIDS, NOTE_COLORS). */
    private const GRIDS = [
        ['....##.', '....#.#', '....#..', '....#..', '..###..', '.####..', '..##...'],
        ['.######', '.#....#', '.#....#', '.#....#', '##...##', '##..###', '.....##'],
    ];
    public const COLORS = ['1' => '#ffd23f', '2' => '#e8203a', '3' => '#ece8dc', '4' => '#3ef0ff', '5' => '#ff3ea5'];

    private const WIDTH = 220;
    private const HEIGHT = 130;
    /** Point d'impact dans l'image (au sol). */
    public const ANCHOR_X = 110;
    public const ANCHOR_Y = 100;

    /** Une image toutes les 4 images du jeu web. */
    public const STEP = 4;
    private const LIFE = 70;

    /**
     * @param list<string> $blast éclat du premier instant, centré sur le point d'impact
     * @return array<string, list<string>> « burst.0 », « burst.1 »...
     */
    public static function frames(array $blast): array
    {
        $random = new SeededRandom(1987);
        $notes = [];
        for ($i = 0; $i < 12; $i++) {
            $notes[] = [
                'x' => $random->int(-6, 6), 'y' => -$random->int(8, 20),
                'vx' => $random->int(-18, 18) / 10, 'vy' => -$random->int(12, 30) / 10,
                'grid' => self::GRIDS[$i % 2], 'color' => (string) ($i % 5 + 1),
            ];
        }

        $frames = [];
        for ($t = 0, $n = 0; $t < self::LIFE; $t += self::STEP, $n++) {
            $layers = [];
            if ($t < 12) {
                $layers[] = new Layer($blast, self::ANCHOR_X - intdiv(strlen($blast[0]), 2), self::ANCHOR_Y - count($blast) + 4);
            }
            foreach ($notes as $note) {
                if ($t > 50 && $t % 8 < 4) {
                    continue; // clignote avant de disparaître
                }
                [$x, $y] = self::position($note, $t);
                $layers[] = (new Layer($note['grid'], self::ANCHOR_X + (int) round($x), self::ANCHOR_Y + (int) round($y)))
                    ->recolor(['#' => $note['color']]);
            }
            $frames["burst.$n"] = Compositor::compose(self::WIDTH, self::HEIGHT, $layers, 'K', 0);
        }

        return $frames;
    }

    /**
     * Position d'une note après $t images (Ambience : x += vx + sin(t/6) × 0,4 ; y += vy ; vy += 0,05).
     *
     * @param array{x: int, y: int, vx: float, vy: float} $note
     * @return array{float, float}
     */
    private static function position(array $note, int $t): array
    {
        $x = $note['x'];
        $y = $note['y'];
        $vy = $note['vy'];
        for ($i = 1; $i <= $t; $i++) {
            $x += $note['vx'] + sin($i / 6) * 0.4;
            $y += $vy;
            $vy += 0.05;
        }

        return [$x, $y];
    }
}
