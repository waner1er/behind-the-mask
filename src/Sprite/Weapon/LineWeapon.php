<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Weapon;

use Vigilante\PixelArt\Layer;
use Vigilante\PixelArt\Line;
use Vigilante\Sprite\Pose;

/** Arme longue tracée d'un trait (batte, chaîne, lame...). Segments [x0, y0, x1, y1] relatifs à la main. */
final readonly class LineWeapon implements Weapon
{
    /**
     * @param array{int, int, int, int} $walk
     * @param array{int, int, int, int} $windup
     * @param array{int, int, int, int} $strike
     */
    public function __construct(
        private string $main,
        private string $shade,
        private array $walk,
        private array $windup,
        private array $strike,
    ) {
    }

    public function layer(Pose $pose, array $hand): Layer
    {
        [$x0, $y0, $x1, $y1] = match ($pose) {
            Pose::Walk => $this->walk,
            Pose::Windup => $this->windup,
            Pose::Strike => $this->strike,
        };
        [$hx, $hy] = $hand;

        return Line::between($hx + $x0, $hy + $y0, $hx + $x1, $hy + $y1, $this->main, $this->shade);
    }
}
