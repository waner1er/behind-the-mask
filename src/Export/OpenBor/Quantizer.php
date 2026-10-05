<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use GdImage;
use RuntimeException;

/** Ramène une image en vraies couleurs (décor rendu par Chrome) à 255 couleurs + la transparence. */
final class Quantizer
{
    /** Pixel quasi transparent : il devient l'index 0. */
    private const ALPHA_CUT = 64;

    public static function fromPngFile(string $file): IndexedImage
    {
        $image = @imagecreatefrompng($file);
        if (!$image instanceof GdImage) {
            throw new RuntimeException("PNG illisible : $file");
        }

        return self::quantize($image);
    }

    public static function quantize(GdImage $image): IndexedImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // le décor en pixel art a peu de couleurs : on les compte avant de réduire
        $colors = [];
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $argb = imagecolorat($image, $x, $y);
                if (($argb >> 24) <= self::ALPHA_CUT) {
                    $colors[$argb & 0xffffff] = true;
                }
            }
        }

        return count($colors) <= 255 ? self::exact($image, $colors) : self::reduced($image);
    }

    /** @param array<int, true> $colors */
    private static function exact(GdImage $image, array $colors): IndexedImage
    {
        $rgb = array_keys($colors);
        sort($rgb);
        $index = array_flip($rgb);
        $pixels = '';
        for ($y = 0, $height = imagesy($image); $y < $height; $y++) {
            for ($x = 0, $width = imagesx($image); $x < $width; $x++) {
                $argb = imagecolorat($image, $x, $y);
                $pixels .= chr(($argb >> 24) > self::ALPHA_CUT ? 0 : $index[$argb & 0xffffff] + 1);
            }
        }
        $palette = [[255, 0, 255]];
        foreach ($rgb as $color) {
            $palette[] = [($color >> 16) & 0xff, ($color >> 8) & 0xff, $color & 0xff];
        }

        return new IndexedImage(imagesx($image), imagesy($image), $pixels, $palette);
    }

    /** Trop de couleurs (dégradés, fumée) : GD choisit les 255 plus représentatives. */
    private static function reduced(GdImage $image): IndexedImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $copy = imagecreatetruecolor($width, $height);
        imagecopy($copy, $image, 0, 0, 0, 0, $width, $height);
        imagetruecolortopalette($copy, false, 255);

        $palette = [[255, 0, 255]];
        for ($i = 0, $total = imagecolorstotal($copy); $i < $total; $i++) {
            $color = imagecolorsforindex($copy, $i);
            $palette[] = [$color['red'], $color['green'], $color['blue']];
        }
        $pixels = '';
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $transparent = (imagecolorat($image, $x, $y) >> 24) > self::ALPHA_CUT;
                $pixels .= chr($transparent ? 0 : imagecolorat($copy, $x, $y) + 1);
            }
        }

        return new IndexedImage($width, $height, $pixels, $palette);
    }
}
