<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\SpriteSheet;

/** Les animations d'un pilote vu de dos : il roule, frappe (élan puis coup), encaisse. */
final class RearBuilder
{
    public const WIDTH = 56;
    public const HEIGHT = 48;

    public static function build(RearLook $look): SpriteSheet
    {
        $frame = fn(int $step, RearPose $pose) => Compositor::compose(
            self::WIDTH,
            self::HEIGHT,
            [...$look->body($step), ...$look->arms($pose)],
        );

        return new SpriteSheet($look->palette(), [
            'ride' => [$frame(0, RearPose::Ride), $frame(1, RearPose::Ride)],
            'attack' => [$frame(0, RearPose::Windup), $frame(0, RearPose::Strike)],
            'hurt' => [$frame(1, RearPose::Hurt)],
        ]);
    }

    /**
     * Décale tous les calques (pour poser un personnage plus ou moins haut sur son engin).
     *
     * @param list<Layer> $layers
     * @return list<Layer>
     */
    public static function shift(array $layers, int $dx, int $dy): array
    {
        return array_map(fn(Layer $layer) => $layer->shift($dx, $dy), $layers);
    }

    /** Le même calque de l'autre côté du corps (symétrie autour de x = WIDTH / 2). */
    public static function opposite(Layer $layer): Layer
    {
        $rows = self::mirror($layer->rows);

        return new Layer($rows, self::WIDTH - $layer->x - strlen($rows[0]), $layer->y, $layer->map);
    }

    /**
     * Miroir horizontal d'une grille (jambe gauche ↔ jambe droite).
     *
     * @param list<string> $rows
     * @return list<string>
     */
    public static function mirror(array $rows): array
    {
        $width = max(array_map('strlen', $rows));

        return array_map(fn(string $row) => strrev(str_pad($row, $width, '.')), $rows);
    }
}
