<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

/** Une animation d'un modèle OpenBOR (idle, walk, attack1...) : ses images, en boucle ou non. */
final readonly class Animation
{
    /**
     * @param list<Frame> $frames
     * @param list<string> $extra commandes en plus (« energycost 0 »...)
     */
    public function __construct(
        public string $name,
        public array $frames,
        public bool $loop = false,
        public array $extra = [],
    ) {
    }
}
