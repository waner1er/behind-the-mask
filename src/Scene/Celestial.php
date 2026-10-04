<?php

declare(strict_types=1);

namespace Vigilante\Scene;

enum Celestial: string
{
    case Sun = 'sun';
    case Moon = 'moon';
    case None = 'none';
}
