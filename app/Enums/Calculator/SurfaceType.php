<?php

namespace App\Enums\Calculator;

enum SurfaceType: string
{
    case Floor = 'FLOOR';
    case Wall = 'WALL';
    case Ceiling = 'CEILING';
}
