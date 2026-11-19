<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\TenderSubmissionStatus;
use App\Filament\Concerns\CachesNavigationBadge;
use App\Filament\Resources\TenderSubmissionResource\Pages;
use App\Models\TenderSubmission;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * «Tender yarat» submissions from /tenders/create. The admin reads them here (they
 * are also mailed — TenderSubmissionNotification) and turns a good one into a draft
 * Tender with «Tender qaralaması yarat», then completes and publishes it.
 */
final class TenderSubmissionResource extends Resource
{
    use CachesNavigationBadge;

    protected static ?string $model = TenderSubmission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'Tenderlər';

    protected static ?string $navigationLabel = 'Tender müraciətləri';

    protected static ?string $modelLabel = 'Tender müraciəti';

    protected static ?string $pluralModelLabel = 'Tender müraciətləri';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        // Submissions come from the site; the admin's own tenders are made in «Tenderlər».
        return false;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Tender')->schema([
                Forms\Components\TextInput::make('title')->label('Tenderin adı')->required()->maxLength(200)->columnSpanFull(),
                Forms\Components\Select::make('tender_category_id')->label('Kateqoriya')->relationship('category', 'name'),
                Forms\Components\TextInput::make('city')->label('Şəhər / ünvan')->required()->maxLength(120),
                Forms\Components\TextInput::make('area_sqm')->label('Sahə, m²')->numeric(),
                Forms\Components\TextInput::make('budget')->label('Təxmini büdcə')->numeric(),
                Forms\Components\Select::make('currency')->label('Valyuta')
                    ->options(array_combine(TenderSubmission::CURRENCIES, TenderSubmission::CURRENCIES))->required(),
                Forms\Components\DatePicker::make('deadline_at')->label('Təkliflərin son tarixi'),
                Forms\Components\Textarea::make('description')->label('İşin təsviri')->rows(6)->required()->maxLength(5000)->columnSpanFull(),
            ])->columns(2),
            Section::make('Sifarişçi')->schema([
                Forms\Components\Select::make('user_id')->label('İstifadəçi')->relationship('user', 'email')->disabled(),
                Forms\Components\TextInput::make('organizer_name')->label('Sifarişçi / şirkət')->maxLength(160),
                Forms\Components\TextInput::make('contact_name')->label('Əlaqə şəxsi')->required()->maxLength(120),
                Forms\Components\TextInput::make('phone')->label('Telefon')->tel()->required()->maxLength(25),
                Forms\Components\TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
            ])->columns(2),
            Section::make('İdarəetmə')->schema([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(TenderSubmissionStatus::options())
                    // «Tender yaradılıb» has a tender behind it: set by the action, not by hand.
                    ->disableOptionWhen(fn (string $value, ?TenderSubmission $record): bool => $value === TenderSubmissionStatus::Converted->value
                        && $record?->status !== TenderSubmissionStatus::Converted)
                    ->required(),
                Forms\Components\Placeholder::make('tender_link')
                    ->label('Yaradılan tender')
                    ->content(fn (?TenderSubmission $record) => $record?->tender
                        ? new \Illuminate\Support\HtmlString('<a class="text-primary-600 underline" href="'.e(TenderResource::getUrl('edit', ['record' => $record->tender])).'">'
                            .e($record->tender->number.' — '.$record->tender->getTranslation('title', 'az')).'</a>')
                        : '—'),
                Forms\Components\Textarea::make('admin_note')->label('Admin qeydi')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['category', 'user']))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Tender')->searchable()->limit(50)->wrap(),
                Tables\Columns\TextColumn::make('category.name')->label('Kateqoriya')->placeholder('—'),
                Tables\Columns\TextColumn::make('city')->label('Şəhər')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('budget')->label('Büdcə')
                    ->state(fn (TenderSubmission $record): ?string => $record->budgetLabel())->placeholder('—'),
                Tables\Columns\TextColumn::make('contact_name')->label('Əlaqə şəxsi')->searchable()
                    ->description(fn (TenderSubmission $record): string => $record->phone),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->copyable()->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (TenderSubmissionStatus $state): string => $state->label())
                    ->color(fn (TenderSubmissionStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('created_at')->label('Göndərilib')->dateTime('d.m.Y H:i', 'Asia/Baku')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(TenderSubmissionStatus::options()),
            ])
            ->actions([
                self::convertAction(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    /** «Tender qaralaması yarat» — an unpublished Tender from the submission, opened for editing. */
    public static function convertAction(): Actions\Action
    {
        return Actions\Action::make('convert')
            ->label('Tender qaralaması yarat')
            ->icon('heroicon-o-document-duplicate')
            ->color('success')
            ->visible(fn (TenderSubmission $record): bool => $record->tender_id === null && $record->status !== TenderSubmissionStatus::Rejected)
            ->requiresConfirmation()
            ->modalDescription('Müraciətdən dərc olunmamış tender yaradılır. Onu tamamlayıb «Dərc olunub» etdikdən sonra lövhədə görünəcək.')
            ->action(function (TenderSubmission $record) {
                $tender = $record->toDraftTender();

                Notification::make()->title('Tender qaralaması yaradıldı: '.$tender->number)->success()->send();

                return redirect(TenderResource::getUrl('edit', ['record' => $tender]));
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderSubmissions::route('/'),
            'edit' => Pages\EditTenderSubmission::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::cachedBadge(
            'tender-submissions:new',
            fn () => TenderSubmission::query()->where('status', TenderSubmissionStatus::New)->count(),
        );
    }
}
