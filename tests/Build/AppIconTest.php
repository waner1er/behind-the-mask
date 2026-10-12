<?php

declare(strict_types=1);

namespace Vigilante\Tests\Build;

use PHPUnit\Framework\TestCase;
use Vigilante\Build\AppIcon;

final class AppIconTest extends TestCase
{
    /** L'App Store refuse une icône qui n'est pas en 1024 × 1024 ou qui a de la transparence. */
    public function testIconIsAnOpaqueSquareOf1024Pixels(): void
    {
        $png = AppIcon::png();
        $header = unpack('Nwidth/Nheight', substr($png, 16, 8));

        self::assertSame(['width' => 1024, 'height' => 1024], $header);
        self::assertStringNotContainsString('tRNS', $png);
    }

    public function testIconFillsTheWholeGrid(): void
    {
        $grid = AppIcon::grid();

        self::assertCount(AppIcon::SIZE, $grid);
        foreach ($grid as $row) {
            self::assertSame(AppIcon::SIZE, strlen($row));
            self::assertStringNotContainsString('.', $row);
        }
    }
}
