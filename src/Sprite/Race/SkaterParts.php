<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

use Vigilante\PixelArt\Layer;

/**
 * Ce que Pete et VigiBapt ont en commun vus de dos : la position groupée sur la planche,
 * le skate vu par l'arrière et les bras (C/c = manche, S/s = main).
 */
final class SkaterParts
{
    /** Épaules : le haut du corps descend d'un pixel sur l'image 1 (les genoux plient). */
    public const SHOULDER_Y = 22;

    private const BOARD = [
        '..kkkkkkkk..',
        '.TTTTTTTTTT.',
        'WW........WW',
    ];

    private const LEGS = [
        '.AAAAAAAAAA.',
        '.AAAAAAAAAA.',
        'AAAAa..aAAAA',
        'AAAa....aAAA',
        'AAAa....aAAA',
        '.AAAa..aAAA.',
        '..AAa..aAA..',
        '..AAa..aAA..',
        '..AAa..aAA..',
        '.OOOo..oOOO.',
        '.OOOo..oOOO.',
        '.XXXX..XXXX.',
    ];

    /** Bras : [x, y, lignes] pour chaque pose. */
    private const ARMS = [
        'balance' => [15, 22, ['.....cCC', '....cCC.', '...cCC..', '..cCC...', '..cCC...', '.SSs....', '.SS.....']],
        'low' => [33, 22, ['CCc.....', '.CCc....', '..CCc...', '...CCc..', '...CCc..', '....sSS.', '.....SS.']],
        'raised' => [33, 16, ['....SS', '...sSS', '...CCc', '..CCc.', '..CCc.', '.CCc..', 'CCc...']],
        'raisedLeft' => [16, 16, ['SS....', 'SSs...', 'cCC...', '.cCC..', '.cCC..', '..cCC.', '...cCC']],
        'out' => [33, 22, ['CCCCCCcSS', 'ccccccsSS']],
    ];

    /** @param array<string, string> $colors recoloration du skate (k = planche, T = essieux, W = roues) */
    public static function board(array $colors = []): Layer
    {
        return (new Layer(self::BOARD, 22, 45))->recolor($colors);
    }

    public static function legs(): Layer
    {
        return new Layer(self::LEGS, 22, 33);
    }

    /** @param 'balance'|'low'|'raised'|'raisedLeft'|'out' $name */
    public static function arm(string $name): Layer
    {
        [$x, $y, $rows] = self::ARMS[$name];

        return new Layer($rows, $x, $y);
    }

    /**
     * Le bras gauche fait contrepoids ; le droit tient l'arme, bas, levé ou tendu.
     *
     * @return list<Layer>
     */
    public static function arms(RearPose $pose): array
    {
        return match ($pose) {
            RearPose::Ride => [self::arm('balance'), self::arm('low')],
            RearPose::Windup => [self::arm('balance'), self::arm('raised')],
            RearPose::Strike => [self::arm('balance'), self::arm('out')],
            RearPose::Hurt => [self::arm('raisedLeft'), self::arm('raised')],
        };
    }
}
