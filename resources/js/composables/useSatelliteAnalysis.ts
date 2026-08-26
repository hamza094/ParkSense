import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function useSatelliteAnalysis(initialResults: any = null) {
    const satelliteAnalysisResults = ref<any>(initialResults);
    const isRunningSatelliteAnalysis = ref(false);
    const satelliteAnalysisHttp = useHttp({});

    const runSatelliteAnalysis = (onSuccess: (submissions: any[]) => void, heatAnalysisResults: any) => {
        if (!heatAnalysisResults || heatAnalysisResults.length === 0) {
            router.flash('error', 'Please run heat analysis first.');
            return;
        }

        isRunningSatelliteAnalysis.value = true;
        
        satelliteAnalysisHttp.post('/satellite/run-analysis', {
            onSuccess: (data: any) => {
                satelliteAnalysisResults.value = data.submissions;
                onSuccess(data.submissions);
                router.flash('message', 'Satellite analysis submitted successfully!');
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
                isRunningSatelliteAnalysis.value = false;
            }
        });
    };

    return {
        satelliteAnalysisResults,
        isRunningSatelliteAnalysis,
        runSatelliteAnalysis,
    };
}
