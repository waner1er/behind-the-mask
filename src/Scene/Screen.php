<?php

declare(strict_types=1);

namespace Vigilante\Scene;

/** Résolution « borne d'arcade » de l'écran, en pixels. */
final class Screen
{
    public const WIDTH = 320;
    public const HEIGHT = 180;

    /** Ligne où les immeubles touchent le trottoir. */
    public const GROUND = 140;
}
