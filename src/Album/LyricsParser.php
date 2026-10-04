<?php

declare(strict_types=1);

namespace Vigilante\Album;

/**
 * Lit le Markdown des paroles :
 *
 *   # Groupe - Album
 *   Année: 2026
 *   ## 01. Titre (feat. Invité)
 *   ...paroles...
 *   ---
 *   - [Bandcamp](https://...)
 */
final readonly class LyricsParser
{
    public function __construct(private AudioLocator $audio)
    {
    }

    public function parse(string $markdown): Album
    {
        $title = '';
        $year = null;
        $tracks = [];
        $links = [];
        $current = null;

        foreach (preg_split('/\R/', $markdown) ?: [] as $line) {
            $line = trim($line);

            if (preg_match('/^# (.+)$/', $line, $m)) {
                $title = $m[1];
            } elseif (preg_match('/^Année:\s*(\d{4})/u', $line, $m)) {
                $year = (int) $m[1];
            } elseif (preg_match('/^## (\d+)\.\s*(.+?)(?:\s*\(feat\.\s*(.+)\))?$/i', $line, $m)) {
                $current = count($tracks);
                $number = (int) $m[1];
                $tracks[] = new Track($number, $m[2], $m[3] ?? null, [], $this->audio->find($number));
            } elseif ($line === '---') {
                $current = null;
            } elseif (preg_match('/^- \[(.+?)\]\((https?:\/\/.+?)\)$/', $line, $m)) {
                $links[] = new Link($m[1], $m[2]);
            } elseif ($current !== null && $line !== '') {
                $tracks[$current] = $tracks[$current]->withLyric($line);
            }
        }

        return new Album($title, $year, $tracks, $links);
    }
}
