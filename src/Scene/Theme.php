<?php

declare(strict_types=1);

namespace Vigilante\Scene;

/**
 * Ambiance d'un décor (voir config/levels.php).
 * La graine (seed) donne toujours la même rue ; chaos (0 à 1) fait partir la ville en ruine.
 */
final readonly class Theme
{
    /** @param list<string> $signs enseignes lumineuses des immeubles */
    public function __construct(
        public int $seed,
        public Sky $sky = Sky::Paper,
        public Celestial $celestial = Celestial::None,
        public string $accent = '#e8203a',
        public array $signs = ['BAR'],
        public float $chaos = 0,
        public bool $peace = false,
        public bool $datacenter = false,
        public bool $rain = false,
        public bool $fog = false,
    ) {
    }

    /** @param array<string, mixed> $theme */
    public static function fromArray(array $theme): self
    {
        return new self(
            seed: $theme['seed'],
            sky: Sky::from($theme['sky'] ?? 'paper'),
            celestial: Celestial::from($theme['celestial'] ?? 'none'),
            accent: $theme['accent'] ?? '#e8203a',
            signs: $theme['signs'] ?? ['BAR'],
            chaos: $theme['chaos'] ?? 0,
            peace: $theme['peace'] ?? false,
            datacenter: $theme['datacenter'] ?? false,
            rain: $theme['rain'] ?? false,
            fog: $theme['fog'] ?? false,
        );
    }

    public function isNight(): bool
    {
        return $this->sky === Sky::Night;
    }
}
