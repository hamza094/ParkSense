import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

interface InvestmentOption {
    park_id: number;
    park_name: string;
    intervention_type: string;
    scenario: string | null;
    quantity: number;
    unit: string;
    unit_cost: number;
    total_cost: number;
    modeled_benefit: number;
    cost_source: string | null;
    cost_is_assumption: boolean;
    benefit_is_assumption: boolean;
}

interface OptimizationResult {
    plan: any;
    selected_options: InvestmentOption[];
    total_cost: number;
    remaining_budget: number;
    total_modeled_benefit: number;
    modeled_priority_coverage: number;
}

export function useBudgetOptimization() {
    const http = useHttp({});
    
    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<OptimizationResult | null>(null);

    const optimizeBudget = (heatmapAnalysisId: number, budget: number) => {
        loading.value = true;
        error.value = null;
        result.value = null;

        http.post(`/investments/optimize/${heatmapAnalysisId}?budget=${budget}`, {
            onSuccess: (data: any) => {
                router.flash('message', 'Investment plan optimized successfully!');
                router.reload();
            },
            onHttpException: (response: any) => {
                console.error('HTTP Error:', response.status);
                error.value = response.data?.message || 'Failed to optimize investment plan.';
                router.flash('error', response.data?.message || 'Failed to optimize investment plan.');
            },
            onNetworkError: (err: any) => {
                console.error('Network error:', err.message);
                error.value = 'Network error while optimizing investment plan.';
                router.flash('error', 'Network error while optimizing investment plan.');
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    const reset = () => {
        loading.value = false;
        error.value = null;
        result.value = null;
    };

    return {
        loading,
        error,
        result,
        optimizeBudget,
        reset,
    };
}
