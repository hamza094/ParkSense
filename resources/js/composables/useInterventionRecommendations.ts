import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function useInterventionRecommendations(initialRecommendations: any[] = []) {
    const isGeneratingRecommendations = ref(false);
    const recommendations = ref<any[]>(initialRecommendations);
    const error = ref<string | null>(null);
    const interventionHttp = useHttp({});

    const generateRecommendations = (heatmapAnalysisId: number) => {
        isGeneratingRecommendations.value = true;
        error.value = null;

        interventionHttp.post(`/interventions/generate/${heatmapAnalysisId}`, {
            onSuccess: (data: any) => {
                recommendations.value = data.data;
                router.flash('message', 'Intervention recommendations generated successfully!');
            },
            onHttpException: (response: any) => {
                console.error('HTTP Error:', response.status);
                error.value = response.data?.error || 'Failed to generate intervention recommendations';
                router.flash('error', response.data?.error || 'Failed to generate intervention recommendations');
            },
            onNetworkError: (err: any) => {
                console.error('Network error:', err.message);
                error.value = 'Network error while generating intervention recommendations';
                router.flash('error', 'Network error while generating intervention recommendations');
            },
            onFinish: () => {
                isGeneratingRecommendations.value = false;
            }
        });
    };

    return {
        isGeneratingRecommendations,
        recommendations,
        error,
        generateRecommendations,
    };
}
