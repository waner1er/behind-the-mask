<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\PixelArt\Compositor;
use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Pose;

/** Dessine une image d'ennemi : empile cape, bras du fond, jambes, torse, tête, bras de devant, arme. */
final readonly class EnemyFrame
{
    private const WIDTH = 52;

    /** @var array<string, string> */
    private array $farArmColors;

    /** @var array<string, string> */
    private array $nearArmColors;

    public function __construct(private EnemyLook $look)
    {
        $colors = $look->armColors;
        $hand = $colors['S'] ?? 'S';
        $farHand = $colors['s'] ?? 's';
        $this->farArmColors = ['H' => $colors['h'], 'h' => $colors['h'], 'S' => $farHand, 's' => $farHand];
        $this->nearArmColors = ['H' => $colors['H'], 'h' => $colors['h'], 'S' => $hand, 's' => $farHand];
    }

    /**
     * @param int $dx $dy décalage du haut du corps (rebond, élan)
     * @param bool $armed false : l'arme est tombée
     * @return list<string>
     */
    public function draw(
        string $farLeg,
        string $nearLeg,
        string $nearArm,
        string $farArm,
        Pose $pose,
        int $dx = 0,
        int $dy = 0,
        bool $armed = true,
    ): array {
        $up = fn(Layer $layer) => $layer->shift($dx, $dy);
        $look = $this->look;

        $layers = array_map($up, $look->back);
        $layers[] = $up(Skeleton::arm($farArm)->shift(3))->recolor($this->farArmColors);
        $layers[] = Skeleton::leg($farLeg)->recolor(Skeleton::FAR_LEG);
        $layers[] = Skeleton::leg($nearLeg);
        $layers[] = $up($look->torso);
        $layers[] = $up($look->head);
        array_push($layers, ...array_map($up, $look->over));
        $layers[] = $up(Skeleton::arm($nearArm))->recolor($this->nearArmColors);

        $held = $armed ? $look->weapon?->layer($pose, Skeleton::HANDS[$nearArm]) : null;
        if ($held !== null) {
            $layers[] = $up($held);
        }
        array_push($layers, ...array_map($up, $look->front));

        return Compositor::compose(self::WIDTH, Skeleton::HEIGHT, $layers);
    }
}
