<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ConsultationRequestStatus;
use App\Filament\Concerns\CachesNavigationBadge;
use App\Filament\Resources\ConsultationRequestResource\Pages;
use App\Models\ConsultationRequest;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class ConsultationRequestResource extends Resource
{
    use CachesNavigationBadge;

    protected static ?string $model = ConsultationRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Müraciətlər';

    protected static ?string $navigationLabel = 'Müraciətlər';

    protected static ?string $modelLabel = 'Müraciət';

    protected static ?string $pluralModelLabel = 'Müraciətlər';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Əlaqə məlumatları')->schema([
                Forms\Components\TextInput::make('full_name')->label('Ad və soyad')->required()->maxLength(120),
                Forms\Components\TextInput::make('phone')->label('Telefon')->tel()->required()->maxLength(25),
                Forms\Components\Textarea::make('message')->label('Mesaj')->rows(5)->maxLength(2000)->columnSpanFull(),
            ])->columns(2),
            Section::make('İdarəetmə')->schema([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(self::statusOptions())
                    ->default(ConsultationRequestStatus::Pending->value)
                    ->required(),
                Forms\Components\DateTimePicker::make('contacted_at')->label('Əlaqə tarixi')->seconds(false),
                Forms\Components\Textarea::make('admin_note')->label('Admin qeydi')->rows(4)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('full_name')->label('Ad və soyad')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefon')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('message')->label('Mesaj')->limit(60)->wrap()->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')->badge()
                    ->formatStateUsing(fn (ConsultationRequestStatus $state): string => $state->label())
                    ->color(fn (ConsultationRequestStatus $state): string => match ($state) {
                        ConsultationRequestStatus::Pending => 'warning',
                        ConsultationRequestStatus::Contacted => 'info',
                        ConsultationRequestStatus::Completed => 'success',
                        ConsultationRequestStatus::Rejected => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Göndərilmə tarixi')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::statusOptions()),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultationRequests::route('/'),
            'create' => Pages\CreateConsultationRequest::route('/create'),
            'edit' => Pages\EditConsultationRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::cachedBadge(
            'consultation-requests:pending',
            fn () => ConsultationRequest::query()->where('status', ConsultationRequestStatus::Pending)->count(),
        );
    }

    public static function statusOptions(): array
    {
        return collect(ConsultationRequestStatus::cases())
            ->mapWithKeys(fn (ConsultationRequestStatus $status): array => [$status->value => $status->label()])
            ->all();
    }
}
