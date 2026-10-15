<?php

declare(strict_types=1);

namespace Vigilante\Scene\Layer;

use Vigilante\Scene\Screen;
use Vigilante\Scene\Svg;

/** Ciel fixe de la descente : coucher de soleil sur Los Angeles, en bandes tramées, et un grand soleil rayé. */
final readonly class SunsetLayer implements SceneLayer
{
    /** Ligne d'horizon de la route (le milieu de l'écran, voir js/race/Road.js). */
    public const HORIZON = 90;

    private const BANDS = ['#24124a', '#34175a', '#4a1c66', '#66206e', '#8a2672', '#b03272', '#d4466c', '#f06a62', '#ff9258'];

    private const SUN = ['x' => 160, 'y' => 66, 'radius' => 30];

    public function render(): string
    {
        $svg = Svg::rect(0, 0, Screen::WIDTH, Screen::HEIGHT, '#2a1838');
        $band = (int) ceil(self::HORIZON / count(self::BANDS));
        foreach (self::BANDS as $i => $color) {
            $svg .= Svg::rect(0, $i * $band, Screen::WIDTH, $band, $color);
            if ($i > 0) {
                $svg .= $this->dither($i * $band, self::BANDS[$i - 1]);
            }
        }

        return $svg . $this->sun();
    }

    /** Une rangée de pixels un sur deux de la bande du dessus : la transition « trame ». */
    private function dither(int $y, string $color): string
    {
        $svg = '';
        for ($x = 0; $x < Screen::WIDTH; $x += 2) {
            $svg .= Svg::rect($x, $y, 1, 1, $color);
        }

        return $svg;
    }

    /** Soleil synthwave : du jaune au rose, coupé de bandes de plus en plus épaisses vers le bas. */
    private function sun(): string
    {
        ['x' => $cx, 'y' => $cy, 'radius' => $r] = self::SUN;
        $svg = '';
        for ($dy = -$r; $dy <= $r && $cy + $dy < self::HORIZON; $dy++) {
            $below = $dy - 2;
            if ($below > 0 && $below % 7 < 1 + intdiv($below, 9)) {
                continue;
            }
            $half = (int) floor(sqrt($r * $r - $dy * $dy));
            $svg .= Svg::rect($cx - $half, $cy + $dy, $half * 2 + 1, 1, $this->sunColor(($dy + $r) / (2 * $r)));
        }

        return $svg;
    }

    private function sunColor(float $t): string
    {
        return match (true) {
            $t < 0.3 => '#ffe86a',
            $t < 0.5 => '#ffc24a',
            $t < 0.7 => '#ff8a4a',
            default => '#ff5a7a',
        };
    }
}
