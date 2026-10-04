<?php

declare(strict_types=1);

namespace Vigilante\Scene\Layer;

use Vigilante\Scene\Celestial;
use Vigilante\Scene\Painter\CelestialPainter;
use Vigilante\Scene\Palette;
use Vigilante\Scene\Screen;
use Vigilante\Scene\Svg;
use Vigilante\Scene\Theme;
use Vigilante\Support\SeededRandom;

/** Ciel fixe : trame de points (demi-teinte de BD), étoiles, lueur d'incendie, soleil ou lune. */
final readonly class BackgroundLayer implements SceneLayer
{
    private const STARS = 50;

    public function __construct(private Theme $theme)
    {
    }

    public function render(): string
    {
        $svg = '<defs>' . $this->halftonePatterns() . '</defs>'
            . Svg::rect(0, 0, Screen::WIDTH, Screen::GROUND, $this->theme->sky->background())
            . Svg::rect(0, 0, Screen::WIDTH, 24, 'url(#halftone-a)')
            . Svg::rect(0, 24, Screen::WIDTH, 24, 'url(#halftone-b)')
            . Svg::rect(0, 48, Screen::WIDTH, 24, 'url(#halftone-c)');

        if ($this->theme->isNight()) {
            $svg .= $this->stars();
        }

        return $svg . $this->fireGlow() . $this->celestial();
    }

    /** Trois trames de plus en plus clairsemées vers le bas. */
    private function halftonePatterns(): string
    {
        $dot = fn(int $x, int $y) => Svg::rect($x, $y, 1, 1, $this->theme->sky->dots());

        return '<pattern id="halftone-a" width="4" height="4" patternUnits="userSpaceOnUse">' . $dot(0, 0) . $dot(2, 2) . '</pattern>'
            . '<pattern id="halftone-b" width="4" height="4" patternUnits="userSpaceOnUse">' . $dot(0, 0) . '</pattern>'
            . '<pattern id="halftone-c" width="8" height="4" patternUnits="userSpaceOnUse">' . $dot(0, 0) . '</pattern>';
    }

    private function stars(): string
    {
        $random = new SeededRandom($this->theme->seed);
        $svg = '';
        for ($i = 0; $i < self::STARS; $i++) {
            $x = $random->int(0, Screen::WIDTH - 1);
            $y = $random->int(2, 100);
            $attrs = $random->oneIn(3) ? Svg::blink($random->tenths(20)) : '';
            $svg .= Svg::rect($x, $y, 1, 1, Palette::PAPER, $attrs);
        }

        return $svg;
    }

    /** La ville brûle : lueur orangée à l'horizon. */
    private function fireGlow(): string
    {
        if ($this->theme->chaos <= 0) {
            return '';
        }
        $svg = '';
        foreach ([[60, 0.25], [90, 0.45], [115, 0.7]] as [$y, $strength]) {
            $opacity = $this->theme->chaos * $strength * 0.35;
            $svg .= Svg::rect(0, $y, Screen::WIDTH, Screen::GROUND - $y, '#ff5a1e', Svg::opacity($opacity));
        }

        return $svg;
    }

    private function celestial(): string
    {
        $painter = new CelestialPainter($this->theme);

        return match ($this->theme->celestial) {
            Celestial::Sun => $painter->sun(),
            Celestial::Moon => $painter->moon(),
            Celestial::None => '',
        };
    }
}
