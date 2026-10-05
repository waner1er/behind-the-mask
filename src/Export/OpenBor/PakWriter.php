<?php

declare(strict_types=1);

namespace Vigilante\Export\OpenBor;

/**
 * Archive .pak d'OpenBOR (format de borpak) : « PACK », version 0, les fichiers bout à bout,
 * puis le répertoire (taille de l'entrée, début, taille, nom avec « \ » terminé par un zéro)
 * et enfin la position du répertoire. Entiers 32 bits petit-boutistes.
 */
final class PakWriter
{
    /** @param array<string, string> $files chemin (« data/models.txt ») => contenu */
    public static function pack(array $files): string
    {
        ksort($files); // archive identique d'un build à l'autre
        $body = 'PACK' . pack('V', 0);
        $directory = '';
        $stored = []; // contenu déjà écrit => position : un fichier identique n'est stocké qu'une fois
        foreach ($files as $path => $content) {
            $hash = sha1($content);
            if (!isset($stored[$hash])) {
                $stored[$hash] = strlen($body);
                $body .= $content;
            }
            $name = str_replace('/', '\\', $path) . "\0";
            $directory .= pack('VVV', 12 + strlen($name), $stored[$hash], strlen($content)) . $name;
        }

        return $body . $directory . pack('V', strlen($body));
    }
}
