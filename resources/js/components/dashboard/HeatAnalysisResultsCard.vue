<script setup lang="ts">
defineProps<{
    heatAnalysisResults: any;
}>();
</script>

<template>
    <div v-if="heatAnalysisResults" class="bg-white border border-sidebar-border/70 rounded-xl p-6 dark:border-sidebar-border">
        <h3 class="text-lg font-semibold mb-4">Top 3 Hottest Parks</h3>
        <div class="space-y-3">
            <div 
                v-for="(item, index) in heatAnalysisResults" 
                :key="item.id"
                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
            >
                <div class="flex items-center gap-4">
                    <span class="text-2xl font-bold text-blue-600">{{ Number(index) + 1 }}</span>
                    <div>
                        <h4 class="font-medium">{{ item.park?.name || 'Unknown Park' }}</h4>
                        <p class="text-sm text-gray-500">{{ item.matched_tile_count }} tiles analyzed</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-red-600">{{ item.average_temperature }}°C</div>
                    <div class="text-sm text-gray-500">
                        Min: {{ item.min_temperature }}°C | Max: {{ item.max_temperature }}°C
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
