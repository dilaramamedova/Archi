<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DictionaryRequestStatus;
use App\Filament\Resources\DictionaryRequestResource\Pages;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\DictionaryRequest;
use App\Services\Laminate\DictionaryRequestResolver;
use App\Services\Laminate\LaminateCatalog;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;
use InvalidArgumentException;

/**
 * Seller proposals for values that are not in a shared dictionary yet — an unlisted
 * brand, or a "Digər" usage class / AC class / joint type on a laminate card
 * (TZ «Ламинат» §1, §8). Until a moderator approves one, it exists only here.
 */
class DictionaryRequestResource extends Resource
{
    protected static ?string $model = DictionaryRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Kataloq';

    protected static ?string $navigationLabel = 'Lüğət təklifləri';

    protected static ?string $modelLabel = 'Lüğət təklifi';

    protected static ?string $pluralModelLabel = 'Lüğət təklifləri';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $count = DictionaryRequest::query()->pending()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function fieldLabel(string $code): string
    {
        return match ($code) {
            'brand' => 'Brend',
            'usage_class' => 'Laminat · istismar sinfi',
            'abrasion' => 'Laminat · aşınma sinfi (AC)',
            'connection' => 'Laminat · birləşmə növü',
            default => $code,
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('field_code')->label('Lüğət')
                    ->formatStateUsing(fn (string $state) => static::fieldLabel($state)),
                Tables\Columns\TextColumn::make('proposed_label')->label('Təklif olunan dəyər')->weight('bold')->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label('Məhsul')->placeholder('—')
                    ->url(fn (DictionaryRequest $r) => $r->product_id ? ProductResource::getUrl('edit', ['record' => $r->product_id]) : null),
                Tables\Columns\TextColumn::make('proposer.name')->label('Təklif edən')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (DictionaryRequestStatus $state) => $state->label())
                    ->color(fn (DictionaryRequestStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('rejection_reason')->label('Rədd səbəbi')->placeholder('—')->wrap()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('Tarix')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')
                    ->options(collect(DictionaryRequestStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->default(DictionaryRequestStatus::Pending->value),
                Tables\Filters\SelectFilter::make('field_code')->label('Lüğət')
                    ->options(collect(['brand', 'usage_class', 'abrasion', 'connection'])->mapWithKeys(fn ($c) => [$c => static::fieldLabel($c)])),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Təsdiqlə')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DictionaryRequest $record) => $record->status === DictionaryRequestStatus::Pending)
                    ->modalHeading(fn (DictionaryRequest $record) => '«'.$record->proposed_label.'» — '.static::fieldLabel($record->field_code))
                    ->schema(fn (DictionaryRequest $record): array => static::approveSchema($record))
                    ->action(function (DictionaryRequest $record, array $data): void {
                        $existing = ($data['mode'] ?? 'create') === 'existing' ? (int) ($data['existing_id'] ?? 0) : null;

                        try {
                            app(DictionaryRequestResolver::class)->approve($record, auth()->id(), $existing ?: null, array_filter([
                                'az' => $data['label_az'] ?? null,
                                'ru' => $data['label_ru'] ?? null,
                                'en' => $data['label_en'] ?? null,
                            ]));
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Təsdiqləndi — eyni dəyəri təklif edən bütün kartlara tətbiq olundu')->send();
                    }),
                Actions\Action::make('reject')
                    ->label('Rədd et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (DictionaryRequest $record) => $record->status === DictionaryRequestStatus::Pending)
                    ->schema([
                        Forms\Components\Textarea::make('reason')->label('Səbəb')->required()->maxLength(500)->rows(3)
                            ->helperText('Kartdakı sahə boş qalır; satıcı siyahıdan seçə və ya yenidən təklif edə bilər.'),
                    ])
                    ->action(fn (DictionaryRequest $record, array $data) => app(DictionaryRequestResolver::class)->reject($record, auth()->id(), $data['reason'])),
            ]);
    }

    /**
     * Create the value, or map the proposal onto an existing one. Similar existing
     * values are listed first — the moderator decides, nothing merges by itself.
     */
    private static function approveSchema(DictionaryRequest $record): array
    {
        $similar = app(DictionaryRequestResolver::class)->similar($record);
        $isBrand = $record->entity_type === DictionaryRequest::ENTITY_BRAND;

        $existing = $isBrand
            ? Brand::query()->orderBy('name->az')->get()->mapWithKeys(fn (Brand $b) => [$b->id => (string) $b->getTranslation('name', 'az', false)])->all()
            : (Attribute::query()->where('slug', LaminateCatalog::OPTION_FIELDS[$record->field_code] ?? '')->with('options')->first()?->options
                ->mapWithKeys(fn ($o) => [$o->id => (string) $o->getTranslation('value', 'az', false)])->all() ?? []);

        $fields = [
            Forms\Components\Radio::make('mode')
                ->label('Nə edilsin?')
                ->options([
                    'create' => $isBrand ? 'Yeni brend yarat: '.$record->proposed_label : 'Yeni dəyər yarat: '.$record->proposed_label,
                    'existing' => 'Mövcud dəyərə bağla',
                ])
                ->default($similar ? 'existing' : 'create')
                ->required()
                ->live(),
            Forms\Components\Select::make('existing_id')
                ->label($similar ? 'Mövcud dəyər (oxşarlar: '.implode(', ', $similar).')' : 'Mövcud dəyər')
                ->options($existing)
                ->default($similar ? array_key_first($similar) : null)
                ->searchable()
                ->required(fn (Get $get) => $get('mode') === 'existing')
                ->visible(fn (Get $get) => $get('mode') === 'existing'),
        ];

        if (! $isBrand) {
            foreach (['az' => 'Ad (AZ)', 'ru' => 'Ad (RU)', 'en' => 'Ad (EN)'] as $locale => $label) {
                $fields[] = Forms\Components\TextInput::make('label_'.$locale)
                    ->label($label)
                    ->default($record->proposed_label)
                    ->maxLength(100)
                    ->visible(fn (Get $get) => $get('mode') === 'create');
            }
        }

        return $fields;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDictionaryRequests::route('/'),
        ];
    }
}
