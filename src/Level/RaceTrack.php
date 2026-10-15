<?php

declare(strict_types=1);

namespace Vigilante\Level;

use Vigilante\Support\SeededRandom;

/**
 * La descente en skate d'un niveau (clé « race » de config/levels.php), telle que la lit le JavaScript :
 * le tracé (virages, bosses), le bord de la route, les obstacles, les bonus et les ennemis.
 *
 * Distances en segments de route ; x = position en travers, -1 et 1 = les bordures du trottoir.
 */
final class RaceTrack
{
    /** Pas de pièges au départ ni dans la ligne droite d'arrivée. */
    private const CLEAR_START = 80;
    private const CLEAR_END = 60;

    /**
     * @param array<string, mixed> $race la clé « race » du niveau
     * @param list<string> $signs enseignes du thème : textes des panneaux publicitaires
     * @return array<string, mixed>
     */
    public static function build(array $race, array $signs): array
    {
        /** @var list<array{int, int|float, int|float}> $sections */
        $sections = $race['sections'];
        $length = array_sum(array_map(fn(array $section) => $section[0], $sections));
        $random = new SeededRandom((int) $race['seed']);

        return [
            'sections' => $sections,
            'length' => $length,
            'finish' => $length - 30,
            'roadside' => self::roadside($length, $signs, $random),
            'obstacles' => self::obstacles($race['obstacles'], $length, $random),
            'pickups' => self::pickups($length, $random),
            'traffic' => $race['traffic'],
        ];
    }

    /**
     * Palmiers des deux côtés, lampadaires, et de temps en temps un donut géant ou un panneau.
     *
     * @param list<string> $signs
     * @return list<array{at: int, sprite: string, x: float}>
     */
    private static function roadside(int $length, array $signs, SeededRandom $random): array
    {
        $props = [];
        for ($at = 10; $at < $length + 40; $at += 8) {
            $props[] = ['at' => $at, 'sprite' => 'palm', 'x' => -1.5 - $random->int(0, 3) / 10];
            $props[] = ['at' => $at + 4, 'sprite' => 'palm', 'x' => 1.5 + $random->int(0, 3) / 10];
            if ($at % 24 === 2) {
                $props[] = ['at' => $at + 2, 'sprite' => 'lamp', 'x' => -1.18];
                $props[] = ['at' => $at + 6, 'sprite' => 'lamp', 'x' => 1.18];
            }
            if ($random->oneIn(6)) {
                $sprite = $random->oneIn(3) || $signs === [] ? 'donut' : 'sign-' . $random->pick($signs);
                $props[] = ['at' => $at + 2, 'sprite' => $sprite, 'x' => ($random->oneIn(2) ? -1 : 1) * 2.4];
            }
        }

        return $props;
    }

    /**
     * @param array{every: int, pool: list<string>} $config
     * @return list<array{at: int, sprite: string, x: float}>
     */
    private static function obstacles(array $config, int $length, SeededRandom $random): array
    {
        $obstacles = [];
        for ($at = self::CLEAR_START; $at < $length - self::CLEAR_END; $at += $config['every'] + $random->int(-10, 10)) {
            $obstacles[] = ['at' => $at, 'sprite' => $random->pick($config['pool']), 'x' => $random->int(-7, 7) / 10];
        }

        return $obstacles;
    }

    /**
     * Des cœurs et des caisses de vinyles sur la chaussée ; le Wall of Death à mi-parcours, pour le boss.
     *
     * @return list<array{at: int, kind: string, x: float}>
     */
    private static function pickups(int $length, SeededRandom $random): array
    {
        $pickups = [['at' => intdiv($length, 2), 'kind' => 'wod', 'x' => 0.0]];
        for ($at = 150; $at < $length - self::CLEAR_END; $at += 170) {
            $pickups[] = ['at' => $at, 'kind' => $random->oneIn(3) ? 'life' : 'vinyls', 'x' => $random->int(-6, 6) / 10];
        }

        return $pickups;
    }
}
