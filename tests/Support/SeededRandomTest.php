<?php

declare(strict_types=1);

namespace Vigilante\Tests\Support;

use PHPUnit\Framework\TestCase;
use Vigilante\Support\SeededRandom;

final class SeededRandomTest extends TestCase
{
    /** Les décors et les vagues historiques dépendent de cette équivalence. */
    public function testProducesTheSameSequenceAsMtRand(): void
    {
        mt_srand(909);
        $expected = array_map(fn() => mt_rand(0, 99), range(1, 50));

        $random = new SeededRandom(909);
        $actual = array_map(fn() => $random->int(0, 99), range(1, 50));

        self::assertSame($expected, $actual);
    }
}
