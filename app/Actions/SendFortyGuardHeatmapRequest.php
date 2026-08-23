<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;

class SendFortyGuardHeatmapRequest
{
    public function __invoke(array $apiData)
    {
        return Http::timeout(60)
            ->withHeaders([
                'api-key' => config('services.fortyguard.key'),
            ])
            ->post(config('services.fortyguard.url') . '/heatmap', $apiData);
    }
}
