<?php

declare(strict_types=1);

namespace Vigilante\Album;

final readonly class Album
{
    /**
     * @param list<Track> $tracks dans l'ordre de l'album
     * @param list<Link> $links
     */
    public function __construct(
        public string $title,
        public ?int $year,
        public array $tracks,
        public array $links,
    ) {
    }

    /** @param int $index position dans l'album, à partir de 0 */
    public function track(int $index): ?Track
    {
        return $this->tracks[$index] ?? null;
    }
}
