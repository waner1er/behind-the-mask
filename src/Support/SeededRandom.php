<?php

declare(strict_types=1);

namespace Vigilante\Support;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Hasard reproductible : une même graine donne toujours la même suite de tirages,
 * donc le même décor et les mêmes vagues d'ennemis.
 * (Mt19937 produit exactement la même suite que mt_srand()/mt_rand().)
 */
final class SeededRandom
{
    private Randomizer $randomizer;

    public function __construct(int $seed)
    {
        $this->randomizer = new Randomizer(new Mt19937($seed));
    }

    public function int(int $min, int $max): int
    {
        return $this->randomizer->getInt($min, $max);
    }

    /** Vrai avec une probabilité de $percent %. */
    public function chance(float $percent): bool
    {
        return $this->int(0, 99) < $percent;
    }

    /** Vrai une fois sur $sides. */
    public function oneIn(int $sides): bool
    {
        return $this->int(0, $sides - 1) === 0;
    }

    /**
     * @template T
     * @param list<T> $list
     * @return T
     */
    public function pick(array $list): mixed
    {
        return $list[$this->int(0, count($list) - 1)];
    }

    /** Délai d'animation CSS aléatoire, en dixièmes de seconde (0 à $max). */
    public function tenths(int $max): float
    {
        return $this->int(0, $max) / 10;
    }
}
