<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\PixelArt\Layer;

/**
 * Squelette commun à tous les personnages : jambes, bras et cycle de marche.
 *
 * Les lettres des calques partagés sont « génériques » (A = pantalon, O = chaussures,
 * H = manche, S = main...) : chaque personnage les recolore avec sa palette.
 * Repère : le personnage regarde à droite, pieds centrés en x = 18.
 */
final class Skeleton
{
    public const HEIGHT = 40;

    /** Point d'ancrage (centre des pieds, sous les semelles), marge du contour comprise. */
    public const ANCHOR_X = 19;
    public const ANCHOR_Y = 40;

    /** Jambe du fond : un ton plus sombre. */
    public const FAR_LEG = ['A' => 'a', 'a' => 'v', 'O' => 'q', 'o' => 'O'];

    /** @var list<array{far: string, near: string, nearArm: string, farArm: string, bob: int}> */
    public const WALK_CYCLE = [
        ['far' => 'back', 'near' => 'forward', 'nearArm' => 'back', 'farArm' => 'front', 'bob' => 1],
        ['far' => 'lifted', 'near' => 'support', 'nearArm' => 'mid', 'farArm' => 'mid', 'bob' => 0],
        ['far' => 'forward', 'near' => 'back', 'nearArm' => 'front', 'farArm' => 'back', 'bob' => 1],
        ['far' => 'support', 'near' => 'lifted', 'nearArm' => 'mid', 'farArm' => 'mid', 'bob' => 0],
    ];

    /** Position de la main du bras de devant, pour chaque pose de bras. */
    public const HANDS = ['back' => [13, 23], 'mid' => [16, 23], 'front' => [19, 23], 'punch' => [25, 19]];

    private const LEGS = [
        'forward' => [28, [
            '................AAAa',
            '................AAAa',
            '.................AAAa',
            '.................AAAa',
            '..................AAAa',
            '..................AAAa',
            '...................AAAa',
            '...................OOOo',
            '...................OOOOOo',
            '...................OOOOOOo',
            '...................XXXXXXX',
        ]],
        'back' => [28, [
            '..............AAAa',
            '..............AAAa',
            '.............AAAa',
            '.............AAAa',
            '............AAAa',
            '...........AAAa',
            '..........AAAa',
            '.........OOOo',
            '.........OOOOOo',
            '.........OOOOOOo',
            '.........XXXXXXX',
        ]],
        'support' => [28, [
            '...............AAAa',
            '...............AAAa',
            '...............AAAa',
            '...............AAAa',
            '...............AAAa',
            '...............AAAa',
            '...............AAAa',
            '...............OOOo',
            '...............OOOOOo',
            '...............OOOOOOo',
            '...............XXXXXXX',
        ]],
        'lifted' => [28, [
            '...............AAAa',
            '................AAAa',
            '................AAAa',
            '................AAAa',
            '...............AAAa',
            '..............AAAa',
            '.............OOOo',
            '.............OOOOOo',
            '.............XXXXXX',
        ]],
        // coup de pied sauté du héros : jambe tendue, jambes repliées
        'kick' => [25, [
            '...........................OOO',
            '.................AAAAAAAAAAOOOX',
            '.................AAAAAAAAAAOOOX',
            '.................aaaaaaaaaaOOOX',
            '...........................OOO',
        ]],
        'tuck' => [27, [
            '..............AAA',
            '.............AAAa',
            '............AAAa',
            '..........AAAAAa',
            '.........OOOo',
            '........OOOOo',
            '........XXXX',
        ]],
        'tuckFront' => [27, [
            '...............AAAa',
            '................AAAa',
            '.................AAAa',
            '...............AAAAa',
            '..............OOOOo',
            '.............OOOOOo',
            '.............XXXXXX',
        ]],
    ];

    /** Bras des ennemis : H = manche, h = ombre, S = main. */
    private const ARMS = [
        'back' => [18, [
            '................hHH',
            '...............hHH',
            '...............hHH',
            '..............hHH',
            '..............hHH',
            '.............SSs',
            '.............SSs',
        ]],
        'mid' => [18, [
            '................hHH',
            '................hHH',
            '................hHH',
            '................hHH',
            '................hHH',
            '................SSs',
            '................SSs',
        ]],
        'front' => [18, [
            '................hHH',
            '.................hHH',
            '.................hHH',
            '..................hHH',
            '..................hHH',
            '...................SSs',
            '...................SSs',
        ]],
        'punch' => [18, [
            '................hHH',
            '.................HHHHHHHHSSS',
            '.................hhhhhhhhSSs',
        ]],
    ];

    public static function leg(string $name): Layer
    {
        [$y, $rows] = self::LEGS[$name];

        return new Layer($rows, 0, $y);
    }

    public static function arm(string $name): Layer
    {
        [$y, $rows] = self::ARMS[$name];

        return new Layer($rows, 0, $y);
    }
}
