<?php

declare(strict_types=1);

namespace Vigilante\Tests\PixelArt;

use PHPUnit\Framework\TestCase;
use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Layer;

final class CompositorTest extends TestCase
{
    public function testEachLayerGetsAnOutlineInsideThePadding(): void
    {
        $grid = Compositor::compose(1, 1, [new Layer(['A'])]);

        self::assertSame(['.K.', 'KAK', '.K.'], $grid);
    }

    public function testUpperLayersPaintOverLowerOnesAndCanBeRecolored(): void
    {
        $grid = Compositor::compose(3, 1, [
            new Layer(['AAA']),
            Layer::at(1, 0, ['B'])->recolor(['B' => 'C']),
        ], outline: null, pad: 0);

        self::assertSame(['ACA'], $grid);
    }

    public function testPixelsOutsideTheGridAreClipped(): void
    {
        $grid = Compositor::compose(2, 1, [Layer::at(1, 0, ['AB'])], outline: null, pad: 0);

        self::assertSame(['.A'], $grid);
    }
}
