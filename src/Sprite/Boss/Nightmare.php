<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\Sprite\Character\HeroBuilder;
use Vigilante\Sprite\SpriteSheet;

/** 03 Nightmare : ton double maléfique, le sprite du héros dans une palette d'ombre violette. */
final class Nightmare
{
    private const PALETTE = [
        'K' => '#05030a',
        'M' => '#2a1d3a', 'm' => '#160e22', 'P' => '#4a3466',
        'Z' => '#3a2c4a', 'S' => '#8a7a9a', 's' => '#5e4e70',
        'G' => '#05030a', 'w' => '#c53cff', 'Y' => '#9b4dff',
        'C' => '#3a2a52', 'c' => '#261a38', 'e' => '#9b4dff', 'D' => '#c53cff',
        'A' => '#3a2a52', 'a' => '#261a38', 'v' => '#160e22',
        'O' => '#120a1a', 'o' => '#2a1d3a', 'q' => '#05030a', 'X' => '#05030a',
        'N' => '#c58aff', 'n' => '#6a4a8a', 'h' => '#05030a',
        'k' => '#05030a', 'r' => '#c53cff', 'T' => '#3a2a52', 'W' => '#8a7a9a',
    ];

    public static function build(): SpriteSheet
    {
        return new SpriteSheet(self::PALETTE, HeroBuilder::frames());
    }
}
