<?php

namespace App\Enums\Calculator;

enum LineType: string
{
    case Work = 'WORK';
    case Material = 'MATERIAL';
    case Logistics = 'LOGISTICS';
    case Service = 'SERVICE';
    case Contingency = 'CONTINGENCY';
}
