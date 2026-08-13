<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Classifier attribute values pulled out of the form data before create —
     * they are not product columns and get persisted separately in afterCreate().
     */
    private array $classifierAttributeValues = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->classifierAttributeValues = Arr::pull($data, 'attribute_values') ?? [];

        return $data;
    }

    protected function afterCreate(): void
    {
        ProductResource::syncAttributeValues($this->getRecord(), $this->classifierAttributeValues);
    }
}
