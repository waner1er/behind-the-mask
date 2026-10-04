<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\Sprite\Weapon\WeaponCatalog;

/**
 * Les ennemis des vagues (déclinaisons de couleurs et d'armes des deux gangs),
 * et les gros durs du Wall of Death : des potes hardcore qui chargent avec Pete.
 */
final class EnemyRoster
{
    /** @return array<string, EnemyLook> */
    public static function enemies(): array
    {
        return [
            'skinhead' => Skinheads::look(),
            'masculinist' => Masculinists::look(),
            'batter' => Skinheads::look(['C' => '#2a2a32', 'c' => '#18181e'], WeaponCatalog::get('bat')),
            'chainer' => Skinheads::look(
                ['C' => '#5a1a24', 'c' => '#3a0e16', 'A' => '#2a2a32', 'a' => '#1a1a20', 'v' => '#101014'],
                WeaponCatalog::get('chain'),
            ),
            'hooligan' => Skinheads::look(
                ['C' => '#2e4a9a', 'c' => '#1e3270', 'R' => '#f4f4f4'],
                WeaponCatalog::get('bottle'),
                [Skinheads::beanie()],
            ),
            'knifer' => Masculinists::look(
                ['C' => '#ececf2', 'c' => '#a8a8b8', 'Q' => '#b0202a', 'I' => '#e04050'],
                WeaponCatalog::get('knife'),
            ),
            'gymbro' => Masculinists::look(
                ['S' => '#c8804e', 's' => '#93562e', 'C' => '#1c1c24', 'c' => '#101016', 'A' => '#3a1a5a', 'a' => '#28103e'],
                WeaponCatalog::get('dumbbell'),
            ),
        ];
    }

    /** @return array<string, EnemyLook> */
    public static function toughGuys(): array
    {
        return [
            'tough1' => Skinheads::look([
                'C' => '#1c1a20', 'c' => '#101014', 'R' => '#1c1a20', 'z' => '#e8203a', 'D' => '#101014',
                'A' => '#5a5a3a', 'a' => '#3e3e28', 'v' => '#2a2a1a', 'U' => '#101014',
                'O' => '#ececf2', 'o' => '#b8b8c8', 'q' => '#8a8a94', 'X' => '#f4f4f4',
            ]),
            'tough2' => Masculinists::look([
                'C' => '#c0203a', 'c' => '#801424', 'Q' => '#101014', 'A' => '#26262e', 'a' => '#18181e', 'v' => '#101014',
            ]),
            'tough3' => Skinheads::look(
                ['C' => '#2e6a3a', 'c' => '#1e4a28', 'R' => '#ffd23f', 'A' => '#2a2a32', 'a' => '#1a1a20', 'v' => '#101014'],
                over: [Skinheads::beanie()],
            ),
        ];
    }
}
