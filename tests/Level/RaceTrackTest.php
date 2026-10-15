<?php

declare(strict_types=1);

namespace Vigilante\Tests\Level;

use PHPUnit\Framework\TestCase;
use Vigilante\Level\RaceTrack;

final class RaceTrackTest extends TestCase
{
    private const RACE = [
        'seed' => 7,
        'sections' => [[100, 0, 0], [300, 3, -20], [200, -2, 10]],
        'traffic' => [['at' => 60, 'riders' => ['roller']]],
        'obstacles' => ['every' => 40, 'pool' => ['cone', 'barrier']],
    ];

    public function testTheTrackIsAsLongAsItsSectionsAndEndsAtTheSkatepark(): void
    {
        $track = RaceTrack::build(self::RACE, ['SKATE']);

        self::assertSame(600, $track['length']);
        self::assertLessThan($track['length'], $track['finish']);
        self::assertSame(self::RACE['traffic'], $track['traffic']);
    }

    public function testNoTrapAtTheStartNorInTheFinalStraight(): void
    {
        $track = RaceTrack::build(self::RACE, ['SKATE']);
        $at = array_column($track['obstacles'], 'at');

        self::assertNotEmpty($at);
        self::assertGreaterThanOrEqual(80, min($at));
        self::assertLessThan($track['length'] - 60, max($at));
        foreach ($track['obstacles'] as $obstacle) {
            self::assertContains($obstacle['sprite'], self::RACE['obstacles']['pool']);
            self::assertLessThanOrEqual(0.7, abs($obstacle['x']), 'sur la chaussée');
        }
    }

    public function testOneWallOfDeathOnTheWayAndTheSameTrackForTheSameSeed(): void
    {
        $track = RaceTrack::build(self::RACE, ['SKATE']);

        self::assertCount(1, array_filter($track['pickups'], fn(array $item) => $item['kind'] === 'wod'));
        self::assertSame($track, RaceTrack::build(self::RACE, ['SKATE']));
    }
}
