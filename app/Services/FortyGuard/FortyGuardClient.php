<?php

namespace App\Services\FortyGuard;

use Illuminate\Support\Facades\Http;

class FortyGuardClient
{
    public function environmentalParameters(
        float $latitude,
        float $longitude,
        float $temperature,
        string $startDate,
        string $startTime,
        int $filterType = 1,
        array $analysis = [],
        ?string $endTime = null
    ): array {
        $dateTime = [
            'start_date' => $startDate,
            'start_time' => $startTime,
            'filter_type' => $filterType,
        ];

        if ($filterType === 2 && $endTime) {
            $dateTime['end_time'] = $endTime;
        }

        $response = Http::withHeaders([
            'api-key' => config('services.fortyguard.key'),
        ])
            ->acceptJson()
            ->post(
                config('services.fortyguard.url') . '/env_params',
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'temperature' => $temperature,
                    'date_time' => $dateTime,
                    ...($analysis ? ['analysis' => $analysis] : []),
                ]
            );

        $response->throw();

        return $response->json();
    }

    public function getStatus(string $activityId): array
    {
        $response = Http::withHeaders([
            'api-key' => config('services.fortyguard.key'),
        ])
            ->acceptJson()
            ->get(
                config('services.fortyguard.url') . '/status/' . $activityId
            );

        $response->throw();

        return $response->json();
    }

    public function satellite(
        float $latitude,
        float $longitude,
        string $startDate,
        string $startTime,
        int $filterType,
        int $granularity,
        ?string $endTime = null
    ): array {
        $dateTime = [
            'start_date' => $startDate,
            'start_time' => $startTime,
            'filter_type' => $filterType,
        ];

        if ($filterType === 2 && $endTime) {
            $dateTime['end_time'] = $endTime;
        }

        $response = Http::withHeaders([
            'api-key' => config('services.fortyguard.key'),
        ])
            ->acceptJson()
            ->post(
                config('services.fortyguard.url') . '/satellite',
                [
                    'sat' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ],
                    'date_time' => $dateTime,
                    'granularity' => $granularity,
                ]
            );

        $response->throw();

        return $response->json();
    }
}
