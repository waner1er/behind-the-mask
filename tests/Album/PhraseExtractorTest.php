<?php

declare(strict_types=1);

namespace Vigilante\Tests\Album;

use PHPUnit\Framework\TestCase;
use Vigilante\Album\PhraseExtractor;

final class PhraseExtractorTest extends TestCase
{
    public function testSplitsUppercasesDeduplicatesAndFiltersByLength(): void
    {
        $phrases = (new PhraseExtractor(['no peace']))->extract([
            'Wake up / too late.',
            'wake   up',
            'No peace for you',
            'ok',
            'This one is far too long',
        ], 14);

        self::assertSame(['WAKE UP', 'TOO LATE'], $phrases);
    }
}
