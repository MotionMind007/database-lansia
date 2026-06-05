<?php

namespace App\Filament\Resources\SurveySections;

use App\Filament\Resources\SurveySections\Pages\CreateSurveySection;
use App\Filament\Resources\SurveySections\Pages\EditSurveySection;
use App\Filament\Resources\SurveySections\Pages\ListSurveySections;
use App\Filament\Resources\SurveySections\Schemas\SurveySectionForm;
use App\Filament\Resources\SurveySections\Tables\SurveySectionsTable;
use App\Models\SurveySection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SurveySectionResource extends Resource
{
    protected static ?string $model = SurveySection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SurveySectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SurveySectionsTable::configure($table);
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
            'index' => ListSurveySections::route('/'),
            'create' => CreateSurveySection::route('/create'),
            'edit' => EditSurveySection::route('/{record}/edit'),
        ];
    }
}
