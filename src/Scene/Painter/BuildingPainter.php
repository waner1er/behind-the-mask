<?php

declare(strict_types=1);

namespace Vigilante\Scene\Painter;

use Vigilante\Scene\Palette;
use Vigilante\Scene\Screen;
use Vigilante\Scene\Svg;
use Vigilante\Scene\Theme;
use Vigilante\Support\SeededRandom;

/** Un immeuble noir détouré de blanc, du toit au trottoir. */
final readonly class BuildingPainter
{
    private const DAY_WALLS = ['#141312', '#1b1a18', '#121110', '#1f1d1a'];
    private const NIGHT_WALLS = ['#1e1c24', '#24212b'];
    private const WINDOW_STEP = 9;
    private const FLOOR_STEP = 13;

    private WindowPainter $windows;
    private FacadePainter $facade;

    /** @param list<string> $tags */
    public function __construct(private Theme $theme, array $tags, private SeededRandom $random)
    {
        $this->windows = new WindowPainter($theme, $random);
        $this->facade = new FacadePainter($theme, $tags);
    }

    public function draw(int $x, int $w, int $h, int $index): string
    {
        $top = Screen::GROUND - $h;
        $svg = $this->wall($x, $w, $h, $top, $index) . $this->roof($x, $w, $top);

        $columns = intdiv($w - 8, self::WINDOW_STEP);
        $offset = $x + intdiv($w - ($columns * self::WINDOW_STEP - 4), 2);
        $floors = [];
        for ($wy = $top + 9; $wy + 8 < Screen::GROUND - 26; $wy += self::FLOOR_STEP) {
            $floors[] = $wy;
            $svg .= $this->floor($offset, $wy, $columns, $index);
        }

        if ($w >= 56 && count($floors) > 1 && $this->random->oneIn(2)) {
            $svg .= $this->facade->fireEscape($offset + ($columns - 2) * self::WINDOW_STEP - 2, $floors);
        }

        return $svg
            . $this->fires($x, $w, $top, $offset, $columns, $floors)
            . $this->facade->groundFloor($x, $w, $index)
            . $this->facade->signOrGraffiti($x + intdiv($w, 2), $w, $index);
    }

    /** Mur, hachures de briques et arête détourée. */
    private function wall(int $x, int $w, int $h, int $top, int $index): string
    {
        $color = $this->theme->isNight()
            ? self::NIGHT_WALLS[$index % 2]
            : self::DAY_WALLS[$index % 4];
        $svg = Svg::rect($x, $top, $w, $h, $color);

        for ($i = 0, $bricks = intdiv($w * $h, 40); $i < $bricks; $i++) {
            $svg .= Svg::rect($this->random->int($x + 2, $x + $w - 3), $this->random->int($top + 4, Screen::GROUND - 1), 2, 1, '#2e2c28');
        }

        return $svg . Svg::rect($x, $top, 1, $h, Palette::PAPER, 'opacity=".5"');
    }

    /** Château d'eau ou clim, puis la corniche. */
    private function roof(int $x, int $w, int $top): string
    {
        $svg = $w > 52 && $this->random->oneIn(2)
            ? RooftopPainter::waterTower($x + $w - 20, $top)
            : RooftopPainter::airConditioner($x, $top);

        return $svg
            . Svg::rect($x, $top, $w, 1, Palette::PAPER)
            . Svg::rect($x, $top + 1, $w, 2, Palette::INK)
            . Svg::rect($x, $top + 3, $w, 1, Palette::PAPER, 'opacity=".6"');
    }

    /** Une rangée de fenêtres (ou de baies de serveurs dans le data center du Docteur Mask). */
    private function floor(int $offset, int $y, int $columns, int $index): string
    {
        $svg = '';
        for ($c = 0; $c < $columns; $c++) {
            $x = $offset + $c * self::WINDOW_STEP;
            $svg .= $this->theme->datacenter && $index % 2 === 1
                ? $this->windows->serverRack($x, $y)
                : $this->windows->window($x, $y);
        }

        return $svg;
    }

    /**
     * Incendies aux fenêtres et sur le toit, d'autant plus fréquents que le chaos monte.
     *
     * @param list<int> $floors
     */
    private function fires(int $x, int $w, int $top, int $offset, int $columns, array $floors): string
    {
        $svg = '';
        if ($floors && $this->random->chance($this->theme->chaos * 80)) {
            $column = $this->random->int(0, $columns - 1);
            $svg .= FirePainter::draw($offset + $column * self::WINDOW_STEP - 1, $this->random->pick($floors) - 2);
        }
        if ($this->random->chance($this->theme->chaos * 50)) {
            $svg .= FirePainter::draw($x + $this->random->int(4, $w - 12), $top - 8);
        }

        return $svg;
    }
}
