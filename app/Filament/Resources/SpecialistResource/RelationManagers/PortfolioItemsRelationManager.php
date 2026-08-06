<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpecialistResource\RelationManagers;

use App\Enums\PortfolioStatus;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class PortfolioItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'portfolioItems';

    protected static ?string $title = 'Portfolio';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function getRelationship(): Relation|Builder
    {
        $profile = $this->getOwnerRecord()->specialistProfile()->firstOrCreate();

        return $profile->portfolioItems();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('title')->label('Başlıq')->maxLength(255),
            Forms\Components\FileUpload::make('image_path')
                ->label('Şəkil')->image()->disk('public')->directory('specialists/portfolio')->required(),
            Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->minValue(0)->default(0),
            Forms\Components\Toggle::make('is_cover')->label('Üz qabığı'),
            Forms\Components\Select::make('status')->label('Status')->options([
                PortfolioStatus::Pending->value => PortfolioStatus::Pending->label(),
                PortfolioStatus::Approved->value => PortfolioStatus::Approved->label(),
                PortfolioStatus::Rejected->value => PortfolioStatus::Rejected->label(),
            ])->default(PortfolioStatus::Pending->value)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')->label('Şəkil')->disk('public'),
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->placeholder('Başlıqsız'),
                Tables\Columns\IconColumn::make('is_cover')->label('Üz qabığı')->boolean(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (PortfolioStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([Actions\CreateAction::make()])
            ->recordActions([Actions\EditAction::make(), Actions\DeleteAction::make()]);
    }
}
