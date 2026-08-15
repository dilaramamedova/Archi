<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PortfolioStatus;
use App\Filament\Concerns\CachesNavigationBadge;
use App\Filament\Resources\PortfolioApprovalResource\Pages;
use App\Models\SpecialistPortfolioItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class PortfolioApprovalResource extends Resource
{
    use CachesNavigationBadge;

    protected static ?string $model = SpecialistPortfolioItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'İstifadəçilər';

    protected static ?string $navigationLabel = 'Portfolio yoxlaması';

    protected static ?string $modelLabel = 'Portfolio şəkli';

    protected static ?string $pluralModelLabel = 'Portfolio yoxlaması';

    protected static ?string $slug = 'portfolio-approvals';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return self::cachedBadge(
            'portfolio:pending',
            fn () => self::getModel()::query()->where('status', PortfolioStatus::Pending)->count(),
        );
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Yoxlama')->schema([
                Forms\Components\FileUpload::make('image_path')
                    ->label('Şəkil')->image()->disk('public')->directory('specialists/portfolio')->required(),
                Forms\Components\TextInput::make('title')->label('Başlıq')->maxLength(120),
                Forms\Components\Select::make('status')->label('Status')->options(self::statusOptions())->required(),
                Forms\Components\Toggle::make('is_cover')->label('Üz qabığı'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')->label('Şəkil')->disk('public')->height(90)->width(130),
                Tables\Columns\TextColumn::make('specialistProfile.user.name')->label('Mütəxəssis')->searchable(),
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->placeholder('Başlıqsız'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (PortfolioStatus $state): string => $state->label())
                    ->color(fn (PortfolioStatus $state): string => match ($state) {
                        PortfolioStatus::Pending => 'warning',
                        PortfolioStatus::Approved => 'success',
                        PortfolioStatus::Rejected => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Göndərilib')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')->options(self::statusOptions())->default(PortfolioStatus::Pending->value),
            ])
            ->recordActions([
                Actions\Action::make('approve')->label('Təsdiqlə')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (SpecialistPortfolioItem $record): bool => $record->status !== PortfolioStatus::Approved)
                    ->requiresConfirmation()
                    ->action(fn (SpecialistPortfolioItem $record): bool => $record->update([
                        'status' => PortfolioStatus::Approved,
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                    ])),
                Actions\Action::make('reject')->label('Rədd et')->icon('heroicon-o-x-mark')->color('danger')
                    ->visible(fn (SpecialistPortfolioItem $record): bool => $record->status !== PortfolioStatus::Rejected)
                    ->requiresConfirmation()
                    ->action(fn (SpecialistPortfolioItem $record): bool => $record->update([
                        'status' => PortfolioStatus::Rejected,
                        'approved_at' => null,
                        'approved_by' => auth()->id(),
                    ])),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortfolioApprovals::route('/'),
            'edit' => Pages\EditPortfolioApproval::route('/{record}/edit'),
        ];
    }

    private static function statusOptions(): array
    {
        return collect(PortfolioStatus::cases())->mapWithKeys(
            fn (PortfolioStatus $status): array => [$status->value => $status->label()],
        )->all();
    }
}
