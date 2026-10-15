<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

use Vigilante\PixelArt\Layer;

/** Skinhead en rollers, de dos : crâne rasé, bomber, jean à bretelles ; une jambe pousse à chaque image. */
final class RollerSkin implements RearLook
{
    private const PALETTE = [
        'K' => '#0c0a10',
        'S' => '#e9b08a', 's' => '#b9785a', 'r' => '#c0303a',  // peau, cicatrice
        'C' => '#4f6b38', 'c' => '#33482a',                    // bomber
        'A' => '#7a98c2', 'a' => '#53719c', 'U' => '#b0202a',  // jean, bretelles
        'O' => '#6a1a24', 'o' => '#9a2a36',                    // rollers bordeaux
        'z' => '#ffd23f', 'Z' => '#c08a1a',                    // roues
    ];

    private const HEAD = [
        '...ssSSss...',
        '..sSSSSSSs..',
        '.sSSSSSSSSs.',
        '.SSSSSSSSSS.',
        'sSSSSSSSSSSs',
        '.SSSSrSSSSS.',
        '.sSSSSrSSSs.',
        '..sSSSSSSs..',
        '....sSSs....',
        '....sSSs....',
    ];

    private const TORSO = [
        '...cCCCCCCc...',
        '.cCCCCCCCCCCc.',
        'cCCCCCCCCCCCCc',
        'cCCCCCCCCCCCCc',
        'cCCCCCCCCCCCCc',
        'cCCCCCCCCCCCCc',
        'cCCCCCCCCCCCCc',
        '.cCCCCCCCCCCc.',
        '.cCCCCCCCCCCc.',
        '.cccccccccccc.',
        '..AUAAAAAAUA..',
        '..AAAAAAAAAA..',
    ];

    /** La jambe gauche pousse sur le côté, roulette en l'air ; l'image 1 est son miroir. */
    private const STRIDE = [
        '...AAAAAAAAAA...',
        '..AAAAAaAAAAA...',
        '.AAAAa...AAAA...',
        'AAAa.....aAAA...',
        'AAa......aAAA...',
        'OOO......aAAA...',
        'OOOo.....aAAA...',
        'zZzZ.....OOOO...',
        '.........OOOO...',
        '.........OOOOo..',
        '.........OOOOo..',
        '.........zZzZ...',
    ];

    public function palette(): array
    {
        return self::PALETTE;
    }

    public function body(int $step): array
    {
        return [
            new Layer($step === 0 ? self::STRIDE : RearBuilder::mirror(self::STRIDE), 20, 36),
            new Layer(self::TORSO, 21, 24),
            new Layer(self::HEAD, 22, 14),
        ];
    }

    /** Les poings, avec les bras du skate (manche de bomber). */
    public function arms(RearPose $pose): array
    {
        return RearBuilder::shift(SkaterParts::arms($pose), 0, 3);
    }
}
