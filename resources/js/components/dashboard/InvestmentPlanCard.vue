<template>
    <div class="investment-plan-card bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-semibold mb-4">Recommended Investment</h3>
        
        <!-- Summary Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600">Allocated</p>
                <p class="text-2xl font-bold text-blue-600">${{ formatNumber(totalCost) }}</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600">Remaining</p>
                <p class="text-2xl font-bold text-green-600">${{ formatNumber(remainingBudget) }}</p>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600">Parks Funded</p>
                <p class="text-2xl font-bold text-purple-600">{{ selectedOptions.length }}</p>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600">Coverage</p>
                <p class="text-2xl font-bold text-orange-600">{{ coverage }}%</p>
            </div>
        </div>

        <!-- Selected Interventions -->
        <div v-if="selectedOptions.length > 0" class="space-y-4">
            <div 
                v-for="option in selectedOptions" 
                :key="`${option.park_id}-${option.intervention_type}`"
                class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
            >
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h4 class="font-semibold text-gray-800">{{ option.park_name }}</h4>
                        <p class="text-sm text-gray-600">{{ getInterventionName(option.intervention_type) }}</p>
                    </div>
                    <p class="font-bold text-lg text-blue-600">${{ formatNumber(option.total_cost) }}</p>
                </div>
                
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <span v-if="option.scenario" class="bg-gray-100 px-2 py-1 rounded">
                        {{ option.scenario.charAt(0).toUpperCase() + option.scenario.slice(1) }}
                    </span>
                    <span>{{ option.quantity }} {{ option.unit }}</span>
                    <span>Benefit: {{ Number(option.modeled_benefit).toFixed(1) }}</span>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8 text-gray-500">
            <p>No interventions selected within the budget.</p>
            <p class="text-sm">Try increasing the budget amount.</p>
        </div>

        <!-- Assumption Disclaimer -->
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">
                <strong>Note:</strong> Costs are based on Phoenix planning references and are estimates. 
                Modeled benefits are calculated using priority scores and scale factors.
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface InvestmentOption {
    park_id: number;
    park_name: string;
    intervention_type: string;
    scenario: string | null;
    quantity: number;
    unit: string;
    total_cost: number;
    modeled_benefit: number;
}

interface Props {
    selectedOptions: InvestmentOption[];
    totalCost: number;
    remainingBudget: number;
    coverage: number;
}

const props = defineProps<Props>();

const formatNumber = (num: number): string => {
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};

const getInterventionName = (type: string): string => {
    const names: Record<string, string> = {
        tree_planting: '🌳 Tree Planting',
        cool_pavement: '🛣 Cool Pavement',
        ramada: '⛱ Ramada / Built Shade',
    };
    return names[type] || type;
};
</script>
