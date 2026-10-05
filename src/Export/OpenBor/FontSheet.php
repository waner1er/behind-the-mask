<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use RuntimeException;

/**
 * Police d'OpenBOR : une image de 16 × 16 cases, une par code ASCII (case 65 = « A »).
 * Dessinée avec Press Start 2P, la police de la borne web, sans anticrénelage.
 */
final readonly class FontSheet
{
    /** Cases de 10 px pour des lettres de 8 : le moteur espace les lettres d'un dixième de case. */
    private const CELL = 10;

    public function __construct(private string $ttf)
    {
    }

    /** @param string $hex couleur des lettres */
    public function render(string $hex): IndexedImage
    {
        $size = self::CELL * 16;
        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagealphablending($image, true);

        [$r, $g, $b] = IndexedImage::rgb($hex);
        $ink = imagecolorallocate($image, $r, $g, $b);
        // ombre portée d'un pixel, comme le text-shadow du HUD web : lisible sur le ciel clair
        $shadow = imagecolorallocate($image, 0x0c, 0x0a, 0x10);
        // la case 0 (jamais affichée) donne sa palette à toute la police : vide, le moteur plante
        imagefilledrectangle($image, 0, 0, self::CELL - 1, self::CELL - 1, $ink);
        // ASCII et Latin-1 (accents) : les textes du module sont encodés en ISO-8859-1 (voir Latin1)
        foreach ([...range(32, 126), ...range(160, 255)] as $code) {
            $x = ($code % 16) * self::CELL;
            $y = intdiv($code, 16) * self::CELL;
            // 6 points à 96 ppp = 8 px, la taille native de la police ; couleur négative = sans lissage
            imagettftext($image, 6, 0, $x + 2, $y + 9, -$shadow, $this->ttf, mb_chr($code));
            if (imagettftext($image, 6, 0, $x + 1, $y + 8, -$ink, $this->ttf, mb_chr($code)) === false) {
                throw new RuntimeException("Police illisible : $this->ttf");
            }
        }

        return Quantizer::quantize($image);
    }
}
