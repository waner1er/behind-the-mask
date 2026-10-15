<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

use Vigilante\PixelArt\Layer;
use Vigilante\PixelArt\Line;

/** Flic à moto, de dos : casque blanc, sacoches, feu arrière et gyrophares qui alternent ; il frappe à la matraque. */
final class MotoCop implements RearLook
{
    private const PALETTE = [
        'K' => '#0c0a10',
        'W' => '#f0f0f4', 'w' => '#b8b8c8', 'b' => '#2a4a9a',  // casque, sacoches
        'S' => '#efb48c', 's' => '#bb7a56',
        'C' => '#24345a', 'c' => '#16223e', 'D' => '#0c0c10', 'Y' => '#ffd23f',  // uniforme, gants, insigne
        'R' => '#e8203a', 'r' => '#7a1018', 'p' => '#ece8dc',  // feu arrière, plaque
        'L' => '#ff3040', 'q' => '#5a1018',                    // gyrophare rouge (allumé, éteint)
        'l' => '#40a0ff', 'B' => '#1a2a6a',                    // gyrophare bleu
        'k' => '#1a1a1e', 'o' => '#3a3a44', 'n' => '#8a8a9a',  // pneu, chrome
    ];

    private const HELMET = [
        '...WWWWWW...',
        '..WWWWWWWW..',
        '.WWWWbbWWWW.',
        '.WWWWbbWWWW.',
        'WWWWWbbWWWWW',
        'wWWWWbbWWWWw',
        'wwwwwwwwwwww',
        '.sSSSSSSSSs.',
        '...sSSSSs...',
        '...sSSSSs...',
    ];

    private const TORSO = [
        '..cCCCCCCCCCCc..',
        '.cCCCCCCCCCCCCc.',
        'cCCCCCCCCCCCCCCc',
        'cCCCCCCCCCCCCCCc',
        'cCCCCCCCCCCCCCCc',
        'cCCCCCCCCCCCCCCc',
        '.cCCCCCCCCCCCCc.',
        '.cCCCCCCCCCCCCc.',
        '.cCCCCCCCCCCCCc.',
        '.DDDDDDYDDDDDDD.',
        '.cCCCCCCCCCCCCc.',
    ];

    private const PANNIER = ['wWWWWWWw', 'WWWWWWWW', 'bbbbbbbb', 'WWWWWWWW', 'WWWWWWWW', 'WWWWWWWW', 'wWWWWWWw', '.wwwwww.'];

    private const ARMS = [
        'bar' => [13, 22, ['...cCC', '..cCC.', '.cCC..', '.cCC..', 'cCC...', 'DDD...']],
        'raised' => [37, 15, ['...DD', '..cDD', '..cCC', '.cCC.', '.cCC.', 'cCC..', 'cC...']],
        'out' => [37, 22, ['CCCCCCcDD', 'ccccccDDD']],
    ];

    public function palette(): array
    {
        return self::PALETTE;
    }

    public function body(int $step): array
    {
        $pannier = new Layer(self::PANNIER, 12, 31);
        // un gyrophare s'allume, l'autre s'éteint
        $lights = $step === 0 ? ['LL', 'BB'] : ['qq', 'll'];

        return [
            Layer::at(25, 37, ['kkkkkk', 'kooook', 'kooook', 'kooook', 'kooook', 'kooook', 'kooook', 'kooook', 'kooook', '.kkkk.']),
            Layer::at(33, 42, ['nnnnnnn', 'ooooooo']),
            $pannier,
            RearBuilder::opposite($pannier),
            Layer::at(14, 28, [$lights[0], $lights[0]]),
            Layer::at(40, 28, [$lights[1], $lights[1]]),
            new Layer(self::TORSO, 20, 20),
            Layer::at(23, 30, ['RRRRRRRRRR', 'rRRRRRRRRr']),
            Layer::at(25, 33, ['pppppp', 'pkpkpp', 'pppppp']),
            new Layer(self::HELMET, 22, 10),
        ];
    }

    /** Matraque noire (k), tenue à bout de bras. */
    public function arms(RearPose $pose): array
    {
        $arm = fn(string $name) => new Layer(self::ARMS[$name][2], self::ARMS[$name][0], self::ARMS[$name][1]);
        $bar = $arm('bar');

        return match ($pose) {
            RearPose::Ride => [$bar, RearBuilder::opposite($bar)],
            RearPose::Windup => [$bar, $arm('raised'), Line::between(41, 14, 45, 4, 'k', 'o')],
            RearPose::Strike => [$bar, $arm('out'), Line::between(46, 22, 55, 22, 'k', 'o')],
            RearPose::Hurt => [RearBuilder::opposite($arm('raised')), $arm('raised')],
        };
    }
}
