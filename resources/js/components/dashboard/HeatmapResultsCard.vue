<script setup lang="ts">
defineProps<{
    heatmapResult: any;
}>();
</script>

<template>
    <div v-if="heatmapResult" class="bg-white border border-sidebar-border/70 rounded-xl p-6 dark:border-sidebar-border">
        <h3 class="text-lg font-semibold mb-4">Heatmap Results</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <h4 class="font-medium text-sm text-gray-500 mb-2">Map Data</h4>
                <pre class="bg-gray-100 p-3 rounded text-xs overflow-auto max-h-40">{{ JSON.stringify(heatmapResult.map_data, null, 2) }}</pre>
            </div>
            <div>
                <h4 class="font-medium text-sm text-gray-500 mb-2">Stats Data</h4>
                <pre class="bg-gray-100 p-3 rounded text-xs overflow-auto max-h-40">{{ JSON.stringify(heatmapResult.stats_data, null, 2) }}</pre>
            </div>
        </div>
    </div>
</template>
