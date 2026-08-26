import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function usePriorityScoring(initialScores: any[] = []) {
    const isRunningPriorityScoring = ref(false);
    const priorityScores = ref<any[]>(initialScores);
    const error = ref<string | null>(null);
    const priorityHttp = useHttp({});

    const calculatePriorityScores = (heatmapAnalysisId: number) => {
        isRunningPriorityScoring.value = true;
        error.value = null;

        priorityHttp.post(`/priority-scores/calculate/${heatmapAnalysisId}`, {
            onSuccess: (data: any) => {
                priorityScores.value = data.scores;
                router.flash('message', 'Priority scores calculated successfully!');
            },
            onHttpException: (response: any) => {
                console.error('HTTP Error:', response.status);
                error.value = response.data?.error || 'Failed to calculate priority scores';
                router.flash('error', response.data?.error || 'Failed to calculate priority scores');
            },
            onNetworkError: (err: any) => {
                console.error('Network error:', err.message);
                error.value = 'Network error while calculating priority scores';
                router.flash('error', 'Network error while calculating priority scores');
            },
            onFinish: () => {
                isRunningPriorityScoring.value = false;
            }
        });
    };

    const fetchPriorityScores = (heatmapAnalysisId: number) => {
        priorityHttp.get(`/priority-scores/${heatmapAnalysisId}`, {
            onSuccess: (data: any) => {
                priorityScores.value = data.scores;
            },
            onHttpException: (response: any) => {
                console.error('HTTP Error:', response.status);
                error.value = response.data?.error || 'Failed to fetch priority scores';
            },
            onNetworkError: (err: any) => {
                console.error('Network error:', err.message);
                error.value = 'Network error while fetching priority scores';
            }
        });
    };

    return {
        isRunningPriorityScoring,
        priorityScores,
        error,
        calculatePriorityScores,
        fetchPriorityScores,
    };
}
