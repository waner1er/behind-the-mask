<?php

declare(strict_types=1);

namespace Vigilante\Album;

use JsonSerializable;

/** Lien externe de l'album (Bandcamp, Spotify...). */
final readonly class Link implements JsonSerializable
{
    public function __construct(public string $name, public string $url)
    {
    }

    /** @return array{name: string, url: string} */
    public function jsonSerialize(): array
    {
        return ['name' => $this->name, 'url' => $this->url];
    }
}
