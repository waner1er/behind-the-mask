<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

use Vigilante\PixelArt\Layer;

/** Gym bro en BMX, de dos : nuque épaisse, débardeur, bras énormes sur le guidon, il pédale. */
final class BmxBro implements RearLook
{
    private const PALETTE = [
        'K' => '#0c0a10',
        'S' => '#c8804e', 's' => '#93562e', 'E' => '#2a1a10',  // peau, cheveux ras
        'C' => '#1c1c24', 'c' => '#101016',                    // débardeur
        'A' => '#3a1a5a', 'a' => '#28103e',                    // short
        'O' => '#ececf2', 'X' => '#b8b8c8',                    // baskets
        'k' => '#1a1a1e', 'o' => '#4a4a56', 'w' => '#c8c8d4',  // pneu, chrome
    ];

    private const HEAD = [
        '...EEEEEE...',
        '..EEEEEEEE..',
        '.EEEEEEEEEE.',
        '.EEEEEEEEEE.',
        'sSEEEEEEEESs',
        'sSSEEEEEESSs',
        '.SSSSSSSSSS.',
        '..sSSSSSSs..',
        '...sSSSSs...',
        '...sSSSSs...',
    ];

    private const TORSO = [
        '.......sSSSSs.......',
        '....sSSSSSSSSSSs....',
        '..sSSSSCCCCCCSSSSs..',
        '.sSSSSCCCCCCCCSSSSs.',
        'sSSSSCCCCCCCCCCSSSSs',
        'sSSSSCCCCCCCCCCSSSSs',
        '.sSSCCCCCCCCCCCCSSs.',
        '..ssCCCCCCCCCCCCss..',
        '....CCCCCCCCCCCC....',
        '....CCCCCCCCCCCC....',
        '....CCCCCCCCCCCC....',
        '....cCCCCCCCCCCc....',
        '....AAAAAAAAAAAA....',
        '...AAAAAAAAAAAAAA...',
        '...AAAAaAAAAaAAAA...',
    ];

    private const WHEEL = ['.kk.', 'kook', 'kook', 'kook', 'kook', 'kook', 'kook', 'kook', 'kook', 'kook', 'kook', '.kk.'];

    /** Genou gauche levé (pédale haute), jambe droite tendue ; l'image 1 est son miroir. */
    private const PEDALING = [
        '.sSSs......sSSs...',
        '.sSSs......sSSs...',
        '.sSSs......sSSs...',
        'wOOOOo......sSSs..',
        '.XXXXX......sSSs..',
        '............sSSs..',
        '............sSSs..',
        '...........OOOOo..',
        '...........OOOOow.',
        '...........XXXXX..',
    ];

    private const ARMS = [
        'grip' => [13, 24, ['....sSS', '...sSSS', '..sSSS.', '..sSSS.', '.sSSS..', '.sSSS..', '.SSSs..']],
        'raised' => [35, 16, ['...SSS', '..sSSS', '..sSS.', '.sSSS.', '.sSS..', 'sSSS..', 'sSS...', 'sS....']],
        'punch' => [36, 24, ['sSSSSSSSSSSS', 'sSSSSSSSSSSS', 'ssssssssSSSS']],
    ];

    public function palette(): array
    {
        return self::PALETTE;
    }

    public function body(int $step): array
    {
        return [
            new Layer(self::WHEEL, 26, 36),
            Layer::at(14, 30, ['kkwwwwwwwwwwwwwwwwwwwwwwwwkk']),
            new Layer($step === 0 ? self::PEDALING : RearBuilder::mirror(self::PEDALING), 19, 36),
            new Layer(self::TORSO, 18, 21),
            new Layer(self::HEAD, 22, 12),
        ];
    }

    public function arms(RearPose $pose): array
    {
        $arm = fn(string $name) => new Layer(self::ARMS[$name][2], self::ARMS[$name][0], self::ARMS[$name][1]);
        $grip = $arm('grip');

        return match ($pose) {
            RearPose::Ride => [$grip, RearBuilder::opposite($grip)],
            RearPose::Windup => [$grip, $arm('raised')],
            RearPose::Strike => [$grip, $arm('punch')],
            RearPose::Hurt => [RearBuilder::opposite($arm('raised')), $arm('raised')],
        };
    }
}
