<?php

namespace App\Filament\Resources;

use App\Enums\AttributeFieldType;
use App\Enums\UserRole;
use App\Filament\Concerns\TranslatesAdminMessages;
use App\Filament\Resources\ProductResource\CardForm;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Support\TranslatableSearch;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\Laminate\LaminateCatalog;
use App\Services\Laminate\LaminateModeration;
use App\Services\SearchService;
use App\Support\ClassifierFields;
use Closure;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    use TranslatesAdminMessages;

    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Kataloq';

    protected static ?string $navigationLabel = 'Məhsullar';

    protected static ?string $modelLabel = 'Məhsul';

    protected static ?string $pluralModelLabel = 'Məhsullar';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Tabs::make('Tabs')->tabs([
                static::tab('Əsas')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Ad')
                        // Required in Azerbaijani only; ru/en are optional and the site
                        // falls back to az — sellers enter a single name as well.
                        ->translatable(modifyLocalizedFieldUsing: fn (Forms\Components\TextInput $field, string $locale) => $field->required($locale === 'az'))
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, $record) {
                            if (! $record) {
                                $value = $state;
                                while (is_array($value)) {
                                    $value = $value['az'] ?? reset($value) ?: '';
                                }
                                $set('slug', Str::slug((string) $value));
                            }
                        }),

                    Forms\Components\RichEditor::make('description')
                        ->label('Təsvir')
                        ->translatable(),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('user_id')
                        ->label('Satıcı')
                        // Sellers, and admins for in-house products — not buyers or
                        // specialists. A product already owned by someone else keeps
                        // its owner selectable so the edit form still saves.
                        ->relationship(
                            'user',
                            'name',
                            modifyQueryUsing: fn (Builder $query, ?Product $record): Builder => $query
                                ->where(fn (Builder $q) => $q
                                    ->whereIn('users.role', [UserRole::Seller, UserRole::Admin])
                                    ->when($record?->user_id, fn (Builder $q, $id) => $q->orWhere('users.id', $id)))
                                ->orderBy('users.name'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->name.match ($record->role) {
                            UserRole::Seller => '',
                            UserRole::Admin => ' (admin)',
                            default => $record->role ? ' ('.$record->role->label().')' : '',
                        })
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('category_id')
                        ->label('Kateqoriya (qrup)')
                        // Only active groups under an active section, labelled with
                        // their path («Tavanlar › Tavan növləri»). Sections and the old
                        // «Passiv» groups are not places a product goes; a product
                        // already in one keeps it on edit.
                        ->relationship(
                            'category',
                            'name',
                            modifyQueryUsing: fn (Builder $query, ?Product $record): Builder => static::activeGroups($query, $record?->category_id),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Category $record): string => static::groupLabel($record))
                        ->searchable()
                        // Case-insensitive over the group's and its section's name
                        // (the default LIKE on the JSON column is binary).
                        ->getSearchResultsUsing(fn (string $search, ?Product $record): array => static::activeGroups(Category::query(), $record?->category_id)
                            ->where(fn (Builder $q) => TranslatableSearch::apply($q, $q->qualifyColumn('name'), $search)
                                ->orWhereHas('parent', fn (Builder $p) => TranslatableSearch::apply($p, $p->qualifyColumn('name'), $search)))
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Category $c) => [$c->getKey() => static::groupLabel($c)])
                            ->all())
                        ->preload()
                        // ~115 active groups: the default 50 cut the list off mid-way.
                        ->optionsLimit(500)
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('sub_category_id', null))
                        ->nullable(),

                    Forms\Components\Select::make('sub_category_id')
                        ->label('Məhsul sinfi (alt kateqoriya)')
                        // Product classes hang off a GROUP category (3-level taxonomy:
                        // section → group → class), so the options must be scoped to
                        // the selected category — otherwise every class in the system
                        // was selectable under any group and the admin could store a
                        // combination the seller endpoint rejects.
                        ->relationship(
                            'subCategory',
                            'name',
                            // Active classes only; an existing product's current
                            // (possibly retired) class stays listed so it still shows.
                            modifyQueryUsing: fn (Builder $query, callable $get, ?Product $record): Builder => static::activeClasses($query, $get('category_id'), $record?->sub_category_id),
                        )
                        ->getOptionLabelFromRecordUsing(fn (SubCategory $record): string => $record->name.($record->is_active ? '' : ' (passiv)'))
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search, callable $get, ?Product $record): array => static::activeClasses(SubCategory::query(), $get('category_id'), $record?->sub_category_id)
                            ->where(fn (Builder $q) => TranslatableSearch::apply($q, $q->qualifyColumn('name'), $search))
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (SubCategory $s) => [$s->getKey() => $s->name.($s->is_active ? '' : ' (passiv)')])
                            ->all())
                        ->preload()
                        ->helperText('Yalnız seçilmiş kateqoriyaya (qrupa) aid siniflər göstərilir.')
                        ->noOptionsMessage('Bu kateqoriya üçün aktiv məhsul sinfi yoxdur.')
                        ->noSearchResultsMessage('Axtarışa uyğun məhsul sinfi tapılmadı.')
                        // Belt and braces: a stale id from a previously selected group
                        // must not slip through if it is posted directly.
                        ->rules([
                            fn (callable $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                                if (blank($value)) {
                                    return;
                                }

                                $belongs = SubCategory::query()
                                    ->whereKey($value)
                                    ->where('category_id', (int) $get('category_id'))
                                    ->exists();

                                if (! $belongs) {
                                    $fail(static::adminMessage(
                                        'admin-catalog.product.class_not_in_category',
                                        'Seçilmiş məhsul sinfi bu kateqoriyaya aid deyil.',
                                    ));
                                }
                            },
                        ])
                        // Live: the "Xüsusiyyətlər" tab rebuilds its attribute
                        // fields from the selected product class.
                        ->live()
                        // A new class gets an empty card. Without it a checkbox list has
                        // no starting array, and ticking one box ticked them all.
                        ->afterStateUpdated(fn ($state, callable $set) => $set('card', CardForm::blankState($state)))
                        // Nullable in the DB and never set by the seller flows — a hard
                        // requirement here made every seeded/seller product unsavable.
                        ->nullable()
                        ->visible(fn (callable $get) => (bool) $get('category_id')),

                    Forms\Components\Select::make('brand_id')
                        ->label('Brend')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        // A category card needs a brand or «Brendsiz» (CardProduct::check
                        // enforces it), so the field is only marked, not validated here.
                        // With «Brendsiz» on there is no brand: the field is disabled,
                        // unmarked and not submitted (CardForm::beforeSave clears it).
                        ->markAsRequired(fn (callable $get): bool => CardForm::isCard($get) && ! $get('is_unbranded'))
                        ->disabled(fn (callable $get): bool => CardForm::isCard($get) && (bool) $get('is_unbranded'))
                        ->nullable(),

                    // Category card (docs/product-cards.md): brand-level fields, in
                    // the card's order — brand, country, collection, then the article.
                    ...CardForm::commonFields(),

                    Forms\Components\TextInput::make('sku')
                        ->label('SKU (artikul)')
                        ->unique(ignoreRecord: true)
                        ->nullable(),
                ]),

                static::tab('Qiymət')->schema([
                    // Category card, board classes: the basis comes before the price.
                    CardForm::basisField(),

                    Forms\Components\TextInput::make('price')
                        ->label('Qiymət (₼)')
                        ->numeric()
                        // A category card checks the price itself: a custom size priced
                        // on request has none (CardProduct::check). The marker follows
                        // the same rule, so a card product still shows the «*».
                        ->required(fn (callable $get): bool => ! CardForm::isCard($get))
                        ->markAsRequired(fn (callable $get): bool => ! CardForm::isCard($get) || ! CardForm::quoteOnly($get))
                        ->prefix('₼'),

                    Forms\Components\TextInput::make('old_price')
                        ->label('Köhnə qiymət (₼)')
                        ->numeric()
                        ->nullable()
                        ->prefix('₼'),

                    Forms\Components\TextInput::make('discount_percent')
                        ->label('Endirim (%)')
                        ->numeric()
                        ->nullable()
                        ->suffix('%'),

                    // Category card: availability, price basis (the card computes the
                    // unit, the pack size and what one sold unit costs).
                    ...CardForm::pricingFields(),

                    Forms\Components\Select::make('unit')
                        ->label('Ölçü vahidi')
                        ->options([
                            'piece' => 'Ədəd',
                            'm2' => 'm²',
                            'lm' => 'Xətti metr',
                            'kg' => 'Kq',
                            'litre' => 'Litr',
                            'set' => 'Dəst',
                            'roll' => 'Rulon',
                            'pack' => 'Paket',
                            'pallet' => 'Palet',
                        ])
                        ->default('piece')
                        ->visible(fn (callable $get): bool => ! CardForm::isCard($get)),

                    Forms\Components\TextInput::make('stock')
                        ->label('Stok')
                        // Whole units: 120.5 used to be rounded up silently.
                        ->integer()
                        ->minValue(0)
                        ->default(0),

                    // Counted from orders (Product::applySale) — read-only here, and
                    // never submitted, so a save cannot overwrite the real figure.
                    Forms\Components\TextInput::make('sold_count')
                        ->label('Satış sayı')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Sifarişlərdən avtomatik hesablanır.'),

                    Forms\Components\TextInput::make('min_order')
                        ->label('Minimum sifariş miqdarı')
                        ->helperText('Alıcı bir sifarişdə ən azı bu qədər məhsul seçməlidir. Adi satış üçün 1 yazın.')
                        ->numeric()
                        ->minValue(1)
                        ->default(1),

                    // Used by the renovation calculator. Without a package size an
                    // estimate can only ask for "44.94 m² of SPC" instead of the 21
                    // packs the client is actually charged for; without weight and
                    // volume the delivery and lifting lines cannot be computed and
                    // report themselves as incomplete rather than quoting 0 AZN.
                    Forms\Components\TextInput::make('package_size')
                        ->label('Bir paketdəki miqdar')
                        ->helperText('Kalkulyator üçün: 1 paket neçə m² / p.m / kq örtür. Boş buraxsanız, miqdar paketsiz hesablanır.')
                        ->numeric()
                        ->minValue(0)
                        ->nullable()
                        ->visible(fn (callable $get): bool => ! CardForm::isCard($get)),

                    Forms\Components\TextInput::make('package_unit')
                        ->label('Paketin adı')
                        ->helperText('Smetada göstərilir: paket, qutu, rulon, kisə …')
                        ->maxLength(24)
                        ->nullable()
                        ->visible(fn (callable $get): bool => ! CardForm::isCard($get)),

                    Forms\Components\TextInput::make('weight_kg')
                        ->label('Çəkisi (1 vahid)')
                        ->suffix('kq')
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),

                    Forms\Components\TextInput::make('volume_m3')
                        ->label('Həcmi (1 vahid)')
                        ->suffix('m³')
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                ]),

                static::tab('Statuslar')->schema([
                    Forms\Components\Toggle::make('is_visible')->label('Görünür')->default(true),
                    Forms\Components\Toggle::make('is_approved')->label('Təsdiqlənib'),
                    Forms\Components\Toggle::make('is_featured')->label('Seçilmiş məhsul'),
                    Forms\Components\Toggle::make('is_sale')->label('SALE məhsulu'),
                    Forms\Components\Toggle::make('show_on_homepage')
                        ->label('Ana səhifədə göstər')
                        ->helperText('Yalnız seçilmiş/SALE statuslu məhsullar üçün keçərlidir — ana səhifə bölmələrində görünəcək.'),
                    Forms\Components\Toggle::make('free_delivery')->label('Pulsuz çatdırılma'),
                    Forms\Components\Toggle::make('return_14_days')->label('14 gün qaytarma'),
                ]),

                static::tab('Şəkillər')->schema([
                    Forms\Components\Repeater::make('images')
                        ->relationship()
                        ->schema([
                            Forms\Components\FileUpload::make('path')
                                ->label('Şəkil')
                                ->image()
                                ->disk('public')
                                ->directory('products')
                                // path is NOT NULL in DB — an empty upload state must fail
                                // validation, not blow up with an SQL error on save.
                                ->required(),
                            Forms\Components\Toggle::make('is_main')->label('Əsas şəkil'),
                            Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                        ])
                        ->maxItems(4)
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0)
                        ->label('Şəkillər'),
                ]),

                static::tab('Xüsusiyyətlər')->schema([
                    // Class-specific fields are switched off for now (config/catalog.php):
                    // the tags, the classifier attributes and the laminate card go with them.
                    Forms\Components\Select::make('applications')
                        ->label('Tətbiq sahələri')
                        ->relationship('applications', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->visible(fn (callable $get): bool => ClassifierFields::enabled() && ! CardForm::isCard($get)),

                    // Category card (docs/product-cards.md) — replaces every other block here.
                    CardForm::section(),

                    Section::make('Klassifikator xüsusiyyətləri')
                        ->description('Seçilmiş məhsul sinfinin (alt kateqoriyanın) atributları. Sinif seçmək üçün "Əsas" tabında alt kateqoriyanı təyin edin.')
                        ->schema(fn (callable $get): array => static::classifierAttributeFields($get('sub_category_id')))
                        ->columns(2)
                        ->visible(fn (callable $get): bool => ClassifierFields::enabled()
                            && (bool) $get('sub_category_id')
                            && ! CardForm::isCard($get)
                            && ! LaminateCatalog::isLaminateClass((int) $get('sub_category_id'))),

                    // Laminate has a typed card the seller fills in (TZ «Ламинат»); the
                    // moderator sees it here and decides with Approve / Reject.
                    Section::make('Laminat kartı')
                        ->description('Satıcının doldurduğu laminat kartı — moderasiya üçün. Təsdiq və rədd siyahıdakı düymələrlədir.')
                        ->schema([
                            View::make('filament.laminate-summary')
                                ->viewData(fn (?Product $record): array => [
                                    'summary' => $record ? app(LaminateModeration::class)->summary($record) : null,
                                ]),
                        ])
                        ->visible(fn (callable $get): bool => LaminateCatalog::isLaminateClass((int) $get('sub_category_id'))),

                    // Sits next to the classifier fields on purpose: this is where the
                    // seller's own "Xüsusiyyət əlavə et" rows land, and admins looked for
                    // them here — on the "Əlavə" tab they read as someone else's legacy data.
                    Section::make('Sərbəst xüsusiyyətlər')
                        ->description('Satıcının məhsul formasında əl ilə əlavə etdiyi ad–dəyər sətirləri (və köhnə məhsulların sərbəst xüsusiyyətləri). Məhsul səhifəsində klassifikator atributları ilə birlikdə göstərilir.')
                        ->schema([
                            Forms\Components\KeyValue::make('specifications')
                                ->label('Ad – dəyər sətirləri')
                                ->keyLabel('Parametr')
                                ->valueLabel('Dəyər'),
                        ])
                        // A laminate card has no free rows (TZ §3) — the seller form
                        // does not offer them, so neither does the admin.
                        ->visible(fn (callable $get): bool => ! CardForm::isCard($get)
                            && ! LaminateCatalog::isLaminateClass((int) $get('sub_category_id'))),
                ]),

                static::tab('Əlavə')->schema([
                    Forms\Components\RichEditor::make('features_text')
                        ->label('Özəlliklər')
                        ->translatable(),

                    Forms\Components\Select::make('accessoryProducts')
                        ->label('Aksessuarlar')
                        ->relationship('accessoryProducts', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('badges')
                        ->label('Badge-lər')
                        ->relationship('badges', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->maxItems(3),

                    Forms\Components\Select::make('condition')
                        ->label('Vəziyyət')
                        ->options([
                            'new' => 'Yeni',
                            'used' => 'İşlənmiş',
                        ])
                        ->default('new'),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    /**
     * The list table shows category.name and user.name, which without this ran
     * one query per row — fifty extra round trips per page of twenty-five.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['category', 'subCategory', 'user']);
    }

    /**
     * Groups a product can be placed in: active, under an active section, ordered
     * section by section. $keep (the product's current group) stays in even when
     * retired, so an existing product still shows and saves its value.
     */
    public static function activeGroups(Builder $query, mixed $keep = null): Builder
    {
        $keep = (int) $keep;

        return $query
            ->with('parent')
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $q) => $q
                    ->whereNotNull($q->qualifyColumn('parent_id'))
                    ->where($q->qualifyColumn('is_active'), true)
                    ->whereHas('parent', fn (Builder $p) => $p->where($p->qualifyColumn('is_active'), true)))
                ->when($keep > 0, fn (Builder $q) => $q->orWhere($q->qualifyColumn('id'), $keep)))
            ->orderByRaw('(select p.sort_order from categories as p where p.id = categories.parent_id)')
            ->orderBy('categories.sort_order');
    }

    /** «Section › Group»; a retired group (or one under a retired section) says so. */
    public static function groupLabel(Category $group): string
    {
        $label = $group->parent ? $group->parent->name.' › '.$group->name : (string) $group->name;
        $active = $group->is_active && $group->parent_id && ($group->parent?->is_active ?? false);

        return $active ? $label : $label.' (passiv)';
    }

    /** Active classes of a group, plus the product's current class ($keep). */
    public static function activeClasses(Builder $query, mixed $categoryId, mixed $keep = null): Builder
    {
        $keep = (int) $keep;

        return $query
            ->where($query->qualifyColumn('category_id'), (int) $categoryId)
            ->where(fn (Builder $q) => $q
                ->where($q->qualifyColumn('is_active'), true)
                ->when($keep > 0, fn (Builder $q) => $q->orWhere($q->qualifyColumn('id'), $keep)))
            ->orderBy($query->qualifyColumn('sort_order'));
    }

    /**
     * Error count for a form tab: the validation errors whose state path sits under
     * one of the tab's fields. Card errors are raised after «Create» on the
     * «Xüsusiyyətlər» tab, so the tab header has to say where they are.
     */
    public static function tabErrorCount(Tabs\Tab $component, mixed $livewire): ?string
    {
        if (! is_object($livewire) || ! method_exists($livewire, 'getErrorBag')) {
            return null;
        }

        $keys = array_keys($livewire->getErrorBag()->getMessages());

        if ($keys === []) {
            return null;
        }

        $paths = [];
        foreach ($component->getChildSchemas(withHidden: true) as $schema) {
            foreach ($schema->getFlatFields(withHidden: true) as $field) {
                $paths[] = $field->getStatePath();
            }
        }

        $count = collect($keys)
            ->filter(fn (string $key): bool => collect($paths)->contains(fn (string $path): bool => $key === $path || str_starts_with($key, $path.'.')))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Every class (retired ones marked), grouped by «Section › Group».
     *
     * @return array<string, array<int, string>>
     */
    public static function classFilterOptions(): array
    {
        return SubCategory::query()
            ->with('category.parent')
            ->orderBy('sort_order')
            ->get()
            ->sortBy(fn (SubCategory $s) => [$s->category?->parent?->sort_order ?? PHP_INT_MAX, $s->category?->sort_order ?? PHP_INT_MAX])
            ->groupBy(fn (SubCategory $s): string => $s->category ? static::groupLabel($s->category) : '—')
            ->map(fn ($classes) => $classes
                ->mapWithKeys(fn (SubCategory $s) => [$s->getKey() => $s->name.($s->is_active ? '' : ' (passiv)')])
                ->all())
            ->all();
    }

    private static function tab(string $label): Tabs\Tab
    {
        return Tabs\Tab::make($label)
            ->badge(fn (Tabs\Tab $component, $livewire): ?string => static::tabErrorCount($component, $livewire))
            ->badgeColor('danger')
            ->badgeTooltip('Bu tabda doldurulmalı və ya düzəldilməli sahələr var');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width(60),
                // The default searchable() on a translatable json column
                // compiles to `name like '%term%'`, which cannot use an index
                // and scanned the whole table on every keystroke of the admin
                // search box. search_text is the folded, FULLTEXT-indexed
                // mirror of the same content, so the search goes through the
                // index instead. Sorting still uses the json column, which is
                // fine: it only ever sorts one page.
                Tables\Columns\TextColumn::make('name')
                    ->label('Ad')
                    ->searchable(query: fn (Builder $query, string $search): Builder => SearchService::buildSellerProductQuery($query, $search))
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('category.name')->label('Kateqoriya')->placeholder('—'),
                Tables\Columns\TextColumn::make('subCategory.name')
                    ->label('Məhsul sinfi')
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')->label('Satıcı'),
                Tables\Columns\TextColumn::make('price')->label('Qiymət')->money('AZN')->sortable(),
                Tables\Columns\TextColumn::make('stock')->label('Stok')->sortable(),
                Tables\Columns\TextColumn::make('moderation_status')
                    ->label('Moderasiya')
                    ->badge()
                    ->state(fn (Product $record) => $record->moderation_status)
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'approved' => 'Təsdiqlənib',
                        'rejected' => 'Rədd edilib',
                        default => 'Gözləyir',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\IconColumn::make('is_featured')->label('Seçilmiş')->boolean(),
                Tables\Columns\IconColumn::make('is_sale')->label('SALE')->boolean(),
                Tables\Columns\ToggleColumn::make('show_on_homepage')->label('Ana səhifədə'),
                Tables\Columns\ToggleColumn::make('is_visible')->label('Görünür'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable()
                    // 0 = no manual position; such products keep the storefront's
                    // own ordering instead of being pinned to the top.
                    ->formatStateUsing(fn (?int $state) => $state ?: '—')
                    ->tooltip('Sıranı dəyişmək üçün sətri sürüşdürün'),
                Tables\Columns\TextColumn::make('created_at')->label('Yaradılıb')->dateTime('d.m.Y')->sortable(),
            ])
            // Drag & drop writes into sort_order; the storefront reads it back so a
            // dragged product actually moves in the catalog (CatalogController).
            ->reorderable('sort_order')
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kateqoriya')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                // Classes grouped under «Section › Group» — class names repeat across
                // groups. Plain options, so the search runs in the browser and is
                // case-insensitive.
                Tables\Filters\SelectFilter::make('sub_category_id')
                    ->label('Məhsul sinfi')
                    ->options(fn (): array => static::classFilterOptions())
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_approved')->label('Təsdiqlənib'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Seçilmiş'),
                Tables\Filters\TernaryFilter::make('is_sale')->label('SALE'),
                Tables\Filters\TernaryFilter::make('show_on_homepage')->label('Ana səhifədə'),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Təsdiqlə')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Məhsulu təsdiqlə')
                    ->modalDescription('Təsdiqdən sonra məhsul dərhal kataloqda və axtarışda görünəcək.')
                    ->visible(fn (Product $record) => ! $record->is_approved)
                    ->action(function (Product $record, Actions\Action $action): void {
                        // A laminate card that misses what the TZ requires for
                        // publication cannot be approved — the buyer would see a card
                        // without thickness, size or pack area.
                        if ($missing = static::laminateMissing($record)) {
                            Notification::make()
                                ->danger()
                                ->title('Laminat kartı tam deyil')
                                ->body('Çatışmır: '.implode(', ', $missing).'. Satıcıdan doldurmasını istəyin və ya səbəb göstərərək rədd edin.')
                                ->send();
                            $action->halt();
                        }

                        $record->update([
                            'is_approved' => true,
                            'rejected_at' => null,
                            'rejection_reason' => null,
                        ]);
                    }),
                Actions\Action::make('reject')
                    ->label('Rədd et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Product $record) => ! $record->is_approved && ! $record->rejected_at)
                    ->schema([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rədd səbəbi')
                            ->helperText('Bu mətn satıcıya kabinetində göstəriləcək.')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(fn (Product $record, array $data) => $record->update([
                        'is_approved' => false,
                        'is_visible' => false,
                        'rejected_at' => now(),
                        'rejection_reason' => $data['rejection_reason'],
                    ])),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
                Actions\BulkAction::make('approve_selected')
                    ->label('Seçilmişləri təsdiqlə')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        // Same rule as the single approve: incomplete laminate cards
                        // are skipped and named, the rest are approved.
                        $skipped = [];

                        foreach ($records as $record) {
                            if (static::laminateMissing($record)) {
                                $skipped[] = '#'.$record->id;

                                continue;
                            }

                            $record->update([
                                'is_approved' => true,
                                'rejected_at' => null,
                                'rejection_reason' => null,
                            ]);
                        }

                        if ($skipped) {
                            Notification::make()
                                ->warning()
                                ->title('Bəzi laminat kartları tam deyil')
                                ->body('Təsdiqlənmədi: '.implode(', ', $skipped))
                                ->send();
                        }
                    }),
            ]);
    }

    /**
     * What a laminate product still misses for publication (TZ «Ламинат» §3); empty
     * for complete cards and for every other class.
     *
     * @return list<string>
     */
    public static function laminateMissing(Product $record): array
    {
        if (! LaminateCatalog::isLaminateClass($record->sub_category_id)) {
            return [];
        }

        return app(LaminateModeration::class)->summary($record)['missing'] ?? ['laminat kartı'];
    }

    /**
     * Builds the dynamic form fields for the classifier attributes of the given
     * product class (sub category). State lives under "attribute_values.{attributeId}"
     * — the Create/Edit pages pull it out before save and persist it as
     * ProductAttributeValue rows (see syncAttributeValues()).
     */
    public static function classifierAttributeFields($subCategoryId): array
    {
        if (! $subCategoryId) {
            return [];
        }

        $subCategory = SubCategory::query()->with(['attributes.options'])->find($subCategoryId);

        if (! $subCategory) {
            return [];
        }

        $fields = [];

        foreach ($subCategory->attributes as $attribute) {
            $key = "attribute_values.{$attribute->id}";
            $label = $attribute->getTranslation('name', 'az');
            $unit = $attribute->pivot->unit;
            $tooltip = $attribute->getTranslation('tooltip', 'az') ?: null;
            // Marked as a hint (not enforced) so incomplete legacy products
            // stay editable by the admin.
            $hint = $attribute->pivot->is_required ? 'Məcburi' : null;

            $options = $attribute->field_type->hasOptions()
                ? $attribute->options->mapWithKeys(fn ($option) => [$option->id => $option->getTranslation('value', 'az')])->all()
                : [];

            $typeFields = match ($attribute->field_type) {
                AttributeFieldType::Dropdown => [
                    Forms\Components\Select::make($key)->label($label)->options($options)->nullable(),
                ],
                AttributeFieldType::Multiselect => [
                    Forms\Components\Select::make($key)->label($label)->options($options)->multiple(),
                ],
                // Three states — unanswered (no row stored) / Bəli / Xeyr. A Toggle
                // can never be null, so every boolean attribute of the class used to
                // persist value_bool = 0 and match the catalog's "Xeyr" filter even
                // when the admin never touched it. Mirrors the seller form's select.
                AttributeFieldType::Boolean => [
                    Forms\Components\Select::make($key)
                        ->label($label)
                        ->options([
                            '1' => static::adminMessage('admin-catalog.product.yes', 'Bəli'),
                            '0' => static::adminMessage('admin-catalog.product.no', 'Xeyr'),
                        ])
                        ->placeholder(static::adminMessage('admin-catalog.product.unanswered', 'Seçilməyib'))
                        ->native(false)
                        ->nullable(),
                ],
                AttributeFieldType::Numeric, AttributeFieldType::Decimal => [
                    Forms\Components\TextInput::make($key)->label($label)->numeric()->suffix($unit),
                ],
                AttributeFieldType::Range => [
                    Forms\Components\TextInput::make("{$key}.min")->label("{$label} (min)")->numeric()->suffix($unit),
                    Forms\Components\TextInput::make("{$key}.max")->label("{$label} (maks)")->numeric()->suffix($unit),
                ],
                AttributeFieldType::Textarea => [
                    Forms\Components\Textarea::make($key)->label($label)->rows(2),
                ],
                default => [
                    Forms\Components\TextInput::make($key)->label($label)->maxLength(255),
                ],
            };

            foreach ($typeFields as $index => $field) {
                if ($tooltip && $index === 0) {
                    $field->helperText($tooltip);
                }
                if ($hint) {
                    $field->hint($hint);
                }
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Loads the product's ProductAttributeValue rows into the
     * "attribute_values" form state shape used by classifierAttributeFields().
     */
    public static function attributeValuesFormState(Product $product): array
    {
        $state = [];

        $grouped = $product->attributeValues()->with('attribute')->get()->groupBy('attribute_id');

        foreach ($grouped as $attributeId => $rows) {
            $first = $rows->first();
            $type = $first->attribute?->field_type;

            if (! $type) {
                continue;
            }

            $state[$attributeId] = match ($type) {
                AttributeFieldType::Dropdown => $first->attribute_option_id,
                AttributeFieldType::Multiselect => $rows->pluck('attribute_option_id')->filter()->values()->all(),
                // '1'/'0' — the option keys of the 3-state select (a bare false would
                // cast to '' and render as the "unanswered" placeholder).
                AttributeFieldType::Boolean => $first->value_bool ? '1' : '0',
                AttributeFieldType::Numeric, AttributeFieldType::Decimal => static::trimDecimal($first->value_numeric),
                AttributeFieldType::Range => [
                    'min' => static::trimDecimal($first->value_min),
                    'max' => static::trimDecimal($first->value_max),
                ],
                default => $first->value_text,
            };
        }

        return $state;
    }

    /**
     * Persists form state back into product_attribute_values.
     *
     * The stored values must always describe exactly the product's CURRENT class:
     * anything belonging to a previously selected class is dropped first (and
     * clearing the class wipes every classifier value), because those fields are
     * no longer rendered and therefore never submitted. This mirrors the seller
     * flow in Cabinet\BusinessProductController::update().
     *
     * For every attribute of the current class that is present in the state,
     * existing rows are deleted and re-inserted (multiselect = one row per option).
     */
    public static function syncAttributeValues(Product $product, array $values): void
    {
        $classAttributeIds = static::classAttributeIds($product->sub_category_id);

        // No class (or the class was cleared) → no classifier values may survive.
        $product->attributeValues()
            ->when(
                $classAttributeIds !== [],
                fn ($query) => $query->whereNotIn('attribute_id', $classAttributeIds),
            )
            ->delete();

        if ($classAttributeIds === []) {
            return;
        }

        $submittedIds = array_values(array_intersect(
            array_map('intval', array_keys($values)),
            $classAttributeIds,
        ));

        if ($submittedIds === []) {
            return;
        }

        $attributes = Attribute::query()->whereKey($submittedIds)->get()->keyBy('id');

        foreach ($values as $attributeId => $value) {
            $attribute = $attributes->get((int) $attributeId);

            if (! $attribute) {
                continue;
            }

            $product->attributeValues()->where('attribute_id', $attribute->id)->delete();

            foreach (static::attributeValueRows($attribute, $value) as $row) {
                $product->attributeValues()->create(['attribute_id' => $attribute->id] + $row);
            }
        }
    }

    /**
     * Attribute ids that make up the given product class's form definition.
     *
     * @return list<int>
     */
    private static function classAttributeIds($subCategoryId): array
    {
        if (! $subCategoryId) {
            return [];
        }

        $subCategory = SubCategory::query()->find($subCategoryId);

        if (! $subCategory) {
            return [];
        }

        return $subCategory->attributes()
            ->pluck('attributes.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private static function attributeValueRows(Attribute $attribute, $value): array
    {
        return match ($attribute->field_type) {
            AttributeFieldType::Dropdown => filled($value)
                ? [['attribute_option_id' => (int) $value]]
                : [],
            AttributeFieldType::Multiselect => collect((array) $value)
                ->filter()
                ->map(fn ($optionId) => ['attribute_option_id' => (int) $optionId])
                ->values()
                ->all(),
            // blank() keeps "0"/false as a real answer while null/"" stores no row.
            AttributeFieldType::Boolean => blank($value)
                ? []
                : [['value_bool' => (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN)]],
            AttributeFieldType::Numeric, AttributeFieldType::Decimal => is_numeric($value)
                ? [['value_numeric' => $value]]
                : [],
            AttributeFieldType::Range => (is_numeric($value['min'] ?? null) || is_numeric($value['max'] ?? null))
                ? [[
                    'value_min' => is_numeric($value['min'] ?? null) ? $value['min'] : null,
                    'value_max' => is_numeric($value['max'] ?? null) ? $value['max'] : null,
                ]]
                : [],
            default => filled($value)
                ? [['value_text' => (string) $value]]
                : [],
        };
    }

    private static function trimDecimal($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Only genuinely pending items (not already-rejected ones) need admin attention.
        return static::getModel()::pendingReview()->count() ?: null;
    }
}
