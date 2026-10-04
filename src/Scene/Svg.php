<?php

declare(strict_types=1);

namespace Vigilante\Scene;

/** Petits constructeurs de balises SVG. */
final class Svg
{
    public static function rect(int $x, int $y, int $w, int $h, string $fill, string $attrs = ''): string
    {
        return sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" fill="%s"%s/>',
            $x,
            $y,
            $w,
            $h,
            $fill,
            $attrs !== '' ? ' ' . $attrs : '',
        );
    }

    public static function text(
        int $x,
        int $y,
        string $label,
        string $color,
        int $size,
        string $class,
        string $attrs = '',
    ): string {
        return sprintf(
            '<text x="%d" y="%d" class="%s" fill="%s" style="color:%s" font-size="%d" text-anchor="middle"%s>%s</text>',
            $x,
            $y,
            $class,
            $color,
            $color,
            $size,
            $attrs !== '' ? ' ' . $attrs : '',
            htmlspecialchars($label),
        );
    }

    public static function opacity(float $opacity): string
    {
        return sprintf('opacity="%.2f"', $opacity);
    }

    /** Attributs d'un pixel qui clignote, déphasé de $delay secondes. */
    public static function blink(float $delay): string
    {
        return sprintf('class="blink" style="animation-delay:-%.1fs"', $delay);
    }

    /** Groupe animé en CSS (.grow, .bloom...) qui démarre après $delay secondes. */
    public static function animated(string $class, float $delay, string $content): string
    {
        return sprintf('<g class="%s" style="animation-delay:%.1fs">%s</g>', $class, $delay, $content);
    }
}
