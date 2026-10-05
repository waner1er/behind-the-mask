<?php

declare(strict_types=1);

namespace Vigilante\Tests\Export\OpenBor;

use PHPUnit\Framework\TestCase;
use Vigilante\Export\OpenBor\SceneLayers;

final class SceneLayersTest extends TestCase
{
    public function testSplitsTheFixedSkyAndEachParallaxLayer(): void
    {
        $scene = '<defs><pattern id="p"/></defs><rect id="sky"/>'
            . '<g class="layer" data-factor="0.25"><rect id="far"/></g>'
            . '<g class="layer" data-factor="1"><rect id="street"/></g>';

        $layers = SceneLayers::split($scene);

        self::assertSame(['0', '0.25', '1'], array_map('strval', array_keys($layers)));
        self::assertStringContainsString('id="sky"', $layers[0]);
        self::assertStringNotContainsString('id="far"', $layers[0]);
        self::assertStringContainsString('id="far"', $layers['0.25']);
        self::assertStringContainsString('<defs>', $layers['0.25'], 'les motifs restent disponibles dans chaque plan');
        self::assertStringNotContainsString('id="sky"', $layers[1]);
    }
}
