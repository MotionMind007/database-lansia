<?php

namespace App\Filament\Resources\SurveyQuestions\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SurveyQuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('survey_section_id')
                    ->required()
                    ->numeric(),
                TextInput::make('question_number')
                    ->required(),
                Textarea::make('question_text')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('question_type')
                    ->required(),
                Toggle::make('is_required')
                    ->required(),
                Toggle::make('allow_multiple')
                    ->required(),
                Toggle::make('dashboard_enabled')
                    ->required(),
                TextInput::make('default_chart_type')
                    ->required()
                    ->default('bar'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('options'),
            ]);
    }
}
