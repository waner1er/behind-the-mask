<?php

declare(strict_types=1);

namespace Vigilante\View;

/**
 * Ajoute la date de modification aux URL des fichiers (?v=…) : le navigateur
 * recharge un fichier dès qu'il change, sans vider son cache.
 */
final readonly class AssetVersioner
{
    public function __construct(private string $root)
    {
    }

    public function url(string $file): string
    {
        return $file . '?v=' . filemtime($this->root . '/' . $file);
    }

    /**
     * Import map des modules JavaScript : chaque import (./Game.js...) pointe vers
     * sa version datée, pour que les modules soient eux aussi rechargés après une mise à jour.
     */
    public function importMap(string $directory): string
    {
        $imports = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root . '/' . $directory));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'js') {
                $path = $directory . substr($file->getPathname(), strlen($this->root . '/' . $directory));
                $imports['./' . $path] = './' . $this->url($path);
            }
        }
        ksort($imports);

        return json_encode(['imports' => $imports], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
