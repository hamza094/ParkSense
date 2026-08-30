import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function usePriorityScoring() {
    const loading = ref(false);
    const priorityHttp = useHttp({});

    const calculatePriorityScores = (heatmapAnalysisId: number): Promise<void> => {
        return new Promise((resolve, reject) => {
            loading.value = true;

            priorityHttp.post(`/priority-scores/calculate/${heatmapAnalysisId}`, {
                onSuccess: (data: any) => {
                    router.flash('message', 'Priority scores calculated successfully!');
                    resolve();
                },
                onHttpException: (response: any) => {
                    console.error('HTTP Error:', response.status);
                    const errorMessage = response.data?.error || response.data?.message || '';
                    
                    // If already exists, treat as success so pipeline continues
                    if (errorMessage.includes('already exists') || errorMessage.includes('already calculated')) {
                        console.log('Priority scores already exist, marking as completed for pipeline...');
                        resolve();
                    } else {
                        router.flash('error', errorMessage || 'Failed to calculate priority scores');
                        reject(new Error('HTTP Error'));
                    }
                },
                onNetworkError: (err: any) => {
                    console.error('Network error:', err.message);
                    router.flash('error', 'Network error while calculating priority scores');
                    reject(err);
                },
                onFinish: () => {
                    loading.value = false;
                }
            });
        });
    };

    return {
        loading,
        calculatePriorityScores,
    };
}
