<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class ImportPapuaRegionExcelSeeder extends Seeder
{
    public function run(): void
    {
        $path = env('PAPUA_REGION_EXCEL_PATH', database_path('data/data_wilayah_papua_lengkap.xlsx'));

        if (! is_file($path)) {
            throw new RuntimeException("File wilayah tidak ditemukan: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName('Data Desa Kelurahan');

        if (! $sheet) {
            throw new RuntimeException('Sheet "Data Desa Kelurahan" tidak ditemukan.');
        }

        $rows = collect($sheet->rangeToArray('A2:H'.$sheet->getHighestDataRow(), null, true, true, true))
            ->filter(fn (array $row): bool => filled($row['A'] ?? null) && filled($row['H'] ?? null))
            ->values();

        $created = [
            'city' => 0,
            'district' => 0,
            'village' => 0,
        ];
        $updated = [
            'city' => 0,
            'district' => 0,
            'village' => 0,
        ];

        DB::transaction(function () use ($rows, &$created, &$updated): void {
            $province = $this->province();
            $cities = [];
            $districts = [];

            foreach ($rows as $row) {
                $cityCode = $this->clean($row['C'] ?? null);
                $cityName = $this->clean($row['D'] ?? null);
                $districtCode = $this->clean($row['E'] ?? null);
                $districtName = $this->clean($row['F'] ?? null);
                $villageCode = $this->clean($row['A'] ?? null);
                $villageName = $this->clean($row['H'] ?? null);

                if (! $cityCode || ! $cityName || ! $districtCode || ! $districtName || ! $villageCode || ! $villageName) {
                    continue;
                }

                if (! isset($cities[$cityCode])) {
                    [$cities[$cityCode], $wasCreated] = $this->upsertRegion($cityCode, $cityName, 'city', $province->id);
                    $wasCreated ? $created['city']++ : $updated['city']++;
                }

                if (! isset($districts[$districtCode])) {
                    [$districts[$districtCode], $wasCreated] = $this->upsertRegion($districtCode, $districtName, 'district', $cities[$cityCode]->id);
                    $wasCreated ? $created['district']++ : $updated['district']++;
                }

                [, $wasCreated] = $this->upsertRegion($villageCode, $villageName, 'village', $districts[$districtCode]->id);
                $wasCreated ? $created['village']++ : $updated['village']++;
            }
        });

        $this->command?->info('Import wilayah Papua selesai.');
        $this->command?->line("Kab/Kota dibuat: {$created['city']}, diperbarui: {$updated['city']}");
        $this->command?->line("Distrik dibuat: {$created['district']}, diperbarui: {$updated['district']}");
        $this->command?->line("Kelurahan/Kampung dibuat: {$created['village']}, diperbarui: {$updated['village']}");
    }

    private function province(): Region
    {
        $province = Region::active()->province()->first();

        if ($province) {
            $province->update([
                'name' => 'Provinsi Papua',
                'is_active' => true,
            ]);

            return $province;
        }

        return Region::create([
            'code' => '91',
            'name' => 'Provinsi Papua',
            'type' => 'province',
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: Region, 1: bool}
     */
    private function upsertRegion(string $code, string $name, string $type, ?int $parentId): array
    {
        $region = Region::where('code', $code)->first();

        if ($region) {
            $region->update([
                'name' => $name,
                'type' => $type,
                'parent_id' => $parentId,
                'is_active' => true,
            ]);

            return [$region, false];
        }

        return [
            Region::create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'parent_id' => $parentId,
                'is_active' => true,
            ]),
            true,
        ];
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
