<?php

/**
 * Lit le fichier paroles.md de l'album et retrouve le fichier audio de chaque morceau.
 *
 * Format attendu du Markdown :
 *   # Groupe - Album
 *   Année: 2026
 *   ## 01. Titre (feat. Invité)
 *   ...paroles...
 *   ---
 *   - [Bandcamp](https://...)
 */
final class Album
{
    /**
     * @return array{title: string, year: string, tracks: array<int, array>, links: array<int, array>}
     */
    public static function load(string $directory, string $baseUrl): array
    {
        $markdown = file_get_contents($directory . '/paroles.md');
        $album = ['title' => '', 'year' => '', 'tracks' => [], 'links' => []];
        $current = null;

        foreach (preg_split('/\R/', $markdown) as $line) {
            $line = trim($line);

            if (preg_match('/^# (.+)$/', $line, $m)) {
                $album['title'] = $m[1];
            } elseif (preg_match('/^Année:\s*(\d{4})/u', $line, $m)) {
                $album['year'] = $m[1];
            } elseif (preg_match('/^## (\d+)\.\s*(.+?)(?:\s*\(feat\.\s*(.+)\))?$/i', $line, $m)) {
                $current = count($album['tracks']);
                $album['tracks'][] = [
                    'number' => (int) $m[1],
                    'title' => $m[2],
                    'feat' => $m[3] ?? null,
                    'lyrics' => [],
                    'audio' => self::findAudio($directory, $baseUrl, (int) $m[1]),
                    'duration' => null,
                ];
            } elseif ($line === '---') {
                $current = null;
            } elseif (preg_match('/^- \[(.+?)\]\((https?:\/\/.+?)\)$/', $line, $m)) {
                $album['links'][] = ['name' => $m[1], 'url' => $m[2]];
            } elseif ($current !== null && $line !== '') {
                $album['tracks'][$current]['lyrics'][] = $line;
            }
        }

        return $album;
    }

    /**
     * Découpe les paroles en petites phrases (sur les "/" et les retours à la ligne),
     * pour les graffitis et les cris des personnages.
     *
     * @param string[] $lyrics
     * @return string[]
     */
    public static function phrases(array $lyrics, int $maxLength = 24, array $exclude = []): array
    {
        $phrases = [];

        foreach ($lyrics as $line) {
            foreach (explode('/', $line) as $part) {
                $part = strtoupper(trim($part, " \t.,:;"));
                $part = preg_replace('/\s+/', ' ', $part);
                if ($part === '' || strlen($part) > $maxLength || strlen($part) < 4) {
                    continue;
                }
                foreach ($exclude as $word) {
                    if (str_contains($part, strtoupper($word))) {
                        continue 2;
                    }
                }
                $phrases[$part] = true;
            }
        }

        return array_keys($phrases);
    }

    /** Préfère le .mp3 (léger) au .wav s'il existe. */
    private static function findAudio(string $directory, string $baseUrl, int $number): ?string
    {
        $pattern = sprintf('%s/*- %02d *', $directory, $number);

        foreach (['mp3', 'ogg', 'wav'] as $extension) {
            $files = glob("$pattern.$extension");
            if ($files) {
                return $baseUrl . '/' . rawurlencode(basename($files[0]));
            }
        }

        return null;
    }
}
