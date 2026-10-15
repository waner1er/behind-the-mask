<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

use Vigilante\PixelArt\Layer;
use Vigilante\PixelArt\Line;
use Vigilante\Sprite\Character\BaptParts;

/** VigiBapt de dos : tignasse, chemise à carreaux, jean, et sa guitare tenue comme une hache. */
final class BaptRear implements RearLook
{
    private const HEAD = [
        '...ZZzZZ....',
        '..ZZZZZZZz..',
        '.ZZZZZZzZZZ.',
        '.ZZZZZZZZZZ.',
        'ZZZZZzZZZZZZ',
        'sZZZZZZZZZZs',
        'sZZZZZZZZZZs',
        '.ZZZzZZZZZZ.',
        '..ZZZZZZZZ..',
        '...ZsSSsZ...',
        '....sSSs....',
        '....sSSs....',
    ];

    private const TORSO = [
        '...cCCCCCCc...',
        '.cCBCCCBCCCBc.',
        'cCCBCCCBCCCBCc',
        'BBBBBBBBBBBBBB',
        'cCCBCCCBCCCBCc',
        'cCCBCCCBCCCBCc',
        '.cCBCCCBCCCBc.',
        '.BBBBBBBBBBBB.',
        '.cCBCCCBCCCBc.',
        '.DDDDDDDDDDDD.',
        '.AAAAAAAAAAAA.',
        '.AAAAAAAAAAAA.',
    ];

    public function palette(): array
    {
        return BaptParts::PALETTE;
    }

    public function body(int $step): array
    {
        return [
            SkaterParts::board(['T' => 'y']),
            SkaterParts::legs(),
            ...RearBuilder::shift([new Layer(self::TORSO, 21, 21), new Layer(self::HEAD, 22, 9)], 0, $step),
        ];
    }

    /** La guitare : manche N/n, caisse R/r. */
    public function arms(RearPose $pose): array
    {
        $guitar = match ($pose) {
            RearPose::Ride => [Line::between(39, 28, 43, 34, 'N', 'n'), Layer::at(42, 34, ['.RR.', 'RRRR', 'RrRR', 'RRRR', '.RR.'])],
            RearPose::Windup, RearPose::Hurt => [Line::between(38, 14, 40, 7, 'N', 'n'), Layer::at(38, 1, ['.RR.', 'RRRR', 'RrRR', 'RRRR', '.RR.', '.bb.'])],
            RearPose::Strike => [Line::between(42, 22, 49, 22, 'N', 'n'), Layer::at(50, 20, ['.RRR.', 'RRrRR', 'RRRRR', 'RbRRR', '.RRR.'])],
        };

        return [...SkaterParts::arms($pose), ...$guitar];
    }
}
