<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

/**
 * Zone de coup, exprimée comme dans le JS : de $from à $to pixels devant les pieds,
 * de $above pixels au-dessus du sol sur $height pixels. Le modèle l'adapte à son échelle.
 */
final readonly class Hit
{
    public function __construct(
        public int $from,
        public int $to,
        public int $damage,
        public bool $knockdown = false,
        public int $above = 30,
        public int $height = 18,
        public int $pause = 0,
    ) {
    }
}
