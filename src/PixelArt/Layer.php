<?php

declare(strict_types=1);

namespace Vigilante\PixelArt;

/**
 * Calque de pixel art : une grille de texte (1 caractère = 1 pixel, '.' = transparent),
 * sa position dans le sprite et une éventuelle recoloration de ses caractères.
 */
final readonly class Layer
{
    /**
     * @param list<string> $rows
     * @param array<string, string> $map caractère d'origine => caractère affiché
     */
    public function __construct(
        public array $rows,
        public int $x = 0,
        public int $y = 0,
        public array $map = [],
    ) {
    }

    /** @param list<string> $rows */
    public static function at(int $x, int $y, array $rows): self
    {
        return new self($rows, $x, $y);
    }

    public function shift(int $dx = 0, int $dy = 0): self
    {
        return new self($this->rows, $this->x + $dx, $this->y + $dy, $this->map);
    }

    /** @param array<string, string> $map */
    public function recolor(array $map): self
    {
        return new self($this->rows, $this->x, $this->y, $map);
    }
}
