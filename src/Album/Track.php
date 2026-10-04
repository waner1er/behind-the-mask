<?php

declare(strict_types=1);

namespace Vigilante\Album;

/** Un morceau de l'album : ses paroles (ligne par ligne) et l'URL de son fichier audio. */
final readonly class Track
{
    /** @param list<string> $lyrics */
    public function __construct(
        public int $number,
        public string $title,
        public ?string $feat,
        public array $lyrics,
        public ?string $audio,
    ) {
    }

    public function withLyric(string $line): self
    {
        return new self($this->number, $this->title, $this->feat, [...$this->lyrics, $line], $this->audio);
    }
}
