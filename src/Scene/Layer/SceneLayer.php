<?php

declare(strict_types=1);

namespace Vigilante\Scene\Layer;

/** Un plan du décor, rendu en SVG sur la largeur d'un écran. */
interface SceneLayer
{
    public function render(): string;
}
