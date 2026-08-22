<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Park;
use Illuminate\Database\Seeder;

class ParksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/phoenix_parks.geojson');

        if (! file_exists($path)) {
            throw new \RuntimeException("GeoJSON file not found: {$path}");
        }

        $geojson = json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($geojson['features'] as $feature) {
            $properties = $feature['properties'] ?? [];

            // Only Flatland Parks
            if (($properties['PROPERTY_TYPE'] ?? null) !== 'Flatland Park') {
                continue;
            }

            Park::updateOrCreate(
                [
                    'park_id' => (string) $properties['PARK_ID'],
                ],
                [
                    'name' => $properties['PROPERTY_NAME'] ?? 'Unnamed Park',
                    'property_type' => $properties['PROPERTY_TYPE'],
                    'park_type' => $properties['PARK_TYPE'] ?? null,

                    'acres' => $properties['PARK_ACRES'] ?? null,
                    'latitude' => $properties['LATITUDE'] ?? null,
                    'longitude' => $properties['LONGITUDE'] ?? null,

                    'playground' => $this->toNullableBoolean(
                        $properties['PLAYGROUND'] ?? null
                    ),
                    'splash_pads' => $this->toNullableBoolean(
                        $properties['SPLASH_PADS'] ?? null
                    ),
                    'swimming_pool' => $this->toNullableBoolean(
                        $properties['SWIMMING_POOL'] ?? null
                    ),
                    'sports_complex' => $this->toNullableBoolean(
                        $properties['SPORTS_COMPLEX'] ?? null
                    ),
                    'shade_structures' => $this->toNullableBoolean(
                        $properties['SHADE_STRUCTURES'] ?? null
                    ),
                    'recreation_community_center' => $this->toNullableBoolean(
                        $properties['RECREATION_COMMUNITY_CENTER'] ?? null
                    ),

                    'geometry' => isset($feature['geometry']) ? json_encode($feature['geometry']) : null,
                ]
            );
        }
    }

    private function toNullableBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match (strtolower(trim((string) $value))) {
            'yes', 'y', 'true', '1' => true,
            'no', 'n', 'false', '0' => false,
            default => null,
        };
    }
}
