<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Classifier attribute values pulled out of the form data before save —
     * they are not product columns and get persisted separately in afterSave().
     */
    private array $classifierAttributeValues = [];

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['attribute_values'] = ProductResource::attributeValuesFormState($this->getRecord());

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->classifierAttributeValues = Arr::pull($data, 'attribute_values') ?? [];

        return $data;
    }

    protected function afterSave(): void
    {
        ProductResource::syncAttributeValues($this->getRecord(), $this->classifierAttributeValues);
    }
}
