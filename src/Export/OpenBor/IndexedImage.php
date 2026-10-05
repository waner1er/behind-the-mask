<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use InvalidArgumentException;

/**
 * Image à palette, le seul format qu'OpenBOR sait lire en PNG : un octet par pixel,
 * index 0 = transparent, 256 couleurs au plus.
 */
final readonly class IndexedImage
{
    /**
     * @param string $pixels width × height octets, ligne par ligne
     * @param list<array{int, int, int}> $palette couleurs RVB, l'index 0 étant la transparence
     */
    public function __construct(
        public int $width,
        public int $height,
        public string $pixels,
        public array $palette,
    ) {
        if (strlen($pixels) !== $width * $height) {
            throw new InvalidArgumentException("Image {$width}×{$height} : " . strlen($pixels) . ' pixels');
        }
        if ($palette === [] || count($palette) > 256) {
            throw new InvalidArgumentException('Palette de 1 à 256 couleurs : ' . count($palette));
        }
    }

    /**
     * Une grille de pixel art (1 caractère = 1 pixel, « . » = transparent).
     *
     * @param list<string> $rows
     * @param list<string> $chars caractères de la palette, dans l'ordre des index 1, 2...
     * @param array<array-key, string> $colors caractère => couleur « #rrggbb »
     */
    public static function fromGrid(array $rows, array $chars, array $colors): self
    {
        $index = array_flip($chars);
        $width = max(array_map('strlen', $rows));
        $pixels = '';
        foreach ($rows as $row) {
            for ($x = 0; $x < $width; $x++) {
                $char = $row[$x] ?? '.';
                $pixels .= chr($char === '.' ? 0 : $index[$char] + 1);
            }
        }
        $palette = [[255, 0, 255]];
        foreach ($chars as $char) {
            $palette[] = self::rgb($colors[$char]);
        }

        return new self($width, count($rows), $pixels, $palette);
    }

    /** @return array{int, int, int} */
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [(int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2))];
    }
}
