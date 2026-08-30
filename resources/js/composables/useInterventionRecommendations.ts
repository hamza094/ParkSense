import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function useInterventionRecommendations() {
    const loading = ref(false);
    const interventionHttp = useHttp({});

    const generateRecommendations = (heatmapAnalysisId: number): Promise<void> => {
        return new Promise((resolve, reject) => {
            loading.value = true;

            interventionHttp.post(`/interventions/generate/${heatmapAnalysisId}`, {
                onSuccess: (data: any) => {
                    router.flash('message', 'Intervention recommendations generated successfully!');
                    resolve();
                },
                onHttpException: (response: any) => {
                    console.error('HTTP Error:', response.status);
                    const errorMessage = response.data?.error || response.data?.message || '';
                    
                    // If already exists, treat as success so pipeline continues
                    if (errorMessage.includes('already exists') || errorMessage.includes('already generated')) {
                        console.log('Intervention recommendations already exist, marking as completed for pipeline...');
                        resolve();
                    } else {
                        router.flash('error', errorMessage || 'Failed to generate intervention recommendations');
                        reject(new Error('HTTP Error'));
                    }
                },
                onNetworkError: (err: any) => {
                    console.error('Network error:', err.message);
                    router.flash('error', 'Network error while generating intervention recommendations');
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
        generateRecommendations,
    };
}
