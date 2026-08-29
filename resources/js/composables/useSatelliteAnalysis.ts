import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function useSatelliteAnalysis() {
    const loading = ref(false);
    const satelliteAnalysisHttp = useHttp({});

    const runSatelliteAnalysis = (heatmapAnalysisId: number) => {
        loading.value = true;
        
        satelliteAnalysisHttp.post(`/satellite/run-analysis/${heatmapAnalysisId}`, {
            onSuccess: (data: any) => {
                router.flash('message', 'Satellite analysis submitted successfully. Processing...');
                router.reload();
            },
            onHttpException: (response: any) => {
                console.error('HTTP Error:', response.status);
                router.flash('error', response.data?.error || 'Failed to run satellite analysis');
            },
            onNetworkError: (error: any) => {
                console.error('Network error:', error.message);
                router.flash('error', 'Network error while running satellite analysis');
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    return {
        loading,
        runSatelliteAnalysis,
    };
}
