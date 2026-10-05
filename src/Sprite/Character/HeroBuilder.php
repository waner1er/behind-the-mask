<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\Sprite\SpriteSheet;
use Vigilante\Sprite\Weapon\Weapon;

/** Toutes les animations d'un héros (Pete par défaut), avec son arme fétiche ou une arme ramassée. */
final class HeroBuilder
{
    public static function build(?Weapon $weapon = null, HeroLook $look = new HeroParts()): SpriteSheet
    {
        return new SpriteSheet($look->palette(), self::frames($weapon, $look));
    }

    /** @return array<string, list<list<string>>> */
    public static function frames(?Weapon $weapon = null, HeroLook $look = new HeroParts()): array
    {
        $frame = new HeroFrame($look, $weapon);
        $walk = [];
        foreach (Skeleton::WALK_CYCLE as $step) {
            $walk[] = $frame->draw($step['far'], $step['near'], Stance::Guard, 0, $step['bob']);
        }

        return [
            'idle' => [
                $frame->draw('back', 'forward', Stance::Guard, 0, 1, twinkle: true),
                $frame->draw('back', 'forward', Stance::Guard),
            ],
            'walk' => $walk,
            'attack' => [$frame->draw('back', 'forward', Stance::Windup, -1, 1), $frame->draw('back', 'forward', Stance::Strike, 2, 1)],
            'skate' => [
                $frame->draw('back', 'forward', Stance::Strike, 0, 1, true),
                $frame->draw('back', 'forward', Stance::Strike, 0, 1, true),
            ],
            // saut jambes repliées, puis coup de pied tendu
            'jump' => [$frame->draw('tuck', 'tuckFront', Stance::Guard, 0, -2)],
            'kick' => [$frame->draw('tuck', 'kick', Stance::Windup, 0, -1)],
        ];
    }
}
