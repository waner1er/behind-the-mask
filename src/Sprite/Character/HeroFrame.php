<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Weapon\Weapon;

/** Dessine une image d'un héros, avec son arme fétiche ou une arme ramassée sur un ennemi. */
final readonly class HeroFrame
{
    private const WIDTH = 56;

    /** Hauteur dont le corps monte quand il est sur sa planche. */
    private const RIDE_LIFT = -3;

    public function __construct(private HeroLook $look, private ?Weapon $weapon = null)
    {
    }

    /** @return list<string> */
    public function draw(
        string $farLeg,
        string $nearLeg,
        Stance $stance,
        int $dx = 0,
        int $dy = 0,
        bool $riding = false,
        bool $twinkle = false,
    ): array {
        $lift = $riding ? self::RIDE_LIFT : 0;
        $up = fn(Layer $layer) => $layer->shift($dx, $dy + $lift);
        $back = $this->look->back($riding);

        return Compositor::compose(self::WIDTH, Skeleton::HEIGHT, [
            ...($back === null ? [] : [$riding ? $back : $up($back)]),
            Skeleton::leg($farLeg)->shift(0, $lift)->recolor(Skeleton::FAR_LEG),
            Skeleton::leg($nearLeg)->shift(0, $lift),
            $up($this->look->torso()),
            $up($this->look->head($twinkle)),
            $up($this->look->arm($stance, 'far')),
            ...array_map($up, $this->held($stance)),
            $up($this->look->arm($stance, 'near')),
        ]);
    }

    /** @return list<Layer> */
    private function held(Stance $stance): array
    {
        if ($this->weapon === null) {
            return $this->look->signature($stance);
        }
        $layer = $this->weapon->layer($stance->weaponPose(), $stance->hand()) ?? new Layer([]);

        return [$layer->recolor(HeroParts::WEAPON_MAP)];
    }
}
