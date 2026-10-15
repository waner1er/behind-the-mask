<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Race;

use Vigilante\PixelArt\Layer;

/**
 * Un pilote de la course vu de dos (héros en skate, ennemis en rollers, BMX ou moto).
 * Repère : image de RearBuilder::WIDTH × RearBuilder::HEIGHT, centrée en x = 28,
 * roues posées sur la dernière ligne. Les coups partent vers la droite (le jeu retourne l'image).
 */
interface RearLook
{
    /** @return array<array-key, string> */
    public function palette(): array;

    /**
     * Véhicule, jambes, torse et tête, du fond vers l'avant.
     *
     * @param int $step image de l'animation (0 ou 1) : jambes qui poussent, gyrophare, ruban du masque...
     * @return list<Layer>
     */
    public function body(int $step): array;

    /** @return list<Layer> bras et arme */
    public function arms(RearPose $pose): array;
}
