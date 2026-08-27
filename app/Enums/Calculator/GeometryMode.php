<?php

namespace App\Enums\Calculator;

/** Master spec 6.1 - automatic geometry is the main path, manual is the fallback. */
enum GeometryMode: string
{
    case Standard = 'STANDARD';
    case Manual = 'MANUAL_GEOMETRY';
}
