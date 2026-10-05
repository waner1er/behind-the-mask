<?php

declare(strict_types=1);

namespace Vigilante\Sprite\Character;

use Vigilante\PixelArt\Layer;

/** Les pièces d'un héros jouable, posées sur le squelette commun par HeroFrame. */
interface HeroLook
{
    /** @return array<array-key, string> */
    public function palette(): array;

    /** Calques derrière le corps (skate dans le dos, ou sous les pieds pendant la glisse). */
    public function back(bool $riding): ?Layer;

    /** @param bool $twinkle image « brillante » de l'attente (sourire qui scintille...) */
    public function head(bool $twinkle): Layer;

    public function torso(): Layer;

    /** @param 'near'|'far' $side */
    public function arm(Stance $stance, string $side): Layer;

    /**
     * L'arme fétiche, tenue quand le héros n'a rien ramassé.
     *
     * @return list<Layer>
     */
    public function signature(Stance $stance): array;
}
