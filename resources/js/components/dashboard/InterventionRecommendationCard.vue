<script setup lang="ts">
const props = defineProps<{
    recommendations: any[];
}>();

const formatCost = (cost: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(cost);
};

const getCategoryIcon = (category: string) => {
    switch (category) {
        case 'vegetation':
            return '🌳';
        case 'shade':
            return '⛱';
        case 'surface':
            return '🏗';
        default:
            return '🔧';
    }
};
</script>

<template>
    <div v-if="recommendations && recommendations.length > 0" class="bg-white border border-sidebar-border/70 rounded-xl p-6 dark:border-sidebar-border">
        <h3 class="text-lg font-semibold mb-2">Recommended Cooling Actions</h3>
        <p class="text-xs text-gray-500 mb-4">Planning scenario based on Phoenix-published cost references</p>
        
        <div v-for="(parkData, index) in recommendations" :key="parkData.park.id" class="mb-6 last:mb-0">
            <div class="flex items-center justify-between mb-3 pb-3 border-b border-gray-200">
                <div>
                    <h4 class="font-semibold text-lg">{{ parkData.park.name }}</h4>
                    <p class="text-sm text-gray-500">Priority Score: {{ parkData.priority_score.toFixed(1) }}/100</p>
                </div>
            </div>
            
            <div class="space-y-3">
                <div 
                    v-for="rec in parkData.recommendations" 
                    :key="rec.id"
                    class="p-4 bg-gray-50 rounded-lg border-l-4 border-blue-500"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-2xl">{{ getCategoryIcon(rec.category) }}</span>
                                <div>
                                    <h5 class="font-semibold">{{ rec.name }}</h5>
                                    <span v-if="rec.scenario" class="text-xs text-blue-600 font-medium bg-blue-50 px-2 py-0.5 rounded">
                                        {{ rec.scenario.charAt(0).toUpperCase() + rec.scenario.slice(1) }}
                                    </span>
                                    <span v-if="rec.quantity && rec.unit" class="text-sm text-gray-600 ml-2">
                                        {{ rec.quantity }} {{ rec.unit }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ rec.justification }}</p>
                            <div class="text-xs text-gray-500 mb-2">
                                <span class="font-medium">Rule:</span> {{ rec.rule }}
                            </div>
                            <div v-if="rec.source" class="text-xs text-gray-400 italic">
                                <a v-if="rec.source_url" :href="rec.source_url" target="_blank" class="text-blue-500 hover:underline">
                                    Source: {{ rec.source }} ↗
                                </a>
                                <span v-else>Source: {{ rec.source }}</span>
                            </div>
                        </div>
                        <div class="text-right ml-4">
                            <div class="text-lg font-bold text-green-600">
                                {{ formatCost(rec.upfront_cost) }}
                            </div>
                            <p class="text-xs text-gray-500">Upfront cost</p>
                            <div v-if="rec.category === 'shade'" class="text-xs text-gray-400 mt-1">
                                Phoenix range: $40k-$80k
                            </div>
                            <div v-if="rec.category === 'surface'" class="text-xs text-gray-400 mt-1 italic">
                                Estimated planning scenario
                            </div>
                            <div v-if="rec.annual_maintenance_cost || rec.annual_water_cost" class="mt-2 text-xs">
                                <div v-if="rec.annual_maintenance_cost" class="text-gray-600">
                                    Maintenance reference: {{ formatCost(rec.annual_maintenance_cost) }}/year
                                </div>
                                <div v-if="rec.annual_water_cost" class="text-gray-600">
                                    Water reference: {{ formatCost(rec.annual_water_cost) }}/year
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="recommendations.length === 0" class="text-center py-8 text-gray-500">
            No intervention recommendations available
        </div>
    </div>
</template>
