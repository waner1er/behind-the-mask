<?php

declare(strict_types=1);

namespace Vigilante\Tests\Export\OpenBor;

use GdImage;
use PHPUnit\Framework\TestCase;
use Vigilante\Export\OpenBor\IndexedImage;
use Vigilante\Export\OpenBor\PngEncoder;

final class PngEncoderTest extends TestCase
{
    public function testWritesAnEightBitPalettePngEvenWithFewColors(): void
    {
        $image = IndexedImage::fromGrid(['.K', 'Kw'], ['K', 'w'], ['K' => '#0c0a10', 'w' => '#ffffff']);
        $png = PngEncoder::encode($image);

        // IHDR : largeur, hauteur, profondeur 8, type 3 (palette) — OpenBOR refuse tout le reste
        self::assertSame([2, 2, 8, 3], array_values(unpack('Nw/Nh/Cdepth/Ctype', $png, 16) ?: []));

        $decoded = imagecreatefromstring($png);
        self::assertInstanceOf(GdImage::class, $decoded);
        self::assertSame([255, 255, 255], self::rgbAt($decoded, 1, 1));
        self::assertSame([12, 10, 16], self::rgbAt($decoded, 1, 0));
    }

    public function testIndexZeroIsTheTransparentPixel(): void
    {
        $image = IndexedImage::fromGrid(['.K'], ['K'], ['K' => '#0c0a10']);

        self::assertSame("\x00\x01", $image->pixels);
    }

    /** @return list<int> */
    private static function rgbAt(GdImage $image, int $x, int $y): array
    {
        $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));

        return [$color['red'], $color['green'], $color['blue']];
    }
}
