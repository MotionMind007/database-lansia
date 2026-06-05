<?php

namespace App\Filament\Resources\SurveySections\Pages;

use App\Filament\Resources\SurveySections\SurveySectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSurveySection extends EditRecord
{
    protected static string $resource = SurveySectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
