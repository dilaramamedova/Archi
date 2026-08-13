<?php

namespace App\Models;

use App\Enums\FilterPriority;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SubCategoryAttribute extends Pivot
{
    protected $table = 'attribute_sub_category';

    public $incrementing = true;

    protected $casts = [
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'filter_priority' => FilterPriority::class,
    ];
}
