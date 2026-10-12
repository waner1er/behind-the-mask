<?php

declare(strict_types=1);

namespace Vigilante\Build;

use Vigilante\Application;

/**
 * Prépare le jeu pour l'app iOS (ios/www/, copié tel quel dans l'app par Xcode) :
 * la version statique, plus le code et les seuls médias lus par la page.
 * Le dossier est vidé à chaque build : il ne contient que du généré.
 */
final readonly class IosBundleBuilder
{
    /** Servis tels quels : le code, le style compilé, les polices. */
    private const COPIED = ['js', 'build/style.css', 'medias/fonts'];

    public function __construct(private Application $app, private string $target)
    {
    }

    /** @return list<string> fichiers et dossiers écrits, relatifs au dossier cible */
    public function build(): array
    {
        $this->remove($this->target);
        $written = (new StaticSiteBuilder($this->app, $this->target))->build();

        foreach ([...self::COPIED, ...$this->media()] as $path) {
            $this->copy($this->app->root . '/' . $path, $this->target . '/' . $path);
            $written[] = $path;
        }

        return $written;
    }

    /**
     * Le logo et les morceaux de l'album, pas tout medias/ : ni les WAV, ni les originaux.
     *
     * @return list<string>
     */
    private function media(): array
    {
        $files = [$this->app->config()->get('game')['logo']];
        foreach ($this->app->album()->tracks as $track) {
            if ($track->audio !== null) {
                $files[] = rawurldecode($track->audio);
            }
        }

        return $files;
    }

    private function copy(string $source, string $destination): void
    {
        if (!file_exists($source)) {
            throw new \RuntimeException("Fichier introuvable : $source");
        }
        if (is_dir($source)) {
            foreach (new \FilesystemIterator($source) as $entry) {
                $this->copy($entry->getPathname(), $destination . '/' . $entry->getFilename());
            }

            return;
        }
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0777, true);
        }
        copy($source, $destination);
    }

    private function remove(string $path): void
    {
        if (is_dir($path) && !is_link($path)) {
            foreach (new \FilesystemIterator($path) as $entry) {
                $this->remove($entry->getPathname());
            }
            rmdir($path);
        } elseif (file_exists($path) || is_link($path)) {
            unlink($path);
        }
    }
}
