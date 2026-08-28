<script setup lang="ts">
defineProps<{
    isRunningAnalysis: boolean;
    isRunningEnvironmentalAnalysis: boolean;
    isRunningSatelliteAnalysis: boolean;
    isRunningPriorityScoring: boolean;
    isGeneratingRecommendations: boolean;
    hasHeatAnalysisResults: boolean;
    hasPriorityScores: boolean;
}>();

const emit = defineEmits<{
    runHeatAnalysis: [];
    runEnvironmentalAnalysis: [];
    runSatelliteAnalysis: [];
    runPriorityScoring: [];
    runInterventionRecommendations: [];
}>();
</script>

<template>
    <div class="bg-white border border-sidebar-border/70 rounded-xl p-6 dark:border-sidebar-border">
        <button 
            @click="emit('runHeatAnalysis')"
            :disabled="isRunningAnalysis"
            class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-lg font-medium w-full mb-3"
        >
            {{ isRunningAnalysis ? 'Running Analysis...' : 'Run Park Heat Analysis' }}
        </button>
        <button 
            @click="emit('runEnvironmentalAnalysis')"
            :disabled="isRunningEnvironmentalAnalysis || !hasHeatAnalysisResults"
            class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-lg font-medium w-full mb-3"
        >
            {{ isRunningEnvironmentalAnalysis ? 'Running Environmental Analysis...' : 'Get Environmental Parameters' }}
        </button>
        <button 
            @click="emit('runSatelliteAnalysis')"
            :disabled="isRunningSatelliteAnalysis || !hasHeatAnalysisResults"
            class="bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-lg font-medium w-full mb-3"
        >
            {{ isRunningSatelliteAnalysis ? 'Running Satellite Analysis...' : 'Get Satellite Segmentation' }}
        </button>
        <button 
            @click="emit('runPriorityScoring')"
            :disabled="isRunningPriorityScoring || !hasHeatAnalysisResults"
            class="bg-orange-600 hover:bg-orange-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-lg font-medium w-full mb-3"
        >
            {{ isRunningPriorityScoring ? 'Calculating Priority Scores...' : 'Calculate Priority Scores' }}
        </button>
        <button 
            @click="emit('runInterventionRecommendations')"
            :disabled="isGeneratingRecommendations || !hasPriorityScores"
            class="bg-teal-600 hover:bg-teal-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-lg font-medium w-full"
        >
            {{ isGeneratingRecommendations ? 'Generating Recommendations...' : 'Generate Intervention Recommendations' }}
        </button>
    </div>
</template>
