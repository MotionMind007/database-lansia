<?php

namespace App\Filament\Resources\Regions;

use App\Filament\Resources\Regions\Pages\CreateRegion;
use App\Filament\Resources\Regions\Pages\EditRegion;
use App\Filament\Resources\Regions\Pages\ListRegions;
use App\Models\Region;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-map-pin';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function getNavigationLabel(): string
    {
        return 'Master Wilayah';
    }

    public static function getModelLabel(): string
    {
        return 'Wilayah';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Data Wilayah';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('parent_id')
                ->label('Wilayah Induk')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText('Kosongkan jika ini adalah Provinsi'),

            Select::make('type')
                ->label('Tipe Wilayah')
                ->options([
                    'province' => 'Provinsi',
                    'city' => 'Kabupaten / Kota',
                    'district' => 'Distrik / Kecamatan',
                    'village' => 'Kelurahan / Kampung',
                ])
                ->required()
                ->native(false),

            TextInput::make('name')
                ->label('Nama Wilayah')
                ->required()
                ->maxLength(255),

            TextInput::make('code')
                ->label('Kode Wilayah')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(20),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('name')
                    ->label('Nama Wilayah')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'province' => 'Provinsi',
                        'city' => 'Kabupaten/Kota',
                        'district' => 'Distrik/Kecamatan',
                        'village' => 'Kelurahan/Kampung',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'province' => 'info',
                        'city' => 'success',
                        'district' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('parent.name')
                    ->label('Wilayah Induk')
                    ->default('—')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe Wilayah')
                    ->options([
                        'province' => 'Provinsi',
                        'city' => 'Kabupaten/Kota',
                        'district' => 'Distrik/Kecamatan',
                        'village' => 'Kelurahan/Kampung',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(['1' => 'Aktif', '0' => 'Nonaktif']),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('type')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegions::route('/'),
            'create' => CreateRegion::route('/create'),
            'edit' => EditRegion::route('/{record}/edit'),
        ];
    }
}
