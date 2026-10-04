<?php

declare(strict_types=1);

namespace Vigilante\Tests\Level;

use PHPUnit\Framework\TestCase;
use Vigilante\Level\LevelFactory;
use Vigilante\Level\WaveGenerator;
use Vigilante\Scene\Screen;

final class WaveGeneratorTest extends TestCase
{
    public function testThreeWavesThenTheBossAtTheEndOfTheStreet(): void
    {
        $boss = ['sprite' => 'manager'];
        $waves = WaveGenerator::generate(1, $boss);

        self::assertCount(4, $waves);
        self::assertSame([0, 360, 760], array_column(array_slice($waves, 0, 3), 'at'));
        self::assertSame(LevelFactory::LENGTH - Screen::WIDTH, $waves[3]['at']);
        self::assertSame($boss, $waves[3]['boss'] ?? null);
    }

    public function testFirstLevelOnlyUsesItsUnlockedEnemiesAndIsDeterministic(): void
    {
        $waves = WaveGenerator::generate(1, []);
        $enemies = array_merge(...array_column($waves, 'enemies'));

        self::assertEmpty(array_diff($enemies, ['skinhead', 'batter']));
        self::assertSame($waves, WaveGenerator::generate(1, []));
    }
}
