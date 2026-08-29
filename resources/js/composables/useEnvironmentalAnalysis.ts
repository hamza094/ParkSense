import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function useEnvironmentalAnalysis() {
    const loading = ref(false);
    const environmentalHttp = useHttp({});

    const runEnvironmentalAnalysis = (heatmapAnalysisId: number) => {
        loading.value = true;
        
        environmentalHttp.post(`/environmental/run-analysis/${heatmapAnalysisId}`, {
            onSuccess: (data: any) => {
                router.flash('message', 'Environmental analysis submitted successfully. Processing...');
                router.reload();
            },
            onHttpException: (response: any) => {
                console.error('HTTP Error:', response.status);
                router.flash('error', response.data?.error || 'Failed to run environmental analysis');
            },
            onNetworkError: (error: any) => {
                console.error('Network error:', error.message);
                router.flash('error', 'Network error while running environmental analysis');
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    return {
        loading,
        runEnvironmentalAnalysis,
    };
}
