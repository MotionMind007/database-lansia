<?php

namespace App\Filament\Resources\SurveyQuestions;

use App\Filament\Resources\SurveyQuestions\Pages\CreateSurveyQuestion;
use App\Filament\Resources\SurveyQuestions\Pages\EditSurveyQuestion;
use App\Filament\Resources\SurveyQuestions\Pages\ListSurveyQuestions;
use App\Filament\Resources\SurveyQuestions\Schemas\SurveyQuestionForm;
use App\Filament\Resources\SurveyQuestions\Tables\SurveyQuestionsTable;
use App\Models\SurveyQuestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SurveyQuestionResource extends Resource
{
    protected static ?string $model = SurveyQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SurveyQuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SurveyQuestionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveyQuestions::route('/'),
            'create' => CreateSurveyQuestion::route('/create'),
            'edit' => EditSurveyQuestion::route('/{record}/edit'),
        ];
    }
}
