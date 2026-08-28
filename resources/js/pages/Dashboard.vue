<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import GoogleMap from '@/components/GoogleMap.vue';
import HeatmapResultsCard from '@/components/dashboard/HeatmapResultsCard.vue';
import HeatAnalysisResultsCard from '@/components/dashboard/HeatAnalysisResultsCard.vue';
import EnvironmentalResultsCard from '@/components/dashboard/EnvironmentalResultsCard.vue';
import SatelliteResultsCard from '@/components/dashboard/SatelliteResultsCard.vue';
import PriorityScoreCard from '@/components/dashboard/PriorityScoreCard.vue';
import InterventionRecommendationCard from '@/components/dashboard/InterventionRecommendationCard.vue';
import AnalysisButtons from '@/components/dashboard/AnalysisButtons.vue';
import { dashboard } from '@/routes';
import { useHeatmapPolling } from '@/composables/useHeatmapPolling';
import { useEnvironmentalPolling } from '@/composables/useEnvironmentalPolling';
import { useSatellitePolling } from '@/composables/useSatellitePolling';
import { useHeatAnalysis } from '@/composables/useHeatAnalysis';
import { useEnvironmentalAnalysis } from '@/composables/useEnvironmentalAnalysis';
import { useSatelliteAnalysis } from '@/composables/useSatelliteAnalysis';
import { usePriorityScoring } from '@/composables/usePriorityScoring';
import { useInterventionRecommendations } from '@/composables/useInterventionRecommendations';
import { useBudgetOptimization } from '@/composables/useBudgetOptimization';
import BudgetInput from '@/components/dashboard/BudgetInput.vue';
import InvestmentPlanCard from '@/components/dashboard/InvestmentPlanCard.vue';

const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';
const page = usePage<any>();

const props = defineProps<{
    parks: Array<{
        id: number;
        park_id: string;
        name: string;
        property_type: string;
        park_type: string | null;
        acres: number | null;
        latitude: number | null;
        longitude: number | null;
        geometry: string | null;
    }>;
    heatAnalysisResults?: any[];
    heatmapResult?: any;
    environmentalResults?: any[];
    satelliteResults?: any[];
    priorityScores?: any[];
    interventionRecommendations?: any[];
    investmentPlan?: any;
}>();

const parksData = ref(props.parks);

// Heatmap polling - initialize with prop if available
const { currentActivityId, heatmapResult, handlePolygonSubmitted } = useHeatmapPolling(props.heatmapResult);

// Heat analysis - initialize with prop if available
const { heatAnalysisResults, isRunningAnalysis, runHeatAnalysis } = useHeatAnalysis(props.heatAnalysisResults);

// Environmental polling - initialize with prop if available
const { environmentalResults, environmentalSubmissions, startEnvironmentalPolling } = useEnvironmentalPolling(props.environmentalResults);

// Satellite polling - initialize with prop if available
const { satelliteResults, satelliteSubmissions, startSatellitePolling } = useSatellitePolling(props.satelliteResults);

// Environmental analysis
const { isRunningEnvironmentalAnalysis, runEnvironmentalAnalysis } = useEnvironmentalAnalysis();

// Satellite analysis
const { isRunningSatelliteAnalysis, runSatelliteAnalysis } = useSatelliteAnalysis();

// Priority scoring
const { isRunningPriorityScoring, priorityScores, calculatePriorityScores } = usePriorityScoring(props.priorityScores);

// Intervention recommendations
const { isGeneratingRecommendations, recommendations, generateRecommendations } = useInterventionRecommendations(props.interventionRecommendations);

// Budget optimization
const { loading: isOptimizingBudget, error: budgetError, result: optimizationResult, optimize: optimizeBudget } = useBudgetOptimization();

// Initialize with prop if available (for page refresh)
if (props.investmentPlan) {
    optimizationResult.value = {
        plan: props.investmentPlan,
        selected_options: props.investmentPlan.items,
        total_cost: props.investmentPlan.allocated_cost,
        remaining_budget: props.investmentPlan.remaining_budget,
        total_modeled_benefit: props.investmentPlan.total_modeled_benefit,
        modeled_priority_coverage: props.investmentPlan.modeled_priority_coverage,
    };
}

const handleRunEnvironmentalAnalysis = () => {
    runEnvironmentalAnalysis((submissions: any[]) => {
        environmentalSubmissions.value = submissions;
        submissions.forEach((submission: any) => {
            startEnvironmentalPolling(submission.activity_id, submission.park_id);
        });
    }, heatAnalysisResults.value);
};

const handleRunSatelliteAnalysis = () => {
    runSatelliteAnalysis((submissions: any[]) => {
        satelliteSubmissions.value = submissions;
        submissions.forEach((submission: any) => {
            startSatellitePolling(submission.activity_id, submission.park_id);
        });
    }, heatAnalysisResults.value);
};

