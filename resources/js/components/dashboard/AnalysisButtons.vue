<script setup lang="ts">
defineProps<{
    isRunningAnalysis: boolean;
    isRunningEnvironmentalAnalysis: boolean;
    hasHeatAnalysisResults: boolean;
}>();

const emit = defineEmits<{
    runHeatAnalysis: [];
    runEnvironmentalAnalysis: [];
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
            class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-lg font-medium w-full"
        >
            {{ isRunningEnvironmentalAnalysis ? 'Running Environmental Analysis...' : 'Get Environmental Parameters' }}
        </button>
    </div>
</template>
