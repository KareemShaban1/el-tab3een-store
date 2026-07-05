<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds Egypt governorates, cities (districts), and areas (shiyakhas).
 *
 * Data source: Open Admin Data — CC-BY-4.0
 * https://github.com/open-admin-data/egypt-administrative-divisions
 */
class LocationsFeesSeeder extends Seeder
{
    public const DEFAULT_CITY_DELIVERY_COST = 50.0;

    public const DEFAULT_AREA_DELIVERY_COST = 50.0;

    /** @var array<int>|null When null, all businesses are seeded. */
    public ?array $businessIds = null;

    public bool $force = false;

    public float $cityDeliveryCost = self::DEFAULT_CITY_DELIVERY_COST;

    public float $areaDeliveryCost = self::DEFAULT_AREA_DELIVERY_COST;

    public function run(): void
    {
        if (! Schema::hasTable('lf_governorates')) {
            $this->command?->error('Locations fees tables do not exist. Run migrations first.');

            return;
        }

        $jsonPath = database_path('data/egypt_locations_hierarchy.json');
        if (! is_readable($jsonPath)) {
            $this->command?->error('Egypt locations data file not found at: '.$jsonPath);

            return;
        }

        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '512M');

        $payload = json_decode(file_get_contents($jsonPath), true);
        if (! is_array($payload) || empty($payload['data'])) {
            $this->command?->error('Invalid Egypt locations data file.');

            return;
        }

        $governorates = $payload['data'];
        $businessIds = $this->businessIds ?? DB::table('business')->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($businessIds === []) {
            $this->command?->warn('No businesses found to seed.');

            return;
        }

        $now = Carbon::now();

        foreach ($businessIds as $businessId) {
            $this->seedBusiness($businessId, $governorates, $now);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $governorates
     */
    protected function seedBusiness(int $businessId, array $governorates, Carbon $now): void
    {
        $existing = DB::table('lf_governorates')->where('business_id', $businessId)->exists();

        if ($existing && ! $this->force) {
            $this->command?->info("Skipping business {$businessId}: locations already seeded (use --force to replace).");

            return;
        }

        if ($existing) {
            DB::table('lf_areas')->where('business_id', $businessId)->delete();
            DB::table('lf_cities')->where('business_id', $businessId)->delete();
            DB::table('lf_governorates')->where('business_id', $businessId)->delete();
        }

        $govCount = 0;
        $cityCount = 0;
        $areaCount = 0;

        DB::beginTransaction();

        try {
            foreach ($governorates as $gov) {
                $governorateId = DB::table('lf_governorates')->insertGetId([
                    'business_id' => $businessId,
                    'name' => $this->locationName($gov['name'] ?? []),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $govCount++;

                foreach ($gov['district'] ?? [] as $district) {
                    $cityId = DB::table('lf_cities')->insertGetId([
                        'business_id' => $businessId,
                        'governorate_id' => $governorateId,
                        'name' => $this->locationName($district['name'] ?? []),
                        'delivery_cost' => $this->cityDeliveryCost,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $cityCount++;

                    $areaBatch = [];
                    foreach ($district['shiyakha'] ?? [] as $area) {
                        $areaBatch[] = [
                            'business_id' => $businessId,
                            'city_id' => $cityId,
                            'name' => $this->locationName($area['name'] ?? []),
                            'delivery_cost' => $this->areaDeliveryCost,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        if (count($areaBatch) >= 500) {
                            DB::table('lf_areas')->insert($areaBatch);
                            $areaCount += count($areaBatch);
                            $areaBatch = [];
                        }
                    }

                    if ($areaBatch !== []) {
                        DB::table('lf_areas')->insert($areaBatch);
                        $areaCount += count($areaBatch);
                    }
                }
            }

            DB::commit();
            $this->command?->info("Business {$businessId}: seeded {$govCount} governorates, {$cityCount} cities, {$areaCount} areas.");
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $name
     */
    protected function locationName(array $name): string
    {
        $local = trim((string) ($name['local'] ?? ''));
        if ($local !== '') {
            return $local;
        }

        $english = trim((string) ($name['en'] ?? ''));

        return $english !== '' ? $english : 'Unknown';
    }
}
