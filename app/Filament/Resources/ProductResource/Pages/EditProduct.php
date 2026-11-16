<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\CardForm;
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

    /** Category card rows (null when the class has no card). */
    private ?array $cardRows = null;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['attribute_values'] = ProductResource::attributeValuesFormState($this->getRecord());

        return [...$data, ...CardForm::fillState($this->getRecord())];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->classifierAttributeValues = Arr::pull($data, 'attribute_values') ?? [];
        [$data, $this->cardRows] = CardForm::beforeSave($data);

        return $data;
    }

    protected function afterSave(): void
    {
        ProductResource::syncAttributeValues($this->getRecord(), $this->classifierAttributeValues);
        // After the classifier sync: a card product's values are exactly its card's.
        CardForm::afterSave($this->getRecord(), $this->cardRows);
    }
}
