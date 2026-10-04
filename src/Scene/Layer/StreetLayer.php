<?php

declare(strict_types=1);

namespace Vigilante\Scene\Layer;

use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Layer;
use Vigilante\PixelArt\SvgRenderer;
use Vigilante\Scene\Painter\DecorSprites;
use Vigilante\Scene\Painter\FirePainter;
use Vigilante\Scene\Painter\LampPainter;
use Vigilante\Scene\Palette;
use Vigilante\Scene\Screen;
use Vigilante\Scene\Svg;
use Vigilante\Scene\Theme;
use Vigilante\Sprite\PropCatalog;
use Vigilante\Support\SeededRandom;

/** Premier plan : trottoir, route, lampadaires, accessoires, ruines ou jardins. */
final readonly class StreetLayer implements SceneLayer
{
    private const GROUND = Screen::GROUND;

    private SeededRandom $random;

    public function __construct(private Theme $theme, private PropCatalog $props)
    {
        $this->random = new SeededRandom($theme->seed + 3);
    }

    public function render(): string
    {
        $svg = $this->sidewalk() . $this->road()
            . LampPainter::draw(36, false) . LampPainter::draw(196, true);

        if ($this->theme->peace) {
            $svg .= $this->garden();
        }

        return $svg . $this->wreckage()
            . $this->prop('hydrant', 92) . $this->prop('trash', 238) . $this->prop('box', 252) . $this->prop('box', 286);
    }

    private function sidewalk(): string
    {
        $ground = self::GROUND;
        $svg = Svg::rect(0, $ground, Screen::WIDTH, 1, Palette::INK)
            . Svg::rect(0, $ground + 1, Screen::WIDTH, 16, Palette::CONCRETE)
            . Svg::rect(0, $ground + 1, Screen::WIDTH, 1, '#7a766c')
            . Svg::rect(0, $ground + 8, Screen::WIDTH, 1, '#4a4740');
        for ($x = 6; $x < Screen::WIDTH; $x += 24) {
            $svg .= Svg::rect($x, $ground + 1, 1, 16, '#3e3b35');
        }
        for ($i = 0; $i < 40; $i++) {
            $x = $this->random->int(0, Screen::WIDTH - 2);
            $y = $this->random->int($ground + 2, $ground + 15);
            $svg .= Svg::rect($x, $y, $this->random->int(1, 2), 1, '#4a4740');
        }

        return $svg
            . Svg::rect(0, $ground + 17, Screen::WIDTH, 1, Palette::PAPER)
            . Svg::rect(0, $ground + 18, Screen::WIDTH, 2, '#8a8579')
            . Svg::rect(0, $ground + 20, Screen::WIDTH, 1, Palette::INK);
    }

    /** Bitume granuleux, marquage au sol, tag et bouche d'égout. */
    private function road(): string
    {
        $top = self::GROUND + 21;
        $svg = Svg::rect(0, $top, Screen::WIDTH, Screen::HEIGHT - $top, '#25231f');
        for ($i = 0; $i < 260; $i++) {
            $x = $this->random->int(0, Screen::WIDTH - 1);
            $y = $this->random->int($top + 1, Screen::HEIGHT - 1);
            $svg .= Svg::rect($x, $y, 1, 1, $this->random->int(0, 1) ? '#302d28' : '#1a1916');
        }
        for ($x = 4; $x < Screen::WIDTH; $x += 40) {
            $svg .= Svg::rect($x, 171, 16, 2, Palette::PAPER) . Svg::rect($x, 173, 16, 1, '#77736a');
        }

        return $svg
            . Svg::text(210, 168, 'VIGILANTE', $this->theme->accent, 5, 'graffiti', 'opacity=".7"')
            . Svg::rect(80, 166, 14, 3, Palette::INK) . Svg::rect(82, 167, 10, 1, '#4a4740');
    }

    /** La ville libérée : des arbres qui poussent et des fleurs qui sortent du bitume. */
    private function garden(): string
    {
        $tree = Compositor::compose(14, 14, [new Layer(DecorSprites::TREE)]);
        $colors = DecorSprites::TREE_COLORS + ['K' => Palette::INK];
        $svg = '';
        foreach ([[70, 0.4], [150, 1.2], [268, 2]] as [$x, $delay]) {
            $svg .= Svg::animated('grow', $delay, SvgRenderer::render($tree, $colors, $x, self::GROUND - 10));
        }

        for ($i = 0; $i < 26; $i++) {
            $x = $this->random->int(2, Screen::WIDTH - 4);
            $y = $this->random->int(self::GROUND + 2, self::GROUND + 14);
            $petal = $this->random->pick(DecorSprites::FLOWER_PETALS);
            $flower = Svg::rect($x, $y - 2, 1, 3, Palette::LEAF)
                . Svg::rect($x - 1, $y - 3, 3, 1, $petal) . Svg::rect($x, $y - 4, 1, 3, $petal)
                . Svg::rect($x, $y - 3, 1, 1, '#ffd23f');
            $svg .= Svg::animated('bloom', 1 + $this->random->tenths(40), $flower);
        }

        return $svg;
    }

    /** Gravats, baril en feu, épave de voiture calcinée. */
    private function wreckage(): string
    {
        $chaos = $this->theme->chaos;
        $svg = '';
        for ($i = 0, $count = (int) round($chaos * 4); $i < $count; $i++) {
            $x = $this->random->int(0, Screen::WIDTH - 12);
            $svg .= SvgRenderer::render(DecorSprites::RUBBLE, DecorSprites::WRECK_COLORS, $x, self::GROUND + $this->random->int(3, 12));
        }
        if ($chaos >= 0.15) {
            $svg .= SvgRenderer::render(DecorSprites::BARREL, DecorSprites::WRECK_COLORS, 150, self::GROUND - 3)
                . FirePainter::draw(150, self::GROUND - 10);
        }
        if ($chaos >= 0.3) {
            $svg .= SvgRenderer::render(DecorSprites::CAR, DecorSprites::WRECK_COLORS, 112, self::GROUND - 5)
                . FirePainter::draw(118, self::GROUND - 13)
                . FirePainter::draw(128, self::GROUND - 15);
        }

        return $svg;
    }

    /** Accessoire posé sur le trottoir (bouche incendie, poubelle, carton). */
    private function prop(string $name, int $x): string
    {
        $grid = $this->props->grid($name);

        return SvgRenderer::render($grid, $this->props->palette(), $x, self::GROUND + 6 - count($grid));
    }
}
