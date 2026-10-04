<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\SpriteSheet;

/** Otage ligoté (comme les prisonniers de Metal Slug) : un pote punk à crête verte à libérer. */
final class Hostage
{
    private const PALETTE = [
        'K' => '#0c0a10', 'G' => '#7dff5a', 'g' => '#3aa02a', 'S' => '#efb48c', 's' => '#bb7a56',
        'J' => '#26262e', 'j' => '#4a4a58', 'W' => '#dfe3f0', 'r' => '#d8c08a', 'R' => '#9a7a4a',
        'A' => '#3d63d6', 'a' => '#26408f', 'O' => '#2a2430', 'X' => '#1e1a24',
    ];

    private const BODY = [
        '.....JJJJJJJ',
        '....JjrrrrrJJ',
        '....JJJJJJJJJ',
        '....JjRrrrrRJ',
        '....JJJJJJJJJ',
        '.....AAAAAAAAA',
        '.....AAAAAAAAAA',
        '..OOOAAAAAAAAAA',
        '.OOOOOaaa.OOOO',
        '.XXXXX....XXXX',
    ];

    public static function build(): SpriteSheet
    {
        $body = new Layer(self::BODY, 0, 10);

        return new SpriteSheet(self::PALETTE, [
            'tied' => [
                Compositor::compose(20, 20, [$body, new Layer(self::head(true), 0, 1)]),
                Compositor::compose(20, 20, [$body, new Layer(self::head(false), 1, 1)]),
            ],
        ]);
    }

    /** @return list<string> */
    private static function head(bool $shouting): array
    {
        return [
            '.......G.G.G',
            '......gGGGGG',
            '.......GGGG',
            '......SSSSS',
            '.....SSSSSSs',
            '.....SKSSKSs',
            '.....SSSSSSs',
            $shouting ? '......SKKKs' : '......SSKSs',
            '.......sss',
        ];
    }
}
