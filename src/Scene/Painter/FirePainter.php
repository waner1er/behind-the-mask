<?php

declare(strict_types=1);

namespace Vigilante\Scene\Painter;

use Vigilante\PixelArt\SvgRenderer;

/** Petit feu animé en CSS (deux images qui alternent). */
final class FirePainter
{
    public static function draw(int $x, int $y): string
    {
        return '<g class="fire">'
            . '<g class="fire__a">' . SvgRenderer::render(DecorSprites::FIRE[0], DecorSprites::FIRE_COLORS, $x, $y) . '</g>'
            . '<g class="fire__b">' . SvgRenderer::render(DecorSprites::FIRE[1], DecorSprites::FIRE_COLORS, $x, $y) . '</g>'
            . '</g>';
    }
}
