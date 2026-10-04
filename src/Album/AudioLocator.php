<?php

declare(strict_types=1);

namespace Vigilante\Album;

/**
 * Retrouve le fichier audio d'un morceau d'après son numéro (« … - 03 Titre.mp3 »).
 * Le .mp3 (léger) est préféré au .wav.
 */
final readonly class AudioLocator
{
    private const EXTENSIONS = ['mp3', 'ogg', 'wav'];

    /**
     * @param string $directory dossier sur le disque
     * @param string $baseUrl même dossier, en URL relative déjà encodée
     */
    public function __construct(private string $directory, private string $baseUrl)
    {
    }

    public function find(int $number): ?string
    {
        $pattern = sprintf('%s/*- %02d *', $this->directory, $number);

        foreach (self::EXTENSIONS as $extension) {
            $files = glob("$pattern.$extension");
            if ($files) {
                return $this->baseUrl . '/' . rawurlencode(basename($files[0]));
            }
        }

        return null;
    }
}
