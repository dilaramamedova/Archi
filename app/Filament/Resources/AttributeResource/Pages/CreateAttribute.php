<?php

namespace App\Filament\Resources\AttributeResource\Pages;

use App\Filament\Resources\AttributeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateAttribute extends CreateRecord
{
    protected static string $resource = AttributeResource::class;

    /**
     * Repeater rows for the attribute's dropdown/multiselect options — pulled out
     * of the form data before create (they are not Attribute columns) and written
     * as AttributeOption records once the attribute has an id.
     *
     * @var array<int|string, array<string, mixed>>
     */
    private array $optionRows = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->optionRows = Arr::pull($data, 'options') ?? [];

        return $data;
    }

    protected function afterCreate(): void
    {
        AttributeResource::createOptions($this->getRecord(), $this->optionRows);
    }
}
