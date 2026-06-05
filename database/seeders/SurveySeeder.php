<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\Region;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        Survey::firstOrCreate(
            ['title' => 'Kuesioner Pendataan Lansia Provinsi Papua'],
            [
                'description' => 'Kuesioner pendukung pendataan lansia di Provinsi Papua.',
                'version'     => '1.0',
                'is_active'   => true,
            ]
        );

        // Seed region Papua Provinsi sebagai root
        $province = Region::firstOrCreate(
            ['code' => 'PPA'],
            [
                'name'      => 'Provinsi Papua',
                'type'      => 'province',
                'is_active' => true,
            ]
        );

        $city = Region::firstOrCreate(
            ['code' => 'PPA-JAY'],
            [
                'parent_id' => $province->id,
                'name'      => 'Kota Jayapura',
                'type'      => 'city',
                'is_active' => true,
            ]
        );

        $district = Region::firstOrCreate(
            ['code' => 'PPA-JAY-ABP'],
            [
                'parent_id' => $city->id,
                'name'      => 'Distrik Abepura',
                'type'      => 'district',
                'is_active' => true,
            ]
        );

        Region::firstOrCreate(
            ['code' => 'PPA-JAY-ABP-AWI'],
            [
                'parent_id' => $district->id,
                'name'      => 'Kelurahan Awiyo',
                'type'      => 'village',
                'is_active' => true,
            ]
        );

        Region::firstOrCreate(
            ['code' => 'PPA-JAY-ABP-WON'],
            [
                'parent_id' => $district->id,
                'name'      => 'Kelurahan Wondama',
                'type'      => 'village',
                'is_active' => true,
            ]
        );
    }
}
