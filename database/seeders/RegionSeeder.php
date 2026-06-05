<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        // Provinsi Papua (sudah ada dari SurveySeeder, tapi pastikan)
        $province = Region::firstOrCreate(
            ['code' => 'PPA'],
            ['name' => 'Provinsi Papua', 'type' => 'province', 'is_active' => true]
        );

        // Tambah Provinsi Papua Barat Daya
        $pbd = Region::firstOrCreate(
            ['code' => 'PBD'],
            ['name' => 'Provinsi Papua Barat Daya', 'type' => 'province', 'is_active' => true, 'parent_id' => null]
        );

        // ═══════════════════════════════════════
        // KABUPATEN/KOTA untuk Provinsi Papua
        // ═══════════════════════════════════════
        $cities = [
            ['code' => 'PPA-JAY',  'name' => 'Kota Jayapura'],
            ['code' => 'PPA-KJAY', 'name' => 'Kabupaten Jayapura'],
            ['code' => 'PPA-BIA',  'name' => 'Kabupaten Biak Numfor'],
            ['code' => 'PPA-KEE',  'name' => 'Kabupaten Keerom'],
            ['code' => 'PPA-SAR',  'name' => 'Kabupaten Sarmi'],
            ['code' => 'PPA-YAP',  'name' => 'Kabupaten Yapen'],
            ['code' => 'PPA-WAR',  'name' => 'Kabupaten Waropen'],
        ];

        foreach ($cities as $c) {
            Region::firstOrCreate(
                ['code' => $c['code']],
                ['name' => $c['name'], 'type' => 'city', 'parent_id' => $province->id, 'is_active' => true]
            );
        }

        // ═══════════════════════════════════════
        // DISTRIK untuk Kota Jayapura
        // ═══════════════════════════════════════
        $kotaJay = Region::where('code', 'PPA-JAY')->first();
        $districts = [
            ['code' => 'PPA-JAY-ABP', 'name' => 'Distrik Abepura'],
            ['code' => 'PPA-JAY-JAY', 'name' => 'Distrik Jayapura Utara'],
            ['code' => 'PPA-JAY-JAS', 'name' => 'Distrik Jayapura Selatan'],
            ['code' => 'PPA-JAY-HER', 'name' => 'Distrik Heram'],
            ['code' => 'PPA-JAY-MBY', 'name' => 'Distrik Muara Tami'],
        ];

        foreach ($districts as $d) {
            Region::firstOrCreate(
                ['code' => $d['code']],
                ['name' => $d['name'], 'type' => 'district', 'parent_id' => $kotaJay->id, 'is_active' => true]
            );
        }

        // ═══════════════════════════════════════
        // KELURAHAN untuk Distrik Abepura
        // ═══════════════════════════════════════
        $abepura = Region::where('code', 'PPA-JAY-ABP')->first();
        $villages = [
            ['code' => 'PPA-JAY-ABP-AWI', 'name' => 'Kelurahan Awiyo'],
            ['code' => 'PPA-JAY-ABP-WON', 'name' => 'Kelurahan Wondama'],
            ['code' => 'PPA-JAY-ABP-YAB', 'name' => 'Kelurahan Yabansai'],
            ['code' => 'PPA-JAY-ABP-ASN', 'name' => 'Kelurahan Asano'],
            ['code' => 'PPA-JAY-ABP-WAY', 'name' => 'Kelurahan Waena'],
        ];

        foreach ($villages as $v) {
            Region::firstOrCreate(
                ['code' => $v['code']],
                ['name' => $v['name'], 'type' => 'village', 'parent_id' => $abepura->id, 'is_active' => true]
            );
        }

        // Kelurahan untuk Distrik Jayapura Utara
        $jayUtara = Region::where('code', 'PPA-JAY-JAY')->first();
        $villagesJU = [
            ['code' => 'PPA-JAY-JAY-HAM', 'name' => 'Kelurahan Hamadi'],
            ['code' => 'PPA-JAY-JAY-APO', 'name' => 'Kelurahan Angkasa Pura'],
            ['code' => 'PPA-JAY-JAY-TAJ', 'name' => 'Kelurahan Tanjung Ria'],
        ];

        foreach ($villagesJU as $v) {
            Region::firstOrCreate(
                ['code' => $v['code']],
                ['name' => $v['name'], 'type' => 'village', 'parent_id' => $jayUtara->id, 'is_active' => true]
            );
        }

        // ═══════════════════════════════════════
        // DISTRIK untuk Kab Biak Numfor
        // ═══════════════════════════════════════
        $biak = Region::where('code', 'PPA-BIA')->first();
        $distrikBiak = [
            ['code' => 'PPA-BIA-BIA', 'name' => 'Distrik Biak Kota'],
            ['code' => 'PPA-BIA-NUM', 'name' => 'Distrik Numfor Barat'],
        ];

        foreach ($distrikBiak as $d) {
            Region::firstOrCreate(
                ['code' => $d['code']],
                ['name' => $d['name'], 'type' => 'district', 'parent_id' => $biak->id, 'is_active' => true]
            );
        }

        $biakKota = Region::where('code', 'PPA-BIA-BIA')->first();
        $villagesBiak = [
            ['code' => 'PPA-BIA-BIA-KAR', 'name' => 'Kelurahan Karang Mulia'],
            ['code' => 'PPA-BIA-BIA-BRI', 'name' => 'Kelurahan Brambaken'],
        ];
        foreach ($villagesBiak as $v) {
            Region::firstOrCreate(
                ['code' => $v['code']],
                ['name' => $v['name'], 'type' => 'village', 'parent_id' => $biakKota->id, 'is_active' => true]
            );
        }
    }
}
