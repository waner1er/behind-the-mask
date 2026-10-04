<?php

declare(strict_types=1);

namespace Vigilante\Tests\PixelArt;

use PHPUnit\Framework\TestCase;
use Vigilante\PixelArt\SvgRenderer;

final class SvgRendererTest extends TestCase
{
    public function testIdenticalNeighbourPixelsAreMergedIntoOneRect(): void
    {
        $svg = SvgRenderer::render(['AA.B'], ['A' => '#111', 'B' => '#222'], 10, 20);

        self::assertSame(
            '<rect x="10" y="20" width="2" height="1" fill="#111"/><rect x="13" y="20" width="1" height="1" fill="#222"/>',
            $svg,
        );
    }

    public function testCharactersMissingFromThePaletteAreSkipped(): void
    {
        self::assertSame('', SvgRenderer::render(['ZZ'], ['A' => '#111']));
    }
}
