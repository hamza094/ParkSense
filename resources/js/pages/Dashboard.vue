<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, watch, onUnmounted, onMounted } from 'vue';
import { router, useHttp, usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import GoogleMap from '@/components/GoogleMap.vue';
import { dashboard } from '@/routes';

// Google Maps API key from environment
const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';

const page = usePage<any>();

// Receive parks data from Inertia
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
}>();

// Store parks data in reactive ref for easy manipulation
const parksData = ref(props.parks);
const currentActivityId = ref<string | null>(null);
const heatmapStatus = ref<any>(null);
const heatmapResult = ref<any>(null);
const pollingInterval = ref<number | null>(null);

// Initialize useHttp for polling status
const pollHttp = useHttp({});

// Experiment code (commented out - for testing only)
/*
// Load cached data on mount for practice
onMounted(() => {
    if (props.cachedHeatmap?.cached && props.cachedHeatmap?.data) {
        console.log('✅ PRACTICE: Loading cached heatmap data');
        console.log('Cached data:', props.cachedHeatmap.data);
        
        const result = props.cachedHeatmap.data.data?.result;
        if (result) {
            heatmapResult.value = result;
            heatmapStatus.value = props.cachedHeatmap.data;
            currentActivityId.value = props.cachedHeatmap.data.data?.activity_id;
            console.log('✅ PRACTICE: Heatmap result loaded from cache');
        }
    } else {
        console.log('⚠️ PRACTICE: No cached heatmap data found');
    }
});
*/

// Original polling code (restored)
const handlePolygonSubmitted = (activityId: string) => {
    console.log('✅ SUCCESS: Polygon submitted with activity ID:', activityId);
    currentActivityId.value = activityId;
    startPolling(activityId);
    // Show success message
    router.flash('message', 'Heatmap submitted successfully. Processing...');
};

const startPolling = (activityId: string) => {
    // Clear any existing interval
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
    
    // Poll every 3 seconds
    pollingInterval.value = window.setInterval(() => {
        checkHeatmapStatus(activityId);
    }, 3000);
    
    // Initial check
    checkHeatmapStatus(activityId);
};

const checkHeatmapStatus = (activityId: string) => {
    console.log('🔄 POLLING: Checking heatmap status for activity ID:', activityId);
    
    pollHttp.get('/parks/heatmap-status/' + activityId, {
        onSuccess: (data: any) => {
            console.log('✅ SUCCESS: Heatmap status check successful');
            console.log('Response data:', data);
            heatmapStatus.value = data;
            
            const status = data?.data?.status;
            console.log('📊 STATUS:', status);
            
            if (status === 'Completed' || status === 'Failed') {
                console.log('🏁 POLLING COMPLETE: Final status is', status);
                
                if (status === 'Completed' && data?.data?.result) {
                    console.log('✅ SUCCESS: Heatmap processing completed successfully');
                    console.log('Heatmap result:', data.data.result);
                    // Store the result data for display
                    heatmapResult.value = data.data.result;
                    // Show success flash message
                    router.flash('message', 'Heatmap processing completed!');
                } else if (status === 'Failed') {
                    console.error('❌ FAILURE: Heatmap processing failed');
                    console.error('Failure details:', data);
                    router.flash('error', 'Heatmap processing failed.');
                }
                
                stopPolling();
            } else {
                console.log('⏳ CONTINUING: Heatmap still processing, will poll again...');
            }
        },
        onHttpException: (response: any) => {
            console.error('❌ FAILURE: HTTP error while checking heatmap status');
            console.error('HTTP Status:', response.status);
            console.error('Response data:', response.data);
            router.flash('error', 'Failed to check heatmap status.');
            stopPolling(); // IMPORTANT: stop polling if there is a server error!
        },
        onNetworkError: (error: any) => {
            console.error('❌ FAILURE: Network error while checking heatmap status');
            console.error('Network error:', error.message);
            router.flash('error', 'Network error while checking heatmap status.');
            stopPolling(); // IMPORTANT: stop polling if there is a network error!
        }
    });
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
    currentActivityId.value = null;
    pollHttp.cancel(); // Cancel any in-progress HTTP requests as per the docs!
};

// Stop polling when component unmounts
onUnmounted(() => {
    stopPolling();
});

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

        <!-- Heatmap Result Card -->
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
