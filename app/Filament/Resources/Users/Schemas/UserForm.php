<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('avatar_path')
                    ->label('Foto Profil')
                    ->image()
                    ->avatar()
                    ->disk('public')
                    ->directory('avatars')
                    ->maxSize(2048)
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('256')
                    ->imageResizeTargetHeight('256')
                    ->helperText('Opsional. Maks 2MB, format JPG/PNG.'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(12)
                    ->maxLength(255)
                    ->helperText('Minimal 12 karakter. Kosongkan saat edit jika password tidak ingin diubah.'),
                TextInput::make('username')
                    ->maxLength(255),
                CheckboxList::make('roles')
                    ->relationship('roles', 'name')
                    ->columns(2)
                    ->required()
                    ->helperText('Pilih minimal satu role untuk menentukan akses user.'),
                Select::make('region_id')
                    ->relationship('region', 'name'),
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('last_login_at'),
            ]);
    }
}
