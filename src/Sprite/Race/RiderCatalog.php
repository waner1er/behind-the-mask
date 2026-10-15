<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

use Vigilante\Sprite\SpriteSheet;

/**
 * Les pilotes de la course vus de dos (clé « race.riders » des données du jeu) :
 * les héros en skate et les ennemis de config/game.php → « riders ».
 */
final class RiderCatalog
{
    /** @return array<string, SpriteSheet> */
    public static function all(): array
    {
        return array_map(RearBuilder::build(...), [
            'hero' => new PeteRear(),
            'bapt' => new BaptRear(),
            'roller' => new RollerSkin(),
            'bmx' => new BmxBro(),
            'moto' => new MotoCop(),
        ]);
    }
}
