<?php

declare(strict_types=1);

namespace Vigilante\Tests\Sprite;

use Vigilante\Sprite\CharacterCatalog;
use Vigilante\Sprite\PropCatalog;
use Vigilante\Sprite\SpriteSheet;
use Vigilante\Tests\SnapshotTestCase;

final class SpriteSnapshotTest extends SnapshotTestCase
{
    public function testCharacterSpritesAreUnchanged(): void
    {
        self::assertMatchesSnapshot('characters', json_encode(CharacterCatalog::all(), JSON_THROW_ON_ERROR));
    }

    public function testPropSpritesAreUnchanged(): void
    {
        self::assertMatchesSnapshot('props', json_encode(new PropCatalog(), JSON_THROW_ON_ERROR));
    }

    public function testEveryBossOfTheLevelsHasASprite(): void
    {
        $sprites = CharacterCatalog::all();
        $levels = require dirname(__DIR__, 2) . '/config/levels.php';

        foreach ($levels as $level) {
            self::assertInstanceOf(SpriteSheet::class, $sprites[$level['boss']['sprite']] ?? null, $level['boss']['sprite']);
        }
    }
}
