<?php

declare(strict_types=1);

namespace Vigilante\Level;

use Vigilante\Scene\Screen;
use Vigilante\Support\SeededRandom;

/**
 * Vagues d'ennemis d'un niveau : de plus en plus nombreuses, avec de nouveaux
 * ennemis armés débloqués au fil de l'album, puis le boss au bout de la rue.
 */
final class WaveGenerator
{
    /** Ennemis débloqués à chaque niveau. */
    private const UNLOCKS = [
        1 => ['skinhead', 'batter'],
        2 => ['masculinist', 'knifer'],
        3 => ['chainer'],
        4 => ['hooligan'],
        5 => ['gymbro'],
    ];

    /** Position de la caméra qui déclenche chaque vague. */
    private const TRIGGERS = [0, 360, 760];

    /**
     * @param array<string, mixed> $boss
     * @return list<array{at: int, enemies: list<string>, boss?: array<string, mixed>}>
     */
    public static function generate(int $level, array $boss): array
    {
        $pool = self::pool($level);
        $random = new SeededRandom($level * 97);
        $waves = [];

        foreach (self::TRIGGERS as $i => $at) {
            $enemies = [];
            for ($e = 0, $count = 2 + intdiv($level, 3) + $i; $e < $count; $e++) {
                $enemies[] = $random->pick($pool);
            }
            $waves[] = ['at' => $at, 'enemies' => $enemies];
        }
        $waves[] = ['at' => LevelFactory::LENGTH - Screen::WIDTH, 'enemies' => [], 'boss' => $boss];

        return $waves;
    }

    /** @return list<string> */
    private static function pool(int $level): array
    {
        $pool = [];
        foreach (self::UNLOCKS as $unlockedAt => $types) {
            if ($unlockedAt <= $level) {
                array_push($pool, ...$types);
            }
        }

        return $pool;
    }
}
