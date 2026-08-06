<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AboutSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Haqqımızda';

    protected static ?string $navigationLabel = 'Ayarlar';

    protected static ?string $title = 'Haqqımızda — Ayarlar';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    private const FILE_KEYS = [
        'about_hero_image',
        'about_mission_image',
    ];

    public function mount(): void
    {
        $data = [];

        foreach ($this->getSettingKeys() as $key) {
            $raw = Setting::getRaw($key);

            if (! in_array($key, self::FILE_KEYS, true) && is_string($raw)) {
                $decoded = json_decode($raw, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $raw = $decoded;
                }
            }

            $data[$key] = $raw;
        }

        $this->getSchema('content')->fill($data);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Section::make('Hero Banner')->schema([
                    Forms\Components\FileUpload::make('about_hero_image')
                        ->label('Arxa plan şəkli')
                        ->image()
                        ->disk('public')
                        ->directory('about')
                        ->visibility('public'),

                    Forms\Components\TextInput::make('about_hero_title')
                        ->label('Başlıq')
                        ->translatable(),

                    Forms\Components\Textarea::make('about_hero_subtitle')
                        ->label('Alt yazı')
                        ->rows(2)
                        ->translatable(),

                    Forms\Components\TextInput::make('about_cta1_text')
                        ->label('Düymə 1 mətni')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_cta1_url')
                        ->label('Düymə 1 linki'),

                    Forms\Components\TextInput::make('about_cta2_text')
                        ->label('Düymə 2 mətni')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_cta2_url')
                        ->label('Düymə 2 linki'),
                ]),

                Section::make('Missiya')->schema([
                    Forms\Components\TextInput::make('about_mission_tag')
                        ->label('Kicker')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_mission_title')
                        ->label('Başlıq')
                        ->translatable(),

                    Forms\Components\Textarea::make('about_mission_p1')
                        ->label('Paraqraf 1')
                        ->rows(3)
                        ->translatable(),

                    Forms\Components\Textarea::make('about_mission_p2')
                        ->label('Paraqraf 2')
                        ->rows(2)
                        ->translatable(),

                    Forms\Components\TextInput::make('about_mission_b1')
                        ->label('Bullet 1')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_mission_b2')
                        ->label('Bullet 2')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_mission_b3')
                        ->label('Bullet 3')
                        ->translatable(),

                    Forms\Components\FileUpload::make('about_mission_image')
                        ->label('Missiya şəkli')
                        ->image()
                        ->disk('public')
                        ->directory('about')
                        ->visibility('public'),
                ]),

                Section::make('Nə təklif edirik')->schema([
                    Forms\Components\TextInput::make('about_what_tag')
                        ->label('Kicker')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_what_title')
                        ->label('Başlıq')
                        ->translatable(),
                ]),

                Section::make('Necə işləyir')->schema([
                    Forms\Components\TextInput::make('about_how_tag')
                        ->label('Kicker')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_how_title')
                        ->label('Başlıq')
                        ->translatable(),
                ]),

                Section::make('Komanda')->schema([
                    Forms\Components\TextInput::make('about_team_tag')
                        ->label('Kicker')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_team_title')
                        ->label('Başlıq')
                        ->translatable(),
                ]),

                Section::make('Bizə qoşul')->schema([
                    Forms\Components\TextInput::make('about_join_tag')
                        ->label('Kicker')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_join_title')
                        ->label('Başlıq')
                        ->translatable(),
                ]),

                Section::make('Əlaqə')->schema([
                    Forms\Components\TextInput::make('about_contact_tag')
                        ->label('Kicker')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_contact_title')
                        ->label('Başlıq')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_contact_address_line1')
                        ->label('Ünvan (sətir 1)')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_contact_address_line2')
                        ->label('Ünvan (sətir 2)')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_contact_phone')
                        ->label('Telefon'),

                    Forms\Components\TextInput::make('about_contact_hours')
                        ->label('İş saatları')
                        ->translatable(),

                    Forms\Components\TextInput::make('about_contact_email')
                        ->label('E-poçt'),

                    Forms\Components\TextInput::make('about_contact_email_b2b')
                        ->label('B2B e-poçt mətni')
                        ->translatable(),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Yadda saxla')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->getSchema('content')->getState();

        foreach ($state as $key => $value) {
            if (in_array($key, self::FILE_KEYS, true)) {
                $filePath = is_array($value) ? collect($value)->first() : $value;
                Setting::set($key, $filePath);

                continue;
            }

            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Ayarlar yadda saxlanıldı')
            ->success()
            ->send();
    }

    private function getSettingKeys(): array
    {
        return [
            'about_hero_image',
            'about_hero_title',
            'about_hero_subtitle',
            'about_cta1_text',
            'about_cta1_url',
            'about_cta2_text',
            'about_cta2_url',
            'about_mission_tag',
            'about_mission_title',
            'about_mission_p1',
            'about_mission_p2',
            'about_mission_b1',
            'about_mission_b2',
            'about_mission_b3',
            'about_mission_image',
            'about_what_tag',
            'about_what_title',
            'about_how_tag',
            'about_how_title',
            'about_team_tag',
            'about_team_title',
            'about_join_tag',
            'about_join_title',
            'about_contact_tag',
            'about_contact_title',
            'about_contact_address_line1',
            'about_contact_address_line2',
            'about_contact_phone',
            'about_contact_hours',
            'about_contact_email',
            'about_contact_email_b2b',
        ];
    }
}