const handleRunPriorityScoring = async () => {
    let heatmapAnalysisId = heatmapResult.value?.id;
    
    // If no heatmapResult, try to get ID from heat analysis results
    if (!heatmapAnalysisId && heatAnalysisResults.value && heatAnalysisResults.value.length > 0) {
        heatmapAnalysisId = heatAnalysisResults.value[0]?.heatmap_analysis_id;
    }
    
    if (!heatmapAnalysisId) {
        alert('Please run heatmap analysis first');
        return;
    }
    if (!heatAnalysisResults.value || heatAnalysisResults.value.length === 0) {
        alert('Please run heat analysis first');
        return;
    }
    try {
        await calculatePriorityScores(heatmapAnalysisId);
    } catch (err) {
        console.error('Priority scoring failed:', err);
    }
};

const handleRunInterventionRecommendations = async () => {
    let heatmapAnalysisId = heatmapResult.value?.id;
    
    // If no heatmapResult, try to get ID from heat analysis results
    if (!heatmapAnalysisId && heatAnalysisResults.value && heatAnalysisResults.value.length > 0) {
        heatmapAnalysisId = heatAnalysisResults.value[0]?.heatmap_analysis_id;
    }
    
    if (!heatmapAnalysisId) {
        alert('Please run heatmap analysis first');
        return;
    }
    if (!priorityScores.value || priorityScores.value.length === 0) {
        alert('Please calculate priority scores first');
        return;
    }
    try {
        await generateRecommendations(heatmapAnalysisId);
    } catch (err) {
        console.error('Intervention recommendations failed:', err);
    }
};

const handleBudgetOptimization = async (budget: number) => {
    let heatmapAnalysisId = heatmapResult.value?.id;
    
    // If no heatmapResult, try to get ID from heat analysis results
    if (!heatmapAnalysisId && heatAnalysisResults.value && heatAnalysisResults.value.length > 0) {
        heatmapAnalysisId = heatAnalysisResults.value[0]?.heatmap_analysis_id;
    }
    
    if (!heatmapAnalysisId) {
        alert('Please run heatmap analysis first');
        return;
    }
    await optimizeBudget(heatmapAnalysisId, budget);
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <!-- Flash Messages -->
        <div v-if="page.flash?.message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ page.flash.message }}
        </div>
        <div v-if="page.flash?.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ page.flash.error }}
        </div>

        <!-- Google Map -->
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <GoogleMap 
                :api-key="googleMapsApiKey" 
                :parks="parksData" 
                :heatmapGeoJson="heatmapResult?.map_data" 
                @polygon-submitted="handlePolygonSubmitted"
            />
        </div>

        <!-- Analysis Buttons -->
        <AnalysisButtons
            :is-running-analysis="isRunningAnalysis"
            :is-running-environmental-analysis="isRunningEnvironmentalAnalysis"
            :is-running-satellite-analysis="isRunningSatelliteAnalysis"
            :is-running-priority-scoring="isRunningPriorityScoring"
            :is-generating-recommendations="isGeneratingRecommendations"
            :has-heat-analysis-results="!!heatAnalysisResults"
            :has-priority-scores="!!priorityScores && priorityScores.length > 0"
            @run-heat-analysis="runHeatAnalysis"
            @run-environmental-analysis="handleRunEnvironmentalAnalysis"
            @run-satellite-analysis="handleRunSatelliteAnalysis"
            @run-priority-scoring="handleRunPriorityScoring"
            @run-intervention-recommendations="handleRunInterventionRecommendations"
        />

        <!-- Heatmap Result Card -->
        <HeatmapResultsCard :heatmap-result="heatmapResult" />

        <!-- Park Heat Analysis Results -->
        <HeatAnalysisResultsCard :heat-analysis-results="heatAnalysisResults" />

        <!-- Environmental Analysis Results -->
        <EnvironmentalResultsCard :environmental-results="environmentalResults" />

        <!-- Satellite Analysis Results -->
        <SatelliteResultsCard :satellite-results="satelliteResults" />

        <!-- Priority Scores -->
        <PriorityScoreCard :priority-scores="priorityScores" />

        <!-- Intervention Recommendations -->
        <InterventionRecommendationCard :recommendations="recommendations" />

        <!-- Budget Optimization -->
        <BudgetInput 
            @optimize="handleBudgetOptimization"
            ref="budgetInputRef"
        />

        <!-- Investment Plan Results -->
        <InvestmentPlanCard
            v-if="optimizationResult"
            :selected-options="optimizationResult.selected_options"
            :total-cost="optimizationResult.total_cost"
            :remaining-budget="optimizationResult.remaining_budget"
            :coverage="optimizationResult.modeled_priority_coverage"
        />

        <!-- Budget Error -->
        <div v-if="budgetError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ budgetError }}
        </div>

        <!-- Processing Status -->
        <div v-if="currentActivityId && !heatmapResult" class="flex items-center gap-3 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-4 rounded-lg shadow-sm">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="font-medium">Generating heatmap data for the selected area... This may take a moment.</span>
        </div>
    </div>
</template>

