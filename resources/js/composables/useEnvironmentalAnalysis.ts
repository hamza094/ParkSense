import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function useEnvironmentalAnalysis() {
    const isRunningEnvironmentalAnalysis = ref(false);
    const environmentalHttp = useHttp({});

    const runEnvironmentalAnalysis = (
        onSuccess: (submissions: any[]) => void,
        heatAnalysisResults: any
    ) => {
        isRunningEnvironmentalAnalysis.value = true;
        
        environmentalHttp.post('/environmental/run-analysis', {
            onSuccess: (data: any) => {
                onSuccess(data.submissions);
                router.flash('message', 'Environmental analysis submitted successfully. Processing...');
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
                isRunningEnvironmentalAnalysis.value = false;
            }
        });
    };

    return {
        isRunningEnvironmentalAnalysis,
        runEnvironmentalAnalysis,
    };
}
