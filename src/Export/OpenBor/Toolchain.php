<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

use RuntimeException;

/** Les outils externes de l'export : Chrome (via Node) pour les décors, ffmpeg pour la musique. */
final readonly class Toolchain
{
    public function __construct(private string $root)
    {
    }

    /** Rend chaque fichier .svg du dossier en .png à côté. */
    public function rasterize(string $directory): void
    {
        $this->run(['node', "$this->root/tools/rasterize-svg.mjs", $directory]);
    }

    /** Filme une séquence animée du jeu web (images PNG + manifest.json dans le dossier). */
    public function captureStory(string $directory, string $sequence): void
    {
        $this->run(['node', "$this->root/tools/capture-story.mjs", $directory, $sequence]);
    }

    /**
     * Enregistre des bruitages du jeu web en WAV (tools/record-sounds.mjs).
     *
     * @param list<string> $sounds « nom » ou « nom:argument »
     */
    public function recordSounds(string $directory, array $sounds): void
    {
        $this->run(['node', "$this->root/tools/record-sounds.mjs", $directory, ...$sounds]);
    }

    /** Musique en Ogg Vorbis, assez légère pour un Raspberry Pi 2. */
    public function encodeMusic(string $source, string $target): void
    {
        $this->run(['ffmpeg', '-loglevel', 'error', '-y', '-i', $source, '-ac', '2', '-ar', '32000', '-c:a', 'libvorbis', '-q:a', '2', $target]);
    }

    /**
     * GIF animé à palette commune, images entières (le lecteur d'OpenBOR ignore les modes de remplacement).
     *
     * @param list<string> $frames PNG dans l'ordre
     * @param int $delay centièmes de seconde par image
     */
    public function animatedGif(array $frames, string $target, int $delay): void
    {
        $this->run(['convert', '-delay', (string) $delay, '-loop', '1', ...$frames, '+dither', '-colors', '255', '+map', $target]);
    }

    /** @param list<string> $command */
    private function run(array $command): void
    {
        exec(implode(' ', array_map('escapeshellarg', $command)) . ' 2>&1', $output, $status);
        if ($status !== 0) {
            throw new RuntimeException("Échec de « {$command[0]} » :\n" . implode("\n", $output));
        }
    }
}
