<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Weapon;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Pose;

/** Une arme tenue en main : son calque selon la pose, placé par rapport à la main. */
interface Weapon
{
    /**
     * @param array{int, int} $hand position [x, y] de la main du bras de devant
     * @return Layer|null null quand l'arme a quitté la main (bouteille lancée...)
     */
    public function layer(Pose $pose, array $hand): ?Layer;
}
