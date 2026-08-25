import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function useHeatAnalysis(initialResults: any = null) {
    const heatAnalysisResults = ref<any>(initialResults);
    const isRunningAnalysis = ref(false);
    const heatAnalysisHttp = useHttp({});

    const runHeatAnalysis = () => {
        isRunningAnalysis.value = true;
        
        heatAnalysisHttp.post('/parks/run-heat-analysis', {
            onSuccess: (data: any) => {
                heatAnalysisResults.value = data.ranked_parks;
                router.flash('message', 'Park heat analysis completed successfully!');
            },
            onHttpException: (response: any) => {
                console.error('HTTP Error:', response.status);
                router.flash('error', response.data?.error || 'Failed to run heat analysis');
            },
            onNetworkError: (error: any) => {
                console.error('Network error:', error.message);
                router.flash('error', 'Network error while running heat analysis');
            },
            onFinish: () => {
                isRunningAnalysis.value = false;
            }
        });
    };

    return {
        heatAnalysisResults,
        isRunningAnalysis,
        runHeatAnalysis,
    };
}
