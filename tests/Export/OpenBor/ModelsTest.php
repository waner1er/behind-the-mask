<?php

declare(strict_types=1);

namespace Vigilante\Tests\Export\OpenBor;

use PHPUnit\Framework\TestCase;
use Vigilante\Export\OpenBor\BossModels;
use Vigilante\Export\OpenBor\EnemyModels;
use Vigilante\Export\OpenBor\HeroModels;
use Vigilante\Export\OpenBor\ModelSheet;
use Vigilante\Sprite\CharacterCatalog;
use Vigilante\Sprite\SpriteSheet;

final class ModelsTest extends TestCase
{
    public function testEveryCabinetButtonDoesSomethingForVigiBapt(): void
    {
        $text = self::text(HeroModels::hero('Bapt', self::sheet('bapt'), ['damage' => 1, 'reach' => 40, 'knockback' => 2.6]), 'Bapt');

        // A frappe, L1 saute (+ A en l'air), R1 skate ; B, X, Y et A + Y via « com »
        foreach (['attack1', 'jump', 'jumpattack', 'special', 'freespecial', 'freespecial2', 'freespecial3', 'freespecial4'] as $anim) {
            self::assertMatchesRegularExpression("/^anim\t$anim$/m", $text);
        }
        foreach (['a2 freespecial', 'a3 freespecial3', 'a4 freespecial2', 'a + a4 freespecial4'] as $combo) {
            self::assertStringContainsString("com\t$combo", $text);
        }
        // le coup de guitare touche de 4 à 40 px devant les pieds (ancrage x = 19, sol y = 40)
        self::assertStringContainsString("attack\t23 10 36 18 10 0", $text);
    }

    public function testAnEnemyThrowsWhatTheWebGameThrows(): void
    {
        $text = self::text(EnemyModels::enemy('hooligan', self::sheet('hooligan'), [
            'hp' => 3, 'speed' => 0.65, 'damage' => 11, 'reach' => 26, 'projectile' => 'bottle', 'every' => 100,
            'moves' => ['throw' => 4],
        ]), 'Hooligan');

        self::assertStringContainsString("custbomb\tBottle", $text);
        self::assertStringContainsString("health\t30", $text);
        self::assertStringNotContainsString("range\t0 26", $text, 'un pur lanceur ne frappe pas au corps à corps');
    }

    public function testABossIsEnlargedPixelForPixel(): void
    {
        $boss = ['scale' => 2, 'sprite' => 'priest', 'name' => 'FATHER BOOZE', 'hp' => 48, 'speed' => 0.45,
            'damage' => 15, 'special' => 'throw', 'projectile' => 'bottle', 'every' => 95, 'line' => 'CHEERS'];
        $files = BossModels::boss(self::sheet('priest'), $boss)->files();

        self::assertSame([108, 84], array_slice(getimagesizefromstring($files['data/chars/Boss_priest/idle0.png']) ?: [], 0, 2));
        self::assertStringContainsString("offset\t38 80", $files['data/chars/Boss_priest/Boss_priest.txt']);
        self::assertSame(['aab', 'aab', 'ccd'], ModelSheet::enlarge(['ab', 'cd'], 1.5));
    }

    private static function sheet(string $name): SpriteSheet
    {
        $sheet = CharacterCatalog::all()[$name];
        self::assertInstanceOf(SpriteSheet::class, $sheet);

        return $sheet;
    }

    private static function text(\Vigilante\Export\OpenBor\CharacterModel $model, string $name): string
    {
        return $model->files()["data/chars/$name/$name.txt"];
    }
}
