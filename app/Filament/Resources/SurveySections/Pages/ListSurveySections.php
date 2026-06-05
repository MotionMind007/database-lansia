<?php

namespace App\Filament\Resources\SurveySections\Pages;

use App\Filament\Resources\SurveySections\SurveySectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSurveySections extends ListRecords
{
    protected static string $resource = SurveySectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
