<?php

declare(strict_types=1);

namespace Vigilante\Sprite;

/** Moment d'une attaque, qui détermine la position de l'arme tenue en main. */
enum Pose: string
{
    case Walk = 'walk';
    case Windup = 'windup';
    case Strike = 'strike';
}
