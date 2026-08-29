import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function usePriorityScoring() {
    const loading = ref(false);
    const priorityHttp = useHttp({});

    const calculatePriorityScores = (heatmapAnalysisId: number) => {
        loading.value = true;

        priorityHttp.post(`/priority-scores/calculate/${heatmapAnalysisId}`, {
            onSuccess: (data: any) => {
                router.flash('message', 'Priority scores calculated successfully!');
                router.reload();
            },
            onHttpException: (response: any) => {
                console.error('HTTP Error:', response.status);
                router.flash('error', response.data?.error || 'Failed to calculate priority scores');
            },
            onNetworkError: (err: any) => {
                console.error('Network error:', err.message);
                router.flash('error', 'Network error while calculating priority scores');
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    return {
        loading,
        calculatePriorityScores,
    };
}
