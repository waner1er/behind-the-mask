<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\Sprite\Pose;

/** Garde des bras du héros : katana levé, armé en arrière, ou coup porté. */
enum Stance: string
{
    case Guard = 'guard';
    case Windup = 'windup';
    case Strike = 'strike';

    /** @return array{int, int} position de la main du bras de devant */
    public function hand(): array
    {
        return match ($this) {
            self::Guard => [18, 22],
            self::Windup => [12, 21],
            self::Strike => [22, 20],
        };
    }

    /** Pose d'une arme ramassée tenue dans cette garde. */
    public function weaponPose(): Pose
    {
        return match ($this) {
            self::Guard => Pose::Walk,
            self::Windup => Pose::Windup,
            self::Strike => Pose::Strike,
        };
    }
}
