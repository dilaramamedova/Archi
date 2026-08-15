<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\CachesNavigationBadge;
use App\Filament\Resources\NewsletterSubscriberResource\Pages;
use App\Models\NewsletterSubscriber;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class NewsletterSubscriberResource extends Resource
{
    use CachesNavigationBadge;

    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Müraciətlər';

    protected static ?string $navigationLabel = 'Abunəçilər';

    protected static ?string $modelLabel = 'Abunəçi';

    protected static ?string $pluralModelLabel = 'Abunəçilər';

    protected static ?int $navigationSort = 2;

    /** Active subscribers, so the sidebar shows the size of the mailing list. */
    public static function getNavigationBadge(): ?string
    {
        return self::cachedBadge(
            'newsletter:active',
            fn () => self::getModel()::query()->where('is_active', true)->count(),
        );
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Forms\Components\TextInput::make('email')
                    ->label('E-poçt')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Bu e-poçt artıq siyahıdadır.']),

                Forms\Components\Toggle::make('is_active')
                    ->label('Abunədir')
                    ->default(true)
                    ->helperText('Söndürsəniz, abunəçi siyahıdan çıxarılmış sayılır.'),

                Forms\Components\DateTimePicker::make('subscribed_at')
                    ->label('Abunə tarixi')
                    ->seconds(false)
                    ->default(now()),

                Forms\Components\DateTimePicker::make('unsubscribed_at')
                    ->label('İmtina tarixi')
                    ->seconds(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->width(60),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-poçt')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('E-poçt kopyalandı'),
                Tables\Columns\IconColumn::make('is_active')->label('Abunədir')->boolean(),
                Tables\Columns\TextColumn::make('subscribed_at')
                    ->label('Abunə tarixi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('unsubscribed_at')
                    ->label('İmtina tarixi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('subscribed_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Abunədir'),
            ])
            ->headerActions([
                // The whole point of the module: get the addresses out to a mail tool.
                Actions\Action::make('copyActive')
                    ->label('Aktiv e-poçtları kopyala')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->action(function (): void {
                        $emails = NewsletterSubscriber::query()
                            ->where('is_active', true)
                            ->orderBy('email')
                            ->pluck('email')
                            ->implode(', ');

                        Notification::make()
                            ->title($emails === '' ? 'Aktiv abunəçi yoxdur' : 'Aktiv abunəçilər')
                            ->body($emails === '' ? null : $emails)
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkAction::make('deactivate')
                    ->label('Abunəni dayandır')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update([
                        'is_active' => false,
                        'unsubscribed_at' => now(),
                    ])),
                Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscribers::route('/'),
            'create' => Pages\CreateNewsletterSubscriber::route('/create'),
            'edit' => Pages\EditNewsletterSubscriber::route('/{record}/edit'),
        ];
    }
}
