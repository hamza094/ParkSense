<script setup lang="ts">
const props = defineProps<{
    priorityScores: any[];
}>();
</script>

<template>
    <div v-if="priorityScores && priorityScores.length > 0" class="bg-white border border-sidebar-border/70 rounded-xl p-6 dark:border-sidebar-border">
        <h3 class="text-lg font-semibold mb-4">Park Priority Scores</h3>
        <div class="space-y-4">
            <div 
                v-for="(item, index) in priorityScores" 
                :key="item.id"
                class="p-4 bg-gray-50 rounded-lg"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-4">
                        <span class="text-2xl font-bold text-blue-600">{{ Number(index) + 1 }}</span>
                        <div>
                            <h4 class="font-medium">{{ item.park_name || 'Unknown Park' }}</h4>
                            <p class="text-sm text-gray-500">Model: {{ item.model_version }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-red-600">{{ item.priority_score.toFixed(1) }}</div>
                        <div class="text-sm text-gray-500">Priority Score</div>
                    </div>
                </div>
                
                <!-- Component Scores Breakdown -->
                <div class="grid grid-cols-5 gap-2 text-xs">
                    <div class="text-center">
                        <div class="font-medium text-gray-600">Heat</div>
                        <div class="text-orange-600 font-semibold">{{ item.heat_severity?.toFixed(1) || '-' }}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-gray-600">Env</div>
                        <div class="text-green-600 font-semibold">{{ item.environmental_stress?.toFixed(1) || '-' }}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-gray-600">Physical</div>
                        <div class="text-blue-600 font-semibold">{{ item.physical_condition?.toFixed(1) || '-' }}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-gray-600">Importance</div>
                        <div class="text-purple-600 font-semibold">{{ item.park_importance?.toFixed(1) || '-' }}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-gray-600">Opportunity</div>
                        <div class="text-teal-600 font-semibold">{{ item.intervention_opportunity?.toFixed(1) || '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="priorityScores.length === 0" class="text-center py-8 text-gray-500">
            No priority scores calculated yet
        </div>
    </div>
</template>
