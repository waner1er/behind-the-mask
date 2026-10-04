<?php

declare(strict_types=1);

namespace Vigilante\Album;

/** Charge un album depuis son dossier : paroles.md + fichiers audio. */
final readonly class AlbumLoader
{
    public const LYRICS_FILE = 'paroles.md';

    /**
     * @param string $root racine du projet
     * @param string $directory dossier de l'album, relatif à la racine
     */
    public function __construct(private string $root, private string $directory)
    {
    }

    public function load(): Album
    {
        $path = $this->root . '/' . $this->directory;
        $url = implode('/', array_map('rawurlencode', explode('/', $this->directory)));
        $parser = new LyricsParser(new AudioLocator($path, $url));

        return $parser->parse((string) file_get_contents($path . '/' . self::LYRICS_FILE));
    }
}
