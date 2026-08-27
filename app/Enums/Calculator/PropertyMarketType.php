<?php

namespace App\Enums\Calculator;

/** Yeni tikili / ikinci el - seeds the condition and demolition defaults only. */
enum PropertyMarketType: string
{
    case NewBuild = 'NEW_BUILD';
    case Secondary = 'SECONDARY';

    public function defaultCondition(): ObjectCondition
    {
        return $this === self::NewBuild ? ObjectCondition::Shell : ObjectCondition::OldRenovation;
    }
}
