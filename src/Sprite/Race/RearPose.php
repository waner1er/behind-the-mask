<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

/** Pose des bras d'un pilote vu de dos : il roule, arme son coup, frappe vers la droite, ou encaisse. */
enum RearPose
{
    case Ride;
    case Windup;
    case Strike;
    case Hurt;
}
