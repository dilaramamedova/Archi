<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string | \UnitEnum | null $navigationGroup = 'Kataloq';
    protected static ?string $navigationLabel = 'Rəylər';
    protected static ?string $modelLabel = 'Rəy';
    protected static ?string $pluralModelLabel = 'Rəylər';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Rəy məlumatları')->schema([
                Forms\Components\Select::make('user_id')
                    ->label('İstifadəçi')
                    ->relationship('user', 'name')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\TextInput::make('reviewable_type')
                    ->label('Tip')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\TextInput::make('reviewable_id')
                    ->label('Məhsul/Usta ID')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\TextInput::make('rating')
                    ->label('Reytinq')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Textarea::make('comment')
                    ->label('Rəy mətni')
                    ->disabled()
                    ->dehydrated(false)
                    ->rows(4),
            ]),

            Section::make('İdarəetmə')->schema([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Gözləyir',
                        'approved' => 'Təsdiqlənib',
                        'rejected' => 'Rədd edilib',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('admin_reply')
                    ->label('Admin cavabı')
                    ->rows(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width(60),
                Tables\Columns\TextColumn::make('user.name')->label('İstifadəçi')->searchable(),
                Tables\Columns\TextColumn::make('reviewable_type')
                    ->label('Tip')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'App\\Models\\Product' => 'Məhsul',
                        'App\\Models\\SpecialistProfile' => 'Usta',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('reviewable_id')->label('Məhsul/Usta ID'),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Reytinq')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state) => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'approved' => 'Təsdiqlənib',
                        'pending' => 'Gözləyir',
                        'rejected' => 'Rədd edilib',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('helpful_count')->label('Faydalı')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Tarix')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Gözləyir',
                        'approved' => 'Təsdiqlənib',
                        'rejected' => 'Rədd edilib',
                    ]),
                Tables\Filters\SelectFilter::make('rating')
                    ->label('Reytinq')
                    ->options([
                        '5' => '5 ulduz',
                        '4' => '4 ulduz',
                        '3' => '3 ulduz',
                        '2' => '2 ulduz',
                        '1' => '1 ulduz',
                    ]),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Təsdiqlə')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record) => $record->status !== 'approved')
                    ->action(fn (Review $record) => $record->update(['status' => 'approved'])),
                Actions\Action::make('reject')
                    ->label('Rədd et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record) => $record->status !== 'rejected')
                    ->action(fn (Review $record) => $record->update(['status' => 'rejected'])),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
                Actions\BulkAction::make('approve_selected')
                    ->label('Seçilmişləri təsdiqlə')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['status' => 'approved'])),
                Actions\BulkAction::make('reject_selected')
                    ->label('Seçilmişləri rədd et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['status' => 'rejected'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }
}
