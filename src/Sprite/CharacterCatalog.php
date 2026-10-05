<?php

declare(strict_types=1);

namespace Vigilante\Sprite;

use Vigilante\Sprite\Boss\BossRoster;
use Vigilante\Sprite\Character\BaptParts;
use Vigilante\Sprite\Character\EnemyBuilder;
use Vigilante\Sprite\Character\EnemyRoster;
use Vigilante\Sprite\Character\HeroBuilder;
use Vigilante\Sprite\Character\Hostage;
use Vigilante\Sprite\Character\Skeleton;
use Vigilante\Sprite\Weapon\WeaponCatalog;

/**
 * Tous les sprites animés envoyés au JavaScript (clé « sprites » des données du jeu).
 *
 * Clés spéciales : « anchor » (point d'ancrage des personnages) et « weaponIcons »
 * (armes posées au sol). Les héros sont « hero » (Pete) et « bapt » (VigiBapt, joueur 2) ;
 * armés d'une arme ramassée, « hero-<arme> » et « bapt-<arme> ».
 */
final class CharacterCatalog
{
    /** @return array<string, mixed> */
    public static function all(): array
    {
        $characters = [
            'anchor' => ['x' => Skeleton::ANCHOR_X, 'y' => Skeleton::ANCHOR_Y],
            'weaponIcons' => [
                'palette' => WeaponCatalog::COLORS + ['K' => '#0c0a10'],
                'sprites' => WeaponCatalog::icons(),
            ],
            'hero' => HeroBuilder::build(),
            'bapt' => HeroBuilder::build(look: new BaptParts()),
            'pow' => Hostage::build(),
        ];

        $looks = EnemyRoster::enemies() + EnemyRoster::toughGuys();
        foreach ($looks as $name => $look) {
            $characters[$name] = EnemyBuilder::build($look);
        }

        foreach (WeaponCatalog::PICKABLE as $weapon) {
            $characters["hero-$weapon"] = HeroBuilder::build(WeaponCatalog::get($weapon));
            $characters["bapt-$weapon"] = HeroBuilder::build(WeaponCatalog::get($weapon), new BaptParts());
        }

        return $characters + BossRoster::sheets();
    }
}
