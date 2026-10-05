<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use DOMDocument;
use GdImage;
use RuntimeException;

/**
 * La fiche du jeu dans les menus de Recalbox : gamelist.xml (nom, description, joueurs...)
 * et la pochette (celle de l'album), à copier à côté du .pak dans share/roms/openbor/.
 */
final readonly class RecalboxGamelist
{
    /** Pochette réduite : l'écran d'un Pi 2 n'a pas besoin de 1800 px. */
    private const COVER_SIZE = 600;

    public function __construct(private string $pakName, private string $coverFile, private ?int $year)
    {
    }

    /** @return array<string, string> fichiers (chemin relatif à roms/openbor/ => contenu) */
    public function files(): array
    {
        $image = "media/images/$this->pakName.jpg";

        return [
            'gamelist.xml' => $this->xml("./$image"),
            $image => $this->cover(),
        ];
    }

    private function xml(string $image): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $list = $document->appendChild($document->createElement('gameList'));
        $game = $list->appendChild($document->createElement('game'));
        $fields = [
            'path' => "./$this->pakName.pak",
            'name' => 'Vigilante - Behind the Mask',
            'desc' => "Beat'em up punk hardcore : un niveau par morceau de l'album de Vigilante. "
                . 'Pete, aïkidoka au katana, et VigiBapt, à la guitare, nettoient la ville jusqu\'au data center du Docteur Mask.',
            'image' => $image,
            'developer' => 'Vigilante',
            'publisher' => 'Vigilante',
            'genre' => "Beat'em up",
            'players' => '1-2',
        ];
        if ($this->year !== null) {
            $fields['releasedate'] = sprintf('%04d0101T000000', $this->year);
        }
        foreach ($fields as $tag => $value) {
            $game->appendChild($document->createElement($tag))->appendChild($document->createTextNode($value));
        }

        return (string) $document->saveXML();
    }

    private function cover(): string
    {
        $source = @imagecreatefromjpeg($this->coverFile);
        if (!$source instanceof GdImage) {
            throw new RuntimeException("Pochette illisible : $this->coverFile");
        }
        $cover = imagescale($source, self::COVER_SIZE, self::COVER_SIZE);
        if (!$cover instanceof GdImage) {
            throw new RuntimeException('Pochette impossible à réduire');
        }
        ob_start();
        imagejpeg($cover, null, 88);

        return (string) ob_get_clean();
    }
}
