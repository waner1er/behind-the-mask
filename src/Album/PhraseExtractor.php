<?php

declare(strict_types=1);

namespace Vigilante\Album;

/**
 * Découpe les paroles en petites phrases en majuscules (sur les « / » et les retours
 * à la ligne), pour les graffitis du décor et les cris des personnages.
 */
final readonly class PhraseExtractor
{
    private const MIN_LENGTH = 4;

    /** @param list<string> $excluded phrases à ne jamais afficher */
    public function __construct(private array $excluded = [])
    {
    }

    /**
     * @param list<string> $lyrics
     * @return list<string> sans doublons, dans l'ordre des paroles
     */
    public function extract(array $lyrics, int $maxLength): array
    {
        $phrases = [];

        foreach ($lyrics as $line) {
            foreach (explode('/', $line) as $part) {
                $part = (string) preg_replace('/\s+/', ' ', strtoupper(trim($part, " \t.,:;")));
                if ($this->accepts($part, $maxLength)) {
                    $phrases[$part] = true;
                }
            }
        }

        return array_keys($phrases);
    }

    private function accepts(string $phrase, int $maxLength): bool
    {
        if (strlen($phrase) < self::MIN_LENGTH || strlen($phrase) > $maxLength) {
            return false;
        }
        foreach ($this->excluded as $word) {
            if (str_contains($phrase, strtoupper($word))) {
                return false;
            }
        }

        return true;
    }
}
