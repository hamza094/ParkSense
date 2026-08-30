<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowRight, Loader2 } from '@lucide/vue';
import GoogleMap from '@/components/GoogleMap.vue';
import { useHeatmapPolling } from '@/composables/useHeatmapPolling';
import { dashboard } from '@/routes';

const page = usePage<any>();
const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';

const props = defineProps<{
    parks: Array<{
        id: number;
        park_id: string;
        name: string;
        property_type: string | null;
        park_type: string | null;
        acres: number | null;
        latitude: number | null;
        longitude: number | null;
        geometry: string | null;
    }>;
    heatAnalyses: Array<{
        id: number;
        park_id: number | null;
        park_name: string;
        created_at: string;
        status: string;
        tile_count: number;
    }>;
}>();

const parksData = ref(props.parks);

// Heatmap polling
const { currentActivityId, heatmapResult, handlePolygonSubmitted } = useHeatmapPolling();

const navigateToHeatAnalysis = (id: number) => {
    router.visit(`/heat-analyses/${id}`);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
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

    <div class="container mx-auto py-6 px-4">
        <!-- Flash Messages -->
        <div v-if="page.flash?.message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ page.flash.message }}
        </div>
        <div v-if="page.flash?.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ page.flash.error }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Google Map Section -->
            <Card>
                <CardHeader>
                    <CardTitle>Draw Analysis Area</CardTitle>
                    <CardDescription>Draw a polygon on the map to create a new heat analysis</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="relative min-h-[400px] rounded-lg border border-sidebar-border/70">
                        <GoogleMap 
                            :api-key="googleMapsApiKey"
                            :parks="parksData" 
                            @polygon-submitted="handlePolygonSubmitted"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Heat Analyses List Section -->
            <Card>
                <CardHeader>
                    <CardTitle>Heat Analyses</CardTitle>
                    <CardDescription>View and manage your heat analysis projects</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="heatAnalyses.length > 0" class="space-y-3">
                        <div 
                            v-for="analysis in heatAnalyses" 
                            :key="analysis.id"
                            class="border rounded-lg p-4 hover:bg-accent cursor-pointer transition-colors"
                            @click="navigateToHeatAnalysis(analysis.id)"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold">Heat Analysis #{{ analysis.id }}</h3>
                                <Badge :variant="analysis.status?.toLowerCase() === 'completed' ? 'default' : 'secondary'">
                                    {{ analysis.status }}
                                </Badge>
                            </div>
                            <div class="text-sm text-muted-foreground space-y-1">
                                <div>Park: {{ analysis.park_name }}</div>
                                <div>Date: {{ formatDate(analysis.created_at) }}</div>
                                <div>Tiles: {{ analysis.tile_count }}</div>
                            </div>
                            <div class="mt-3 flex items-center text-sm text-primary">
                                View Details
                                <ArrowRight class="ml-1 h-4 w-4" />
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-8 text-muted-foreground">
                        <p>No heat analyses found.</p>
                        <p class="text-sm mt-1">Draw a polygon on the map to create your first analysis.</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Processing Status -->
        <div v-if="currentActivityId && !heatmapResult" class="mt-6 flex items-center gap-3 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-4 rounded-lg shadow-sm">
            <Loader2 class="h-5 w-5 animate-spin" />
            <span class="font-medium">Generating heatmap data for the selected area... This may take a moment.</span>
        </div>
    </div>
</template>