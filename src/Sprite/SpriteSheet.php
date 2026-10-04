<?php

declare(strict_types=1);

namespace Vigilante\Sprite;

use JsonSerializable;

/** Toutes les animations d'un personnage : une palette et des grilles de pixels par animation. */
final readonly class SpriteSheet implements JsonSerializable
{
    /**
     * @param array<array-key, string> $palette caractère => couleur CSS (« 1 » devient une clé entière en PHP)
     * @param array<string, list<list<string>>> $frames animation => images
     */
    public function __construct(public array $palette, public array $frames)
    {
    }

    /** @return array{palette: array<array-key, string>, frames: array<string, list<list<string>>>} */
    public function jsonSerialize(): array
    {
        return ['palette' => $this->palette, 'frames' => $this->frames];
    }
}
