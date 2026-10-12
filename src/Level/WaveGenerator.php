<?php

declare(strict_types=1);

namespace Vigilante\Level;

use Vigilante\Scene\Screen;
use Vigilante\Support\SeededRandom;

/**
 * Vagues d'ennemis d'un niveau : de plus en plus nombreuses, avec de nouveaux
 * ennemis armés débloqués au fil de l'album, puis le boss au bout de la rue.
 * Un niveau peut aussi décrire ses vagues une à une (stages), boss de mi-niveau compris.
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
     * @param array<string, mixed> $config le niveau de config/levels.php
     * @return list<array<string, mixed>>
     */
    public static function generate(int $level, array $config): array
    {
        $pool = self::pool($level);
        $random = new SeededRandom($level * 97);
        $length = LevelFactory::length($config);

        $waves = isset($config['stages'])
            ? self::staged($config, $pool, $random, $length)
            : self::drawn($level, $pool, $random);
        $waves[] = ['at' => $length - Screen::WIDTH, 'enemies' => [], 'boss' => $config['boss']];

        return $waves;
    }

    /**
     * Trois vagues tirées au sort, de plus en plus fournies.
     *
     * @param list<string> $pool
     * @return list<array<string, mixed>>
     */
    private static function drawn(int $level, array $pool, SeededRandom $random): array
    {
        $waves = [];
        foreach (self::TRIGGERS as $i => $at) {
            $waves[] = ['at' => $at, 'enemies' => self::draw($pool, $random, 2 + intdiv($level, 3) + $i)];
        }

        return $waves;
    }

    /**
     * Les vagues décrites par le niveau, réparties régulièrement avant l'écran du boss.
     *
     * @param array<string, mixed> $config
     * @param list<string> $pool
     * @return list<array<string, mixed>>
     */
    private static function staged(array $config, array $pool, SeededRandom $random, int $length): array
    {
        /** @var list<string|array<string, mixed>> $stages */
        $stages = $config['stages'];
        $step = ($length - 2 * Screen::WIDTH) / max(1, count($stages) - 1);
        $waves = [];

        foreach ($stages as $i => $stage) {
            $at = (int) round($i * $step);
            if ($stage === 'midboss') {
                $waves[] = ['at' => $at, 'enemies' => [], 'boss' => $config['midboss']];
                continue;
            }
            $stage = (array) $stage;
            $wave = [
                'at' => $at,
                'enemies' => [...self::draw($pool, $random, (int) $stage['enemies']), ...($stage['extra'] ?? [])],
            ];
            if (isset($stage['reinforcements'])) {
                $wave['reinforcements'] = self::draw($pool, $random, (int) $stage['reinforcements']);
            }
            if ($stage['pincer'] ?? false) {
                $wave['pincer'] = true;
            }
            $waves[] = $wave;
        }

        return $waves;
    }

    /**
     * @param list<string> $pool
     * @return list<string>
     */
    private static function draw(array $pool, SeededRandom $random, int $count): array
    {
        $enemies = [];
        for ($e = 0; $e < $count; $e++) {
            $enemies[] = $random->pick($pool);
        }

        return $enemies;
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
