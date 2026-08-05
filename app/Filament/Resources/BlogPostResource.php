<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static string | \UnitEnum | null $navigationGroup = 'Kontent';
    protected static ?string $navigationLabel = 'Bloq yazıları';
    protected static ?string $modelLabel = 'Bloq yazısı';
    protected static ?string $pluralModelLabel = 'Bloq yazıları';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Əsas')->schema([
                Forms\Components\TextInput::make('title')->label('Başlıq')->required()->translatable(),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                Forms\Components\Textarea::make('excerpt')->label('Qısa mətn')->rows(3)->translatable(),

                Forms\Components\RichEditor::make('body')->label('Məzmun')->required()->translatable(),

                Forms\Components\FileUpload::make('cover_image')
                    ->label('Üz şəkli')
                    ->image()
                    ->disk('public')
                    ->directory('blog'),

                Forms\Components\TagsInput::make('tags')
                    ->label('Teqlər')
                    ->placeholder('Teq əlavə edin'),
            ]),

            Section::make('Parametrlər')->schema([
                Forms\Components\Toggle::make('is_published')->label('Dərc edilib'),
                Forms\Components\Toggle::make('is_featured')->label('Seçilmiş'),
                Forms\Components\Toggle::make('show_on_home')->label('Ana səhifədə göstər'),
                Forms\Components\Toggle::make('show_in_header')->label('Headerdə göstər'),
                Forms\Components\DateTimePicker::make('published_at')->label('Dərc tarixi'),
            ]),

            Section::make('SEO (OG)')->schema([
                Forms\Components\TextInput::make('og_title')->label('OG başlıq')->translatable(),
                Forms\Components\Textarea::make('og_description')->label('OG təsvir')->rows(2)->translatable(),
                Forms\Components\FileUpload::make('og_image')
                    ->label('OG şəkil')
                    ->image()
                    ->disk('public')
                    ->directory('blog/og'),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')->label('Şəkil')->width(60)->height(40),
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->searchable()->sortable()->limit(50),
                Tables\Columns\IconColumn::make('is_published')->label('Dərc')->boolean(),
                Tables\Columns\IconColumn::make('show_on_home')->label('Ana s.')->boolean(),
                Tables\Columns\IconColumn::make('show_in_header')->label('Header')->boolean(),
                Tables\Columns\TextColumn::make('views_count')->label('Baxış')->sortable(),
                Tables\Columns\TextColumn::make('published_at')->label('Dərc tarixi')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
