<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\CardForm;
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

    /** Category card rows (null when the class has no card). */
    private ?array $cardRows = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->classifierAttributeValues = Arr::pull($data, 'attribute_values') ?? [];
        [$data, $this->cardRows] = CardForm::beforeSave($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        ProductResource::syncAttributeValues($this->getRecord(), $this->classifierAttributeValues);
        // After the classifier sync: a card product's values are exactly its card's.
        CardForm::afterSave($this->getRecord(), $this->cardRows);
    }
}
