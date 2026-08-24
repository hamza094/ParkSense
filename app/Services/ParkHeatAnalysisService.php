<?php

namespace App\Services;

use App\Models\HeatmapAnalysis;
use App\Models\Park;
use App\Models\ParkHeatAnalysis;
use Illuminate\Database\Eloquent\Collection;
use MatanYadaev\EloquentSpatial\Objects\Geometry;
use MatanYadaev\EloquentSpatial\Objects\MultiPolygon;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class ParkHeatAnalysisService
{
    // ──────────────────────────────────────────────
    //  Tile Extraction
    // ──────────────────────────────────────────────

    /**
     * Extract heatmap tile features from the analysis map_data.
     *
     * @return array<int, array>
     */
    public function extractHeatmapTiles(HeatmapAnalysis $analysis): array
    {
        return data_get($analysis->map_data, 'features', []);
    }

    // ──────────────────────────────────────────────
    //  Spatial Intersection
    // ──────────────────────────────────────────────

    /**
     * Find heatmap tiles whose bounding box overlaps with the park geometry.
     *
     * NOTE: This uses bounding-box approximation, not exact polygon intersection.
     * For pixel-perfect accuracy, migrate tiles to a spatial DB table and use
     * MySQL's native ST_Intersects() via `whereIntersects()`.
     *
     * @param  array<int, array>  $tiles  GeoJSON feature arrays
     * @return array<int, array>  Matched tile features
     */
    public function findIntersectingTiles(Park $park, array $tiles): array
    {
        if (! $park->geometry) {
            return [];
        }

        $parkBbox = $this->calculateBoundingBox($park->geometry);

        return array_values(array_filter($tiles, function (array $tile) use ($parkBbox) {
            $tileGeometry = $this->featureToGeometry($tile);

            if (! $tileGeometry) {
                return false;
            }

            $tileBbox = $this->calculateBoundingBox($tileGeometry);

            return $this->bboxOverlaps($parkBbox, $tileBbox);
        }));
    }

    // ──────────────────────────────────────────────
    //  Heat Metrics
    // ──────────────────────────────────────────────

    /**
     * Calculate aggregated temperature metrics from matched tiles.
     *
     * @param  array<int, array>  $matchedTiles
     * @return array{average_temperature: float|null, min_temperature: float|null, max_temperature: float|null, matched_tile_count: int}
     */
    public function calculateParkHeatMetrics(array $matchedTiles): array
    {
        $temperatures = collect($matchedTiles)
            ->pluck('properties.average_temperature')
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($temperatures->isEmpty()) {
            return [
                'average_temperature' => null,
                'min_temperature' => null,
                'max_temperature' => null,
                'matched_tile_count' => 0,
            ];
        }

        return [
            'average_temperature' => round($temperatures->avg(), 2),
            'min_temperature' => round($temperatures->min(), 2),
            'max_temperature' => round($temperatures->max(), 2),
            'matched_tile_count' => $temperatures->count(),
        ];
    }

    // ──────────────────────────────────────────────
    //  Persistence & Ranking
    // ──────────────────────────────────────────────

    /**
     * Save (or update) park heat analysis results.
     */
    public function saveParkHeatAnalysis(Park $park, HeatmapAnalysis $analysis, array $metrics): ParkHeatAnalysis
    {
        return ParkHeatAnalysis::updateOrCreate(
            [
                'park_id' => $park->id,
                'heatmap_analysis_id' => $analysis->id,
            ],
            [
                'average_temperature' => $metrics['average_temperature'],
                'min_temperature' => $metrics['min_temperature'],
                'max_temperature' => $metrics['max_temperature'],
                'matched_tile_count' => $metrics['matched_tile_count'],
            ]
        );
    }

    /**
     * Rank parks by average temperature (hottest first).
     */
    public function rankParksByTemperature(HeatmapAnalysis $analysis, ?int $limit = null): Collection
    {
        return ParkHeatAnalysis::query()
            ->with('park')
            ->where('heatmap_analysis_id', $analysis->id)
            ->orderByDesc('average_temperature')
            ->when($limit, fn ($query, $limit) => $query->limit($limit))
            ->get();
    }

    // ──────────────────────────────────────────────
    //  Private Helpers
    // ──────────────────────────────────────────────

    /**
     * Convert a GeoJSON feature to a spatial Geometry object.
     */
    private function featureToGeometry(array $feature): ?Geometry
    {
        $geometry = $feature['geometry'] ?? null;

        if (! $geometry) {
            return null;
        }

        $geoJson = json_encode($geometry);

        return match ($geometry['type'] ?? null) {
            'Polygon' => Polygon::fromJson($geoJson),
            'MultiPolygon' => MultiPolygon::fromJson($geoJson),
            default => null,
        };
    }

    /**
     * Calculate the bounding box (envelope) for any supported geometry.
     *
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}
     */
    private function calculateBoundingBox(Geometry $geometry): array
    {
        $points = $this->extractCoordinates($geometry);

        $lats = array_column($points, 'lat');
        $lngs = array_column($points, 'lng');

        return [
            'min_lat' => $lats ? min($lats) : 0,
            'max_lat' => $lats ? max($lats) : 0,
            'min_lng' => $lngs ? min($lngs) : 0,
            'max_lng' => $lngs ? max($lngs) : 0,
        ];
    }

    /**
     * Flatten any geometry into an array of [lat, lng] points.
     *
     * @return array<int, array{lat: float, lng: float}>
     */
    private function extractCoordinates(Geometry $geometry): array
    {
        $coords = $geometry->toArray();
        $points = [];

        if ($geometry instanceof Polygon) {
            // Polygon: coordinates[ring_index][point_index] → [lng, lat]
            foreach ($coords['coordinates'] as $ring) {
                foreach ($ring as $point) {
                    if (is_array($point) && count($point) >= 2) {
                        $points[] = ['lat' => $point[1], 'lng' => $point[0]];
                    }
                }
            }
        } elseif ($geometry instanceof MultiPolygon) {
            // MultiPolygon: coordinates[polygon_index][ring_index][point_index] → [lng, lat]
            foreach ($coords['coordinates'] as $polygon) {
                foreach ($polygon as $ring) {
                    foreach ($ring as $point) {
                        if (is_array($point) && count($point) >= 2) {
                            $points[] = ['lat' => $point[1], 'lng' => $point[0]];
                        }
                    }
                }
            }
        }

        return $points;
    }

    /**
     * Check if two bounding boxes overlap.
     */
    private function bboxOverlaps(array $a, array $b): bool
    {
        return ! (
            $a['max_lat'] < $b['min_lat'] ||
            $a['min_lat'] > $b['max_lat'] ||
            $a['max_lng'] < $b['min_lng'] ||
            $a['min_lng'] > $b['max_lng']
        );
    }
}
