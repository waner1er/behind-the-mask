<?php

declare(strict_types=1);

namespace Vigilante\Tests\PixelArt;

use PHPUnit\Framework\TestCase;
use Vigilante\PixelArt\Line;

final class LineTest extends TestCase
{
    public function testHorizontalLineIsTwoPixelsThickWithShade(): void
    {
        $layer = Line::between(0, 3, 2, 3, 'N', 'n');

        self::assertSame(3, $layer->y);
        self::assertSame(['NNN', 'nnn'], $layer->rows);
    }

    public function testVerticalLineIsShadedOnTheRight(): void
    {
        $layer = Line::between(1, 0, 1, 1, 'M', 'm');

        self::assertSame(['.Mm', '.Mm'], $layer->rows);
    }
}
