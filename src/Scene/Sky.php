<?php

declare(strict_types=1);

namespace Vigilante\Scene;

/** Ambiance du ciel : couleur de fond, trame de points, skyline lointaine et ses fenêtres. */
enum Sky: string
{
    case Paper = 'paper';
    case Dusk = 'dusk';
    case Grey = 'grey';
    case Night = 'night';

    public function background(): string
    {
        return match ($this) {
            self::Paper => Palette::PAPER,
            self::Dusk => '#ebc79a',
            self::Grey => '#b9b9b4',
            self::Night => '#0f0e13',
        };
    }

    public function dots(): string
    {
        return match ($this) {
            self::Paper => '#9d998f',
            self::Dusk => '#b4865a',
            self::Grey => '#8a8a85',
            self::Night => '#34313c',
        };
    }

    public function skyline(): string
    {
        return match ($this) {
            self::Paper => '#a9a59a',
            self::Dusk => '#a07a58',
            self::Grey => '#8e8e89',
            self::Night => '#26242c',
        };
    }

    public function windows(): string
    {
        return match ($this) {
            self::Paper => '#7d796f',
            self::Dusk => '#7a5a3e',
            self::Grey => '#6e6e6a',
            self::Night => '#c8c2a8',
        };
    }
}
