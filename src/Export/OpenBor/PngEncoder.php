<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

/**
 * PNG à palette en 8 bits exactement : le décodeur d'OpenBOR refuse toute autre profondeur
 * (GD descend à 4 bits quand il y a peu de couleurs). Sortie déterministe, sans métadonnées.
 */
final class PngEncoder
{
    private const SIGNATURE = "\x89PNG\r\n\x1a\n";

    public static function encode(IndexedImage $image): string
    {
        // profondeur 8, type 3 (palette), compression, filtre et entrelacement par défaut
        $header = pack('NNCCCCC', $image->width, $image->height, 8, 3, 0, 0, 0);

        $palette = '';
        foreach ($image->palette as [$r, $g, $b]) {
            $palette .= chr($r) . chr($g) . chr($b);
        }

        $raw = '';
        foreach (str_split($image->pixels, $image->width) as $row) {
            $raw .= "\0" . $row; // filtre 0 (aucun) sur chaque ligne
        }

        return self::SIGNATURE
            . self::chunk('IHDR', $header)
            . self::chunk('PLTE', $palette)
            . self::chunk('tRNS', "\0") // index 0 transparent, pour les visionneuses (OpenBOR l'ignore)
            . self::chunk('IDAT', (string) gzcompress($raw, 9))
            . self::chunk('IEND', '');
    }

    private static function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    }
}
