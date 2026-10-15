<?php

declare(strict_types=1);

namespace Vigilante\Scene\Layer;

use Vigilante\PixelArt\PixelFont;
use Vigilante\Scene\Screen;
use Vigilante\Scene\Svg;

/** Les collines au loin, avec leurs grandes lettres blanches (comme à Hollywood, mais au nom du groupe). */
final readonly class HillsLayer implements SceneLayer
{
    private const COLORS = ['far' => '#5a2470', 'near' => '#3e1a56'];

    public function __construct(private string $sign = 'VIGILANTE')
    {
    }

    public function render(): string
    {
        // des sinusoïdes de période 320 / k : le plan se raccorde à lui-même en boucle
        $far = fn(int $x) => 62 + 6 * sin(2 * M_PI * $x / 320) + 4 * sin(2 * M_PI * $x * 3 / 320);
        $near = fn(int $x) => 80 + 5 * sin(2 * M_PI * $x * 2 / 320 + 1) + 3 * sin(2 * M_PI * $x * 5 / 320);

        return $this->ridge($far, self::COLORS['far']) . $this->letters() . $this->ridge($near, self::COLORS['near']);
    }

    /** @param callable(int): float $top */
    private function ridge(callable $top, string $color): string
    {
        $svg = '';
        for ($x = 0; $x < Screen::WIDTH; $x++) {
            $y = (int) round($top($x));
            $svg .= Svg::rect($x, $y, 1, SunsetLayer::HORIZON + 20 - $y, $color);
        }

        return $svg;
    }

    /** Lettres blanches posées en biais sur le flanc de la colline lointaine. */
    private function letters(): string
    {
        $svg = '';
        foreach (PixelFont::write($this->sign, '#') as $y => $row) {
            for ($x = 0, $length = strlen($row); $x < $length; $x++) {
                if ($row[$x] === '#') {
                    $svg .= Svg::rect(236 + $x, 61 + $y + intdiv($x, 12), 1, 1, '#f4ece0');
                }
            }
        }

        return $svg;
    }
}
