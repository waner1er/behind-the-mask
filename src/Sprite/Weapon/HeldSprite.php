<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Weapon;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Pose;

/** Objet tenu en main (haltère, mallette, bouteille...). Positions [x, y] relatives à la main. */
final readonly class HeldSprite implements Weapon
{
    /**
     * @param list<string> $rows
     * @param array{int, int} $walk
     * @param array{int, int} $windup
     * @param array{int, int}|null $strike null : l'objet est lancé
     */
    public function __construct(
        private array $rows,
        private array $walk,
        private array $windup,
        private ?array $strike,
    ) {
    }

    public function layer(Pose $pose, array $hand): ?Layer
    {
        $offset = match ($pose) {
            Pose::Walk => $this->walk,
            Pose::Windup => $this->windup,
            Pose::Strike => $this->strike,
        };

        return $offset === null ? null : Layer::at($hand[0] + $offset[0], $hand[1] + $offset[1], $this->rows);
    }
}
