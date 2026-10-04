<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Boss;

use Vigilante\PixelArt\Layer;
use Vigilante\Sprite\Character\EnemyLook;
use Vigilante\Sprite\Weapon\Weapon;

/** Un boss de fin de niveau : tête, torse, palette et accessoires posés sur le squelette commun. */
abstract class BossDesign
{
    /**
     * Couleurs de base de tous les boss. Elles sont prioritaires sur la palette du boss
     * (comportement d'origine : un 'S' ou 's' redéfini par un boss est ignoré).
     */
    private const SKIN = ['K' => '#0c0a10', 'S' => '#efb48c', 's' => '#bb7a56'];

    /** @return array<string, string> */
    abstract protected function palette(): array;

    abstract protected function head(): Layer;

    abstract protected function torso(): Layer;

    /** @return array<string, string> recoloration des bras partagés (voir EnemyLook) */
    protected function armColors(): array
    {
        return ['H' => 'C', 'h' => 'c'];
    }

    protected function weapon(): ?Weapon
    {
        return null;
    }

    /** @return list<Layer> */
    protected function back(): array
    {
        return [];
    }

    /** @return list<Layer> */
    protected function over(): array
    {
        return [];
    }

    /** @return list<Layer> */
    protected function front(): array
    {
        return [];
    }

    final public function look(): EnemyLook
    {
        return new EnemyLook(
            self::SKIN + $this->palette(),
            $this->head(),
            $this->torso(),
            $this->armColors(),
            $this->weapon(),
            $this->back(),
            $this->over(),
            $this->front(),
        );
    }
}
