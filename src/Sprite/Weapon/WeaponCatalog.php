<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Weapon;

use InvalidArgumentException;
use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Grid;
use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Pose;

/** Toutes les armes des ennemis et des boss. */
final class WeaponCatalog
{
    /** Couleurs des armes, ajoutées à la palette de chaque ennemi. */
    public const COLORS = [
        'N' => '#c8955a', 'n' => '#8a5a30', // bois
        'M' => '#e4e4ec', 'm' => '#8a8a98', // métal
        'L' => '#1c1a20',                   // cuir, caoutchouc
        'j' => '#ffc62a',                   // or, bière
        'P' => '#1a1a22', 'p' => '#7fd4ff', // téléphone, bouclier
    ];

    /** Armes que le héros peut ramasser sur un ennemi mis K.O. */
    public const PICKABLE = ['bat', 'chain', 'knife', 'dumbbell'];

    public static function get(string $name): Weapon
    {
        return match ($name) {
            'bat' => new LineWeapon('N', 'n', walk: [1, 0, -4, -14], windup: [1, 0, -10, -12], strike: [2, 0, 21, -2]),
            'chain' => new LineWeapon('M', 'm', walk: [1, 2, 2, 11], windup: [0, 0, -8, -13], strike: [3, 0, 24, 3]),
            'knife' => new LineWeapon('M', 'm', walk: [1, 2, 1, 6], windup: [0, -1, -2, -6], strike: [3, 0, 9, 0]),
            'baton' => new LineWeapon('L', 'L', walk: [1, 0, 1, 10], windup: [0, 0, -6, -12], strike: [3, 0, 16, -1]),
            'cane' => new LineWeapon('j', 'n', walk: [1, 0, 3, 14], windup: [0, 0, -7, -12], strike: [3, 0, 19, -2]),
            'katana' => new LineWeapon('M', 'm', walk: [1, 1, -12, 12], windup: [0, 0, -10, -18], strike: [3, 0, 28, -2]),
            'dumbbell' => new HeldSprite(
                ['LL....LL', 'LLmMMmLL', 'LLmMMmLL', 'LL....LL'],
                walk: [-2, -1],
                windup: [-4, -11],
                strike: [1, -1],
            ),
            'bottle' => new HeldSprite(['.n.', '.N.', 'NjN', 'NNN', 'NNN'], walk: [0, -5], windup: [-3, -13], strike: null),
            'briefcase' => new HeldSprite(
                ['..LLL..', 'LLLLLLL', 'LLLjLLL', 'LLLLLLL', 'LLLLLLL'],
                walk: [-2, 2],
                windup: [-7, -8],
                strike: [2, -2],
            ),
            'mug' => new HeldSprite(
                ['MMMMM.', 'jjjjjL', 'jMjjj.L', 'jjjjjL', 'jjjjj.'],
                walk: [-1, -4],
                windup: [-5, -13],
                strike: [2, -3],
            ),
            'phone' => new HeldSprite(['PPP', 'PpP', 'PpP', 'PPP'], walk: [0, -4], windup: [0, -4], strike: null),
            default => throw new InvalidArgumentException("Arme inconnue : $name"),
        };
    }

    /**
     * Icônes des armes ramassables posées au sol, rognées au plus juste.
     *
     * @return array<string, list<string>>
     */
    public static function icons(): array
    {
        $icons = [];
        foreach (self::PICKABLE as $name) {
            $layer = self::get($name)->layer(Pose::Strike, [0, 5]);
            $icons[$name] = Grid::trimColumns(Compositor::compose(34, 10, [$layer ?? new Layer([])]));
        }

        return $icons;
    }
}
