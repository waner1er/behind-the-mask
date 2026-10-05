<?php

declare(strict_types=1);

namespace Vigilante\Tests\Export\OpenBor;

use PHPUnit\Framework\TestCase;
use Vigilante\Export\OpenBor\PakWriter;

final class PakWriterTest extends TestCase
{
    public function testEveryFileCanBeFoundThroughTheDirectory(): void
    {
        $files = ['data/models.txt' => 'load Pete', 'data/music/01.ogg' => 'OggS...'];

        self::assertSame($files, self::unpack(PakWriter::pack($files)));
    }

    public function testIdenticalFilesAreStoredOnce(): void
    {
        $music = str_repeat('OggS', 1000);
        $pak = PakWriter::pack(['data/music/menu.ogg' => $music, 'data/music/remix.ogg' => $music]);

        self::assertLessThan(2 * strlen($music), strlen($pak));
        self::assertSame($music, self::unpack($pak)['data/music/remix.ogg']);
    }

    /**
     * Relit une archive comme le fait OpenBOR (packfile.c).
     *
     * @return array<string, string>
     */
    private static function unpack(string $pak): array
    {
        self::assertSame('PACK', substr($pak, 0, 4));
        $position = unpack('V', substr($pak, -4))[1];
        $files = [];
        while ($position < strlen($pak) - 4) {
            ['length' => $length, 'start' => $start, 'size' => $size] = unpack('Vlength/Vstart/Vsize', $pak, $position) ?: [];
            $name = rtrim(substr($pak, $position + 12, $length - 12), "\0");
            $files[str_replace('\\', '/', $name)] = substr($pak, $start, $size);
            $position += $length;
        }

        return $files;
    }
}
