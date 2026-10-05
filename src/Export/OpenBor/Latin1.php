<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

/**
 * OpenBOR lit ses textes octet par octet, et ses polices ont une case par octet :
 * les textes du jeu (UTF-8) passent en ISO-8859-1, les signes absents deviennent leur équivalent.
 */
final class Latin1
{
    private const REPLACEMENTS = ['’' => "'", '‘' => "'", '“' => '"', '”' => '"', '…' => '...', '–' => '-', '—' => '-', '➜' => '>', '▶' => '>'];

    public static function encode(string $utf8): string
    {
        return (string) mb_convert_encoding(strtr($utf8, self::REPLACEMENTS), 'ISO-8859-1', 'UTF-8');
    }

    /** Chaîne littérale pour un script OpenBOR, en UTF-8 : le fichier entier passe ensuite par encode(). */
    public static function literal(string $utf8): string
    {
        return '"' . addcslashes($utf8, '"\\') . '"';
    }
}
