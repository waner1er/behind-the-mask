<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Weapon\Weapon;

/**
 * Apparence d'un ennemi ou d'un boss posée sur le squelette commun.
 *
 * $armColors recolore les bras partagés : 'H'/'h' = manche et ombre, 'S'/'s' = mains (gants...).
 */
final readonly class EnemyLook
{
    /**
     * @param array<string, string> $palette
     * @param array<string, string> $armColors
     * @param list<Layer> $back calques derrière le corps (cape, bazooka...)
     * @param list<Layer> $over calques par-dessus le torse et la tête (chapeau, ceinture...)
     * @param list<Layer> $front calques tout devant (bouclier...)
     */
    public function __construct(
        public array $palette,
        public Layer $head,
        public Layer $torso,
        public array $armColors,
        public ?Weapon $weapon = null,
        public array $back = [],
        public array $over = [],
        public array $front = [],
    ) {
    }
}
