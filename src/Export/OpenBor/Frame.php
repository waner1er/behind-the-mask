<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

/**
 * Une image d'une animation OpenBOR et ce qui s'y passe : durée (centièmes de seconde),
 * zone de coup, déplacement, et commandes jouées à cette image (son, script...).
 */
final readonly class Frame
{
    /**
     * @param string $sprite clé de l'image dans la planche (« attack.1 »)
     * @param list<string> $commands lignes placées juste avant l'image (« sound ... », « @cmd ... »)
     */
    public function __construct(
        public string $sprite,
        public int $delay,
        public ?Hit $hit = null,
        public int $move = 0,
        public array $commands = [],
    ) {
    }

    /**
     * Durée en images du jeu web (60 par seconde) convertie en centièmes, au moins 1.
     *
     * @param list<string> $commands
     */
    public static function at(string $sprite, int $frames, ?Hit $hit = null, int $move = 0, array $commands = []): self
    {
        return new self($sprite, max(1, (int) round($frames * 100 / 60)), $hit, $move, $commands);
    }
}
