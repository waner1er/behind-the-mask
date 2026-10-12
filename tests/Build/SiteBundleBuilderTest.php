<?php

declare(strict_types=1);

namespace Vigilante\Tests\Build;

use PHPUnit\Framework\TestCase;
use Vigilante\Application;
use Vigilante\Build\SiteBundleBuilder;

/** GitHub Pages n'a pas PHP, l'app iOS ni PHP ni réseau : tout ce que la page demande doit être dans le dossier. */
final class SiteBundleBuilderTest extends TestCase
{
    private static string $target;

    public static function setUpBeforeClass(): void
    {
        self::$target = sys_get_temp_dir() . '/vigilante-site-' . getmypid();
        (new SiteBundleBuilder(new Application(dirname(__DIR__, 2)), self::$target))->build();
    }

    public static function tearDownAfterClass(): void
    {
        exec('rm -rf ' . escapeshellarg(self::$target));
    }

    public function testThePageNeedsNoNetwork(): void
    {
        self::assertStringNotContainsString('https://', $this->page());
    }

    public function testEveryFileThePageAsksForIsBundled(): void
    {
        $page = $this->page();
        preg_match_all('/(?:src|href)="([^"?#]+)/', $page, $tags);
        preg_match_all('/"audio":"([^"]+)"/', $page, $music);
        $scenes = glob(self::$target . '/scenes/level-*.html') ?: [];

        self::assertNotEmpty($music[1]);
        self::assertCount(count($music[1]) + 1, $scenes, 'un décor par morceau, plus la ville en paix');
        foreach ([...$tags[1], ...$music[1], 'js/main.js', 'medias/fonts/PermanentMarker-Regular.ttf'] as $url) {
            $file = rawurldecode(stripslashes($url));
            self::assertFileExists(self::$target . '/' . $file, $file);
        }
    }

    public function testOnlyTheMediaReadByThePageIsCopied(): void
    {
        self::assertFileDoesNotExist(self::$target . '/medias/images/logo-vigilante-original.png');
        self::assertSame([], glob(self::$target . '/medias/audio/*/*.wav'));
    }

    private function page(): string
    {
        return (string) file_get_contents(self::$target . '/index.html');
    }
}
