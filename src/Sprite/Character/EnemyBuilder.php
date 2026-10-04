<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\Sprite\Pose;
use Vigilante\Sprite\SpriteSheet;
use Vigilante\Sprite\Weapon\WeaponCatalog;

/** Construit toutes les animations d'un ennemi à partir de son apparence. */
final class EnemyBuilder
{
    public static function build(EnemyLook $look): SpriteSheet
    {
        $frame = new EnemyFrame($look);
        $walk = [];
        foreach (Skeleton::WALK_CYCLE as $step) {
            $walk[] = $frame->draw($step['far'], $step['near'], $step['nearArm'], $step['farArm'], Pose::Walk, 0, $step['bob']);
        }

        return new SpriteSheet($look->palette + WeaponCatalog::COLORS, [
            'idle' => [
                $frame->draw('back', 'forward', 'mid', 'mid', Pose::Walk, 0, 1),
                $frame->draw('back', 'forward', 'mid', 'mid', Pose::Walk),
            ],
            'walk' => $walk,
            'attack' => [
                $frame->draw('back', 'forward', 'back', 'front', Pose::Windup, -1, 1),
                $frame->draw('back', 'forward', 'punch', 'back', Pose::Strike, 2, 1),
            ],
            // au tapis : l'arme a été lâchée
            'dead' => [$frame->draw('back', 'forward', 'mid', 'mid', Pose::Walk, 0, 0, false)],
        ]);
    }
}
