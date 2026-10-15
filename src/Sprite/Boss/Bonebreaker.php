<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Character\EnemyFrame;
use Vigilante\Sprite\Character\HeroParts;
use Vigilante\Sprite\Character\Skeleton;
use Vigilante\Sprite\Pose;
use Vigilante\Sprite\SpriteSheet;
use Vigilante\Sprite\Weapon\Weapon;
use Vigilante\Sprite\Weapon\WeaponCatalog;

/**
 * 07 Skate is a Drug : tête de mort sous la capuche, chaîne à la main, il ne descend jamais de sa planche.
 * Toutes ses images (sauf K.O.) sont posées sur le skate ; « skate » sert à sa charge.
 */
final class Bonebreaker extends BossDesign
{
    /** Le corps monte de 3 pixels pour laisser la place au skate sous les semelles. */
    private const LIFT = 3;

    /** Le skate : planche, tranche rouge, roues. */
    private const BOARD_COLORS = ['k' => '#1a1a1e', 'r' => '#e8203a', 'W' => '#ece8dc'];

    public static function build(): SpriteSheet
    {
        $look = (new self())->look();
        $frame = new EnemyFrame($look);
        $ride = fn(int $bob) => self::onBoard($frame->draw('back', 'forward', 'mid', 'mid', Pose::Walk, 0, $bob));

        return new SpriteSheet($look->palette + WeaponCatalog::COLORS + self::BOARD_COLORS, [
            'idle' => [$ride(1), $ride(0)],
            'walk' => [$ride(0), $ride(1), $ride(0), $ride(1)],
            'attack' => [
                self::onBoard($frame->draw('back', 'forward', 'back', 'front', Pose::Windup, -1, 1)),
                self::onBoard($frame->draw('back', 'forward', 'punch', 'back', Pose::Strike, 2, 1)),
            ],
            'skate' => [
                self::onBoard($frame->draw('back', 'forward', 'punch', 'back', Pose::Strike, 2, 2)),
                self::onBoard($frame->draw('back', 'forward', 'punch', 'back', Pose::Strike, 2, 3)),
            ],
            // au tapis, la planche a filé
            'dead' => [$frame->draw('back', 'forward', 'mid', 'mid', Pose::Walk, 0, 0, false)],
        ]);
    }

    protected function palette(): array
    {
        return [
            'B' => '#ece8dc', 'b' => '#a8a49a', 'G' => '#0c0a10',  // os, orbites
            'C' => '#2a2232', 'c' => '#18121e', 'w' => '#ece8dc',  // sweat à capuche, cordons
            'A' => '#3c5f96', 'a' => '#2a4672', 'v' => '#1c3050',  // jean
            'O' => '#e8203a', 'o' => '#9a1224', 'q' => '#5a0a14',  // baskets rouges
            'X' => '#ece8dc', 'D' => '#0c0a10',
        ];
    }

    protected function head(): Layer
    {
        return new Layer([
            '............BBBBBB',
            '..........BBBBBBBBBB',
            '.........BBBBBBBBBBBB',
            '.........BBBBBBBBBBBBB',
            '.........BBBBBBBGGGBBB',
            '.........bBBBBBBGGGBBB',
            '.........bBBBBBBBBBBGB',
            '..........bBBBBBBBBBBB',
            '..........bbBBGBGBGBG',
            '...........bBBBBBBBB',
            '............bbbbbbb',
        ], 0, 4);
    }

    /** Capuche relevée derrière le crâne. */
    protected function back(): array
    {
        return [Layer::at(8, 3, [
            '....CCCCCC',
            '..CCCCCCCCC',
            '.CCCCCCCC',
            'CCCCCCCC',
            'CCCCCCC',
            'cCCCCCC',
            'cCCCCCC',
            'cCCCCCC',
            '.cCCCCC',
            '..cCCCC',
            '...cCCC',
            '....cCC',
        ])];
    }

    protected function torso(): Layer
    {
        return new Layer([
            '..............bbbb',
            '............cCCCCCCCCCc',
            '............cCCCCCwCwCc',
            '............cCCCCCwCwCc',
            '............cCCCCCCCwCc',
            '............cCCBCBCCCCc',
            '............cCCCBCCCCCc',
            '............cCCBCBCCCCc',
            '............cCCCCCCCCCc',
            '............ccCCCCCCCcc',
            '.............DDDDDDDDD',
            '.............AAvAAAvAa',
        ], 0, 16);
    }

    /** Mains de squelette. */
    protected function armColors(): array
    {
        return ['H' => 'C', 'h' => 'c', 'S' => 'B', 's' => 'b'];
    }

    protected function weapon(): Weapon
    {
        return WeaponCatalog::get('chain');
    }

    /**
     * Pose l'image sur le skate : le corps monte, la planche passe sous les semelles.
     *
     * @param list<string> $grid image composée (contour compris)
     * @return list<string>
     */
    private static function onBoard(array $grid): array
    {
        $width = strlen($grid[0]);
        $lifted = [...array_slice($grid, self::LIFT), ...array_fill(0, self::LIFT, str_repeat('.', $width))];
        $board = Compositor::compose($width - 2, count($grid) - 2, [new Layer(HeroParts::BOARD_UNDER, 0, Skeleton::HEIGHT - 4)]);

        foreach ($lifted as $y => $row) {
            for ($x = 0; $x < $width; $x++) {
                if ($row[$x] !== '.') {
                    $board[$y][$x] = $row[$x];
                }
            }
        }

        return $board;
    }
}
