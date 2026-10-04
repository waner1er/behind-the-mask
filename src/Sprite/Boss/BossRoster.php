<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\Sprite\Character\EnemyBuilder;
use Vigilante\Sprite\SpriteSheet;

/** Un boss par morceau de l'album, indexé par l'identifiant de sprite utilisé dans config/levels.php. */
final class BossRoster
{
    /** @return array<string, SpriteSheet> */
    public static function sheets(): array
    {
        $designs = [
            'manager' => new Manager(),
            'general' => new General(),
            'priest' => new Priest(),
            'champion' => new Champion(),
            'troll' => new Troll(),
            'cop' => new Cop(),
            'dealer' => new Dealer(),
            'mask' => new Mask(),
        ];
        $sheets = array_map(fn(BossDesign $design) => EnemyBuilder::build($design->look()), $designs);

        return ['nightmare' => Nightmare::build()] + $sheets;
    }
}
