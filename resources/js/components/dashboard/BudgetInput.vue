<template>
    <div class="budget-input">
        <h3 class="text-lg font-semibold mb-3">Cooling Investment Budget</h3>
        <div class="flex gap-3">
            <div class="relative flex-1">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                <input
                    v-model="budget"
                    type="number"
                    min="0"
                    step="1000"
                    placeholder="Enter budget"
                    class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :disabled="loading"
                />
            </div>
            <button
                @click="handleOptimize"
                :disabled="loading || !budget"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
            >
                {{ loading ? 'Optimizing...' : 'Generate' }}
            </button>
        </div>
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{
    optimize: [budget: number];
}>();

const budget = ref<number | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

const handleOptimize = () => {
    if (!budget.value || budget.value <= 0) {
        error.value = 'Please enter a valid budget amount.';
        return;
    }

    error.value = null;
    emit('optimize', budget.value);
};

const setLoading = (isLoading: boolean) => {
    loading.value = isLoading;
};

const setError = (errorMessage: string | null) => {
    error.value = errorMessage;
};

defineExpose({
    setLoading,
    setError,
});
</script>
