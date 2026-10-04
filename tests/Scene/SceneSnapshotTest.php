<?php

declare(strict_types=1);

namespace Vigilante\Tests\Scene;

use PHPUnit\Framework\Attributes\DataProvider;
use Vigilante\Application;
use Vigilante\Tests\SnapshotTestCase;

/** Les décors sont procéduraux mais reproductibles : la même graine donne toujours la même rue. */
final class SceneSnapshotTest extends SnapshotTestCase
{
    /** @return iterable<string, array{int}> */
    public static function levels(): iterable
    {
        foreach (range(0, 8) as $index) {
            yield "niveau $index" => [$index];
        }
    }

    #[DataProvider('levels')]
    public function testLevelSceneIsUnchanged(int $index): void
    {
        self::assertMatchesSnapshot("scene-level-$index", self::app()->scenes()->level($index));
    }

    public function testPeaceSceneIsUnchanged(): void
    {
        self::assertMatchesSnapshot('scene-peace', self::app()->scenes()->peace());
    }

    private static function app(): Application
    {
        return new Application(dirname(__DIR__, 2));
    }
}
