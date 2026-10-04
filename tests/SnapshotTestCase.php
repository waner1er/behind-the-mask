<?php

declare(strict_types=1);

namespace Vigilante\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Compare une sortie à son empreinte enregistrée dans tests/snapshots/.
 * Après un changement voulu (nouveau décor, sprite retouché) : UPDATE_SNAPSHOTS=1 composer test
 */
abstract class SnapshotTestCase extends TestCase
{
    protected static function assertMatchesSnapshot(string $name, string $content): void
    {
        $file = __DIR__ . '/snapshots/' . $name . '.sha1';
        $hash = sha1($content);

        if (getenv('UPDATE_SNAPSHOTS') || !is_file($file)) {
            file_put_contents($file, $hash . "\n");
        }

        self::assertSame(
            trim((string) file_get_contents($file)),
            $hash,
            "« $name » a changé. Si c'est voulu : UPDATE_SNAPSHOTS=1 composer test",
        );
    }
}
