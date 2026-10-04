<?php

declare(strict_types=1);

namespace Vigilante\Support;

/** Lit (une seule fois) les fichiers de config/ qui renvoient un tableau PHP. */
final class ConfigRepository
{
    /** @var array<string, array<mixed>> */
    private array $loaded = [];

    public function __construct(private readonly string $directory)
    {
    }

    /** @return array<mixed> */
    public function get(string $name): array
    {
        return $this->loaded[$name] ??= require $this->directory . '/' . $name . '.php';
    }
}
