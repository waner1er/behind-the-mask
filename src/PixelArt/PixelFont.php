<?php

declare(strict_types=1);

namespace Vigilante\PixelArt;

/**
 * Petite police 3 × 5 pixels, pour écrire dans un sprite ou un décor
 * (panneaux du bord de route, lettres géantes sur la colline).
 */
final class PixelFont
{
    public const HEIGHT = 5;

    private const GLYPHS = [
        'A' => ['.#.', '#.#', '###', '#.#', '#.#'],
        'B' => ['##.', '#.#', '##.', '#.#', '##.'],
        'C' => ['.##', '#..', '#..', '#..', '.##'],
        'D' => ['##.', '#.#', '#.#', '#.#', '##.'],
        'E' => ['###', '#..', '##.', '#..', '###'],
        'F' => ['###', '#..', '##.', '#..', '#..'],
        'G' => ['.##', '#..', '#.#', '#.#', '.##'],
        'H' => ['#.#', '#.#', '###', '#.#', '#.#'],
        'I' => ['###', '.#.', '.#.', '.#.', '###'],
        'J' => ['..#', '..#', '..#', '#.#', '.#.'],
        'K' => ['#.#', '#.#', '##.', '#.#', '#.#'],
        'L' => ['#..', '#..', '#..', '#..', '###'],
        'M' => ['#.#', '###', '###', '#.#', '#.#'],
        'N' => ['##.', '#.#', '#.#', '#.#', '#.#'],
        'O' => ['.#.', '#.#', '#.#', '#.#', '.#.'],
        'P' => ['##.', '#.#', '##.', '#..', '#..'],
        'Q' => ['.#.', '#.#', '#.#', '##.', '.##'],
        'R' => ['##.', '#.#', '##.', '#.#', '#.#'],
        'S' => ['.##', '#..', '.#.', '..#', '##.'],
        'T' => ['###', '.#.', '.#.', '.#.', '.#.'],
        'U' => ['#.#', '#.#', '#.#', '#.#', '###'],
        'V' => ['#.#', '#.#', '#.#', '#.#', '.#.'],
        'W' => ['#.#', '#.#', '###', '###', '#.#'],
        'X' => ['#.#', '#.#', '.#.', '#.#', '#.#'],
        'Y' => ['#.#', '#.#', '.#.', '.#.', '.#.'],
        'Z' => ['###', '..#', '.#.', '#..', '###'],
        '0' => ['###', '#.#', '#.#', '#.#', '###'],
        '1' => ['.#.', '##.', '.#.', '.#.', '###'],
        '2' => ['##.', '..#', '.#.', '#..', '###'],
        '4' => ['#.#', '#.#', '###', '..#', '..#'],
        '7' => ['###', '..#', '.#.', '.#.', '.#.'],
        '!' => ['.#.', '.#.', '.#.', '...', '.#.'],
        '?' => ['##.', '..#', '.#.', '...', '.#.'],
        ' ' => ['...', '...', '...', '...', '...'],
    ];

    /**
     * Le texte en pixels : $ink pour les lettres, un pixel vide entre deux lettres.
     * Les caractères inconnus deviennent des espaces.
     *
     * @return list<string>
     */
    public static function write(string $text, string $ink): array
    {
        $rows = array_fill(0, self::HEIGHT, '');
        foreach (str_split(strtoupper($text)) as $i => $char) {
            $glyph = self::GLYPHS[$char] ?? self::GLYPHS[' '];
            foreach ($glyph as $y => $line) {
                $rows[$y] .= ($i > 0 ? Grid::EMPTY : '') . str_replace('#', $ink, $line);
            }
        }

        return $rows;
    }

    /** Largeur du texte en pixels. */
    public static function width(string $text): int
    {
        return max(0, strlen($text) * 4 - 1);
    }
}
