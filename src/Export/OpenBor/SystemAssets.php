<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use GdImage;
use RuntimeException;
use Vigilante\Scene\Screen;

/** Ce que le moteur va chercher à des chemins fixes : polices, ombres, flèche GO, écran titre. */
final readonly class SystemAssets
{
    /** Couleurs des polices 1 à 4 (blanc papier, rouge Vigilante, jaune, gris). */
    private const FONT_COLORS = ['#ece8dc', '#e8203a', '#ffd23f', '#9d998f'];

    private const ARROW = [
        'WWW..WWW..W...',
        'W....W.W..WW..',
        'W.WW.W.W..WWW.',
        'W..W.W.W..WW..',
        'WWWW.WWW..W...',
    ];

    public function __construct(private string $fontFile, private string $logoFile)
    {
    }

    /** @return array<string, string> */
    public function files(): array
    {
        $fonts = new FontSheet($this->fontFile);
        $files = [];
        foreach (self::FONT_COLORS as $i => $color) {
            $files['data/sprites/font' . ($i === 0 ? '' : $i + 1) . '.png'] = PngEncoder::encode($fonts->render($color));
        }
        for ($i = 1; $i <= 6; $i++) {
            $files["data/sprites/shadow$i.png"] = PngEncoder::encode(self::shadow(6 + $i * 4, 2 + intdiv($i, 2)));
        }
        $arrow = IndexedImage::fromGrid(self::ARROW, ['W'], ['W' => '#ece8dc']);
        $files['data/sprites/arrow.png'] = PngEncoder::encode($arrow);
        $files['data/sprites/arrowl.png'] = PngEncoder::encode($arrow);
        // écrans obligatoires : logo au lancement, titleb (fond) et title (par-dessus) à l'accueil
        $title = PngEncoder::encode($this->title());
        foreach (['logo', 'titleb', 'title'] as $screen) {
            $files["data/bgs/$screen.png"] = $title;
        }

        return $files;
    }

    /** Ombre au sol : une ellipse sombre. */
    private static function shadow(int $width, int $height): IndexedImage
    {
        $rows = [];
        for ($y = 0; $y < $height; $y++) {
            $row = '';
            for ($x = 0; $x < $width; $x++) {
                $dx = ($x + 0.5 - $width / 2) / ($width / 2);
                $dy = ($y + 0.5 - $height / 2) / ($height / 2);
                $row .= $dx * $dx + $dy * $dy <= 1 ? 'S' : '.';
            }
            $rows[] = $row;
        }

        return IndexedImage::fromGrid($rows, ['S'], ['S' => '#0c0a10']);
    }

    /** Le logo du groupe au centre d'un écran noir. */
    private function title(): IndexedImage
    {
        $logo = @imagecreatefrompng($this->logoFile);
        if (!$logo instanceof GdImage) {
            throw new RuntimeException("Logo illisible : $this->logoFile");
        }
        $screen = imagecreatetruecolor(Screen::WIDTH, Screen::HEIGHT);
        imagefill($screen, 0, 0, imagecolorallocate($screen, 12, 10, 16));
        $height = Screen::HEIGHT - 30;
        $width = (int) round(imagesx($logo) * $height / imagesy($logo));
        imagecopyresampled($screen, $logo, intdiv(Screen::WIDTH - $width, 2), 6, 0, 0, $width, $height, imagesx($logo), imagesy($logo));

        return Quantizer::quantize($screen);
    }
}
