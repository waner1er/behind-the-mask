<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

use Vigilante\PixelArt\Layer;
use Vigilante\PixelArt\Line;
use Vigilante\Sprite\Character\HeroParts;

/** Pete de dos, en position de descente : casquette, nœud du masque au vent, un V rouge sur le kimono. */
final class PeteRear implements RearLook
{
    private const HEAD = [
        '....MMMM....',
        '..MMMMMMMM..',
        '.MMMMMMMMMM.',
        '.MMMMPPMMMM.',
        '.mmmmmmmmmm.',
        '.ZZZZZZZZZZ.',
        'sGGGGGGGGGGs',
        '.ZZZZGGZZZZ.',
        '.ZZZZZZZZZZ.',
        '..ZZZZZZZZ..',
        '....sSSs....',
        '....sSSs....',
    ];

    private const TORSO = [
        '...cCCCCCCc...',
        '.cCCCCCCCCCCc.',
        'cCCCCCCCCCCCCc',
        'cCCMCCCCCCMCCc',
        'cCCMMCCCCMMCCc',
        'cCCCMMCCMMCCCc',
        '.cCCCMMMMCCCc.',
        '.cCCCCMMCCCCc.',
        '.cCCCCCCCCCCc.',
        '.DDDDDDDDDDDD.',
        '.cCCCCCCCCCCc.',
        '.ccCCCCCCCCcc.',
    ];

    /** Les rubans du masque flottent au vent. */
    private const RIBBONS = [
        ['GG..', '.GGG', '...G'],
        ['GGG.', '..GG', '....'],
    ];

    public function palette(): array
    {
        return HeroParts::PALETTE;
    }

    public function body(int $step): array
    {
        return [
            SkaterParts::board(),
            SkaterParts::legs(),
            ...RearBuilder::shift([
                new Layer(self::TORSO, 21, 21),
                new Layer(self::HEAD, 22, 9),
                Layer::at(28, 16, self::RIBBONS[$step]),
            ], 0, $step),
        ];
    }

    /** Le katana : pointe vers l'arrière en roulant, levé pour l'élan, tendu à l'horizontale pour frapper. */
    public function arms(RearPose $pose): array
    {
        $katana = match ($pose) {
            RearPose::Ride => [Layer::at(40, 28, ['YY']), Line::between(41, 29, 48, 38, 'N', 'n')],
            RearPose::Windup, RearPose::Hurt => [Layer::at(37, 14, ['YY']), Line::between(38, 12, 45, 2, 'N', 'n')],
            RearPose::Strike => [Layer::at(42, 21, ['Y', 'Y', 'Y']), Line::between(43, 22, 55, 22, 'N', 'n')],
        };

        return [...SkaterParts::arms($pose), ...$katana];
    }
}
