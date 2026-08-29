import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function useInterventionRecommendations() {
    const loading = ref(false);
    const interventionHttp = useHttp({});

    const generateRecommendations = (heatmapAnalysisId: number) => {
        loading.value = true;

        interventionHttp.post(`/interventions/generate/${heatmapAnalysisId}`, {
            onSuccess: (data: any) => {
                router.flash('message', 'Intervention recommendations generated successfully!');
                router.reload();
            },
            onHttpException: (response: any) => {
                console.error('HTTP Error:', response.status);
                router.flash('error', response.data?.error || 'Failed to generate intervention recommendations');
            },
            onNetworkError: (err: any) => {
                console.error('Network error:', err.message);
                router.flash('error', 'Network error while generating intervention recommendations');
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    return {
        loading,
        generateRecommendations,
    };
}
