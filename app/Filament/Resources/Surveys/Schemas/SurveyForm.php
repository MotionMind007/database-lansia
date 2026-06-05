<?php

namespace App\Filament\Resources\Surveys\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SurveyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('version')
                    ->required()
                    ->default('1.0'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
