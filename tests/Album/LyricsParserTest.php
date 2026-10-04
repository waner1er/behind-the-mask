<?php

declare(strict_types=1);

namespace Vigilante\Tests\Album;

use PHPUnit\Framework\TestCase;
use Vigilante\Album\AudioLocator;
use Vigilante\Album\LyricsParser;

final class LyricsParserTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/vigilante-' . uniqid();
        mkdir($this->directory);
        touch($this->directory . '/Band - Album - 02 Second.mp3');
        touch($this->directory . '/Band - Album - 02 Second.wav');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory . '/*') ?: []);
        rmdir($this->directory);
    }

    public function testParsesTitleYearTracksLyricsAndLinks(): void
    {
        $markdown = <<<'MD'
            # Band - Album
            Année: 2026

            ## 01. First Song
            Line one / line two

            Line three
            ---
            ## 02. Second (feat. Guest)
            Hello
            ---
            - [Bandcamp](https://example.org/album)
            MD;

        $album = (new LyricsParser(new AudioLocator($this->directory, 'audio')))->parse($markdown);

        self::assertSame('Band - Album', $album->title);
        self::assertSame(2026, $album->year);
        self::assertCount(2, $album->tracks);
        self::assertSame(['Line one / line two', 'Line three'], $album->tracks[0]->lyrics);
        self::assertNull($album->tracks[0]->audio);
        self::assertSame('Guest', $album->tracks[1]->feat);
        self::assertSame('audio/Band%20-%20Album%20-%2002%20Second.mp3', $album->tracks[1]->audio, 'le MP3 passe avant le WAV');
        self::assertSame('https://example.org/album', $album->links[0]->url);
    }
}
