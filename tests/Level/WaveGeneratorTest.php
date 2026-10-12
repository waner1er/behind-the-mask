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
        $waves = WaveGenerator::generate(1, ['boss' => $boss]);

        self::assertCount(4, $waves);
        self::assertSame([0, 360, 760], array_column(array_slice($waves, 0, 3), 'at'));
        self::assertSame(LevelFactory::LENGTH - Screen::WIDTH, $waves[3]['at']);
        self::assertSame($boss, $waves[3]['boss'] ?? null);
    }

    public function testFirstLevelOnlyUsesItsUnlockedEnemiesAndIsDeterministic(): void
    {
        $waves = WaveGenerator::generate(1, ['boss' => []]);
        $enemies = array_merge(...array_column($waves, 'enemies'));

        self::assertEmpty(array_diff($enemies, ['skinhead', 'batter']));
        self::assertSame($waves, WaveGenerator::generate(1, ['boss' => []]));
    }

    public function testStagesSpreadAlongALongerStreetWithAMidLevelBoss(): void
    {
        $midboss = ['sprite' => 'tv'];
        $waves = WaveGenerator::generate(1, [
            'length' => 9,
            'stages' => [
                ['enemies' => 2],
                'midboss',
                ['enemies' => 3, 'extra' => ['knifer'], 'pincer' => true, 'reinforcements' => 2],
            ],
            'midboss' => $midboss,
            'boss' => ['sprite' => 'manager'],
        ]);

        self::assertSame([0, 1120, 2240, 2560], array_column($waves, 'at'));
        self::assertCount(2, $waves[0]['enemies']);
        self::assertSame($midboss, $waves[1]['boss']);
        self::assertSame('knifer', $waves[2]['enemies'][3]);
        self::assertTrue($waves[2]['pincer']);
        self::assertCount(2, $waves[2]['reinforcements']);
        self::assertSame(['sprite' => 'manager'], $waves[3]['boss']);
    }
}
