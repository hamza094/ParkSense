<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Loader2, Leaf, Satellite, Star, Lightbulb, DollarSign, Info as InfoIcon, AlertTriangle } from '@lucide/vue';
import GoogleMap from '@/components/GoogleMap.vue';
import { useAnalysisOrchestrator } from '@/composables/useAnalysisOrchestrator';
import { useBudgetOptimization } from '@/composables/useBudgetOptimization';
import { useEnvironmentalAnalysis } from '@/composables/useEnvironmentalAnalysis';
import { useSatelliteAnalysis } from '@/composables/useSatelliteAnalysis';
import { usePriorityScoring } from '@/composables/usePriorityScoring';
import { useInterventionRecommendations } from '@/composables/useInterventionRecommendations';

const props = defineProps<{
    heatAnalysis: {
        id: number;
        park_id: number | null;
        park_name: string | null;
        created_at: string;
        status: string;
        activity_id: string;
        has_heatmap_data: boolean;
    };
    heatmapGeoJson?: {
        type: string;
        features: Array<{
            id: string;
            type: string;
            properties: {
                tile_id: number;
                average_temperature: number;
                min_temperature: number;
                max_temperature: number;
            };
            geometry: {
                type: string;
                coordinates: number[][][];
            };
        }>;
    };
    parks: Array<{
        id: number;
        park_id: string;
        name: string;
        property_type: string | null;
        park_type: string | null;
        acres: number | null;
        geometry: string | null;
        latitude: number | null;
        longitude: number | null;
    }>;
    environmentalResults: any[];
    satelliteResults: any[];
    priorityScores: any[];
    priorityScoringOverview?: {
        title: string;
        description: string;
        factors: Array<{
            name: string;
            description: string;
            weight: string;
            range: string;
        }>;
        calculation_note: string;
    };
    interventionRecommendations: any[];
    interventionRecommendationsOverview?: {
        title: string;
        description: string;
        factors: Array<{
            name: string;
            description: string;
            weight: string;
            range: string;
        }>;
        calculation_note: string;
    };
    budgetOptimizationOverview?: {
        title: string;
        description: string;
        factors: Array<{
            name: string;
            description: string;
            weight: string;
            range: string;
        }>;
        calculation_note: string;
    };
    investmentPlan: any;
}>();

const budget = ref<number>(1500000); // Phoenix NPEP reference budget
const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';
const heatmapGeoJson = ref(props.heatmapGeoJson || null);
const showBudgetMethodology = ref(true);
const showInterventionMethodology = ref(true);
const showEnvironmentalMethodology = ref(true);
const showSatelliteMethodology = ref(true);
const showPriorityMethodology = ref(true);

// Budget scenarios based on Phoenix funding references
const budgetScenarios = [
    { name: 'Small Project', amount: 250000, description: 'Good for testing with smaller budgets' },
    { name: 'Medium Investment', amount: 500000, description: 'Moderate budget for several park improvements' },
    { name: 'Phoenix NPEP Reference', amount: 1500000, description: 'Based on Phoenix\'s $1.5M neighborhood park program' },
];

const setBudgetScenario = (amount: number) => {
    budget.value = amount;
};

// Helper function to calculate average from array of values
const getAverageValue = (values: any) => {
  if (!values) return 'N/A';
  if (Array.isArray(values)) {
    const sum = values.reduce((acc: number, val: number) => acc + (val || 0), 0);
    return (sum / values.length).toFixed(2);
  }
  return values.toFixed ? values.toFixed(2) : values;
};

// Composables
const { 
    currentStep, 
    stepStatus, 
    runSequentialAnalysis,
    getProgressPercentage,
    getStepLabel,
    environmentalLoading,
    satelliteLoading,
    priorityLoading,
    interventionLoading,
    cancelAllPolling
} = useAnalysisOrchestrator();

const { runEnvironmentalAnalysis, loading: envLoading, processingParks: envProcessingParks } = useEnvironmentalAnalysis();
const { runSatelliteAnalysis, loading: satLoading, processingParks: satProcessingParks } = useSatelliteAnalysis();
const { calculatePriorityScores, loading: prioLoading } = usePriorityScoring();
const { generateRecommendations, loading: intLoading } = useInterventionRecommendations();
const { optimizeBudget, loading: budgetLoading } = useBudgetOptimization();
const http = useHttp({});

// Handlers
const handleRunEnvironmental = async () => {
    stepStatus.value.environmental = 'loading';
    try {
        await runEnvironmentalAnalysis(props.heatAnalysis.id);
        stepStatus.value.environmental = 'completed';
        router.reload();
    } catch (error) {
        stepStatus.value.environmental = 'failed';
        console.error('Environmental analysis failed:', error);
    }
};

const handleRunSatellite = async () => {
    stepStatus.value.satellite = 'loading';
    try {
        await runSatelliteAnalysis(props.heatAnalysis.id);
        stepStatus.value.satellite = 'completed';
        router.reload();
    } catch (error) {
        stepStatus.value.satellite = 'failed';
        console.error('Satellite analysis failed:', error);
    }
};

const handleCalculatePriority = async () => {
    stepStatus.value.priority = 'loading';
    try {
        await calculatePriorityScores(props.heatAnalysis.id);
        stepStatus.value.priority = 'completed';
        router.reload();
    } catch (error) {
        stepStatus.value.priority = 'failed';
        console.error('Priority scoring failed:', error);
    }
};

const handleGenerateRecommendations = async () => {
    stepStatus.value.interventions = 'loading';
    try {
        await generateRecommendations(props.heatAnalysis.id);
        stepStatus.value.interventions = 'completed';
        router.reload();
    } catch (error) {
        stepStatus.value.interventions = 'failed';
        console.error('Intervention recommendations failed:', error);
    }
};

const handleRunAllAnalyses = async () => {
    try {
        await runSequentialAnalysis(props.heatAnalysis.id);
    } catch (error) {
        console.error('Sequential analysis failed:', error);
    }
};

const handleOptimizeBudget = () => {
    optimizeBudget(props.heatAnalysis.id, budget.value);
};

// Auto-run analysis on component mount
onMounted(async () => {
    // Fetch GeoJSON data asynchronously to avoid Inertia timeout
    if (props.heatAnalysis.has_heatmap_data && !heatmapGeoJson.value) {
        try {
            const response = await fetch(`/api/heat-analyses/${props.heatAnalysis.id}/geojson`);
            if (response.ok) {
                heatmapGeoJson.value = await response.json();
            }
        } catch (error) {
            console.error('Failed to load GeoJSON:', error);
        }
    }

    // Don't auto-run - let user manually trigger analysis using the buttons
    // This gives users control and prevents unexpected background processing
});

// Cleanup polling when component unmounts
onUnmounted(() => {
    cancelAllPolling();
});

const runParkHeatAnalysis = async (heatmapAnalysisId: number) => {
    return new Promise((resolve) => {
        http.post(`/parks/run-heat-analysis?heatmap_analysis_id=${heatmapAnalysisId}`, {
            onSuccess: (data: any) => {
                console.log('Park heat analysis completed:', data);
                resolve(data);
            },
            onHttpException: (response: any) => {
                console.error('Park heat analysis failed:', response.status);
                console.error('Error data:', response.data);
                // Don't throw - we'll try to proceed anyway
                resolve(null);
            },
            onNetworkError: (error: any) => {
                console.error('Park heat analysis network error:', error.message);
                // Don't throw - we'll try to proceed anyway
                resolve(null);
            }
        });
    });
};

const goBack = () => {
    router.visit('/dashboard');
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const formatEvidenceType = (evidenceType: string) => {
    const typeMap: Record<string, string> = {
        'research_reference': 'Research Reference',
        'measured_reference': 'Measured Reference',
        'planning_assumption': 'Planning Assumption',
    };
    return typeMap[evidenceType] || 'Unknown';
};

// Helper functions for satellite segmentation data
const calculateVegetationPercent = (segments: any) => {
    if (!segments) return 'N/A';
    
    const vegetationClasses = ['tree', 'plant', 'grass'];
    let vegetation = 0;
    
    for (const [className, percentage] of Object.entries(segments)) {
        const classLower = className.toLowerCase();
        for (const vegClass of vegetationClasses) {
            if (classLower.includes(vegClass.toLowerCase())) {
                vegetation += parseFloat(percentage) || 0;
                break;
            }
        }
    }
    
    return vegetation.toFixed(1);
};

const calculateHardSurfacePercent = (segments: any) => {
    if (!segments) return 'N/A';
    
    const hardSurfaceClasses = ['building', 'road', 'route'];
    let hardSurface = 0;
    
    for (const [className, percentage] of Object.entries(segments)) {
        const classLower = className.toLowerCase();
        for (const surfaceClass of hardSurfaceClasses) {
            if (classLower.includes(surfaceClass.toLowerCase())) {
                hardSurface += parseFloat(percentage) || 0;
                break;
            }
        }
    }
    
    return hardSurface.toFixed(1);
};
</script>

<template>
    <Head title="Heat Analysis Detail" />
    
    <div class="container mx-auto py-6 px-4">
        <!-- Header -->
        <div class="mb-6">
            <Button variant="ghost" @click="goBack" class="mb-4">
                <ArrowLeft class="mr-2 h-4 w-4" />
                Back to Dashboard
            </Button>
            
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Heat Analysis #{{ heatAnalysis.id }}</h1>
                    <div class="flex items-center gap-4 mt-2 text-sm text-muted-foreground">
                        <span>Created: {{ formatDate(heatAnalysis.created_at) }}</span>
                        <span>•</span>
                        <span>Park: {{ heatAnalysis.park_name || 'Multiple Parks' }}</span>
                        <span>•</span>
                        <Badge :variant="heatAnalysis.status?.toLowerCase() === 'completed' ? 'default' : 'secondary'">
                            {{ heatAnalysis.status }}
                        </Badge>
                    </div>
                </div>
                
                <!-- Run All Analyses Button - Only show if no data exists at all -->
                <Button 
                    v-if="parks.length > 0 && environmentalResults.length === 0 && satelliteResults.length === 0 && priorityScores.length === 0 && interventionRecommendations.length === 0"
                    @click="handleRunAllAnalyses"
                    :disabled="currentStep !== ''"
                    variant="default"
                    class="bg-indigo-600 hover:bg-indigo-700"
                >
                    <Loader2 v-if="currentStep" class="mr-2 h-4 w-4 animate-spin" />
                    {{ currentStep ? 'Running Analyses...' : 'Run All Analyses' }}
                </Button>
            </div>
        </div>

        <!-- Progress Indicator -->
        <div v-if="currentStep" class="mb-6">
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-4">
                        <Loader2 class="h-5 w-5 animate-spin text-blue-600" />
                        <div class="flex-1">
                            <div class="flex justify-between text-sm mb-2">
                                <span>Processing Analysis</span>
                                <span>{{ getStepLabel(currentStep) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all" 
                                     :style="{ width: getProgressPercentage() + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- No Parks Warning -->
        <div v-if="parks.length === 0" class="mb-6">
            <Card class="border-red-200 bg-red-50 dark:bg-red-950/20 dark:border-red-900">
                <CardContent class="pt-6">
                    <div class="flex items-start gap-4">
                        <div class="p-2 bg-red-100 rounded-full dark:bg-red-900/50">
                            <AlertTriangle class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-red-800 dark:text-red-300">No Parks Found in Selected Area</h3>
                            <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                                The heatmap area you drew does not intersect with any registered parks. Because there are no parks, the environmental analysis and scoring cannot proceed. Please go back to the dashboard and draw a polygon that covers at least one park.
                            </p>
                            <Button @click="goBack" variant="outline" class="mt-4 border-red-300 text-red-700 hover:bg-red-100 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/50">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Return to Dashboard
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Map Section -->
        <Card class="mb-6">
            <CardHeader>
                <CardTitle>Heatmap Map</CardTitle>
                <CardDescription>Selected heatmap tiles for this analysis</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="relative min-h-[400px] rounded-lg border border-sidebar-border/70">
                    <GoogleMap 
                        :api-key="googleMapsApiKey"
                        :parks="parks"
                        :heatmap-geo-json="heatmapGeoJson"
                        :readonly="true"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Environmental Analysis Section -->
        <Card class="mb-6">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Leaf class="h-5 w-5 text-green-600" />
                    Environmental Analysis
                </CardTitle>
                <CardDescription>Analyze environmental factors for selected parks</CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Toggle Methodology Button -->
                <Button 
                    @click="showEnvironmentalMethodology = !showEnvironmentalMethodology"
                    variant="outline"
                    class="mb-4"
                >
                    <InfoIcon class="mr-2 h-4 w-4" />
                    {{ showEnvironmentalMethodology ? 'Hide' : 'Show' }} Environmental Analysis Methodology
                </Button>
                
                <!-- Environmental Analysis Methodology Overview -->
                <div v-if="showEnvironmentalMethodology" class="mb-6 p-4 bg-green-50 dark:bg-green-950 rounded-lg border border-green-200 dark:border-green-800">
                    <h4 class="font-semibold text-green-900 dark:text-green-100 mb-3 flex items-center gap-2">
                        <InfoIcon class="h-4 w-4" />
                        Environmental Analysis Methodology
                    </h4>
                    <p class="text-sm text-green-800 dark:text-green-200 mb-4">
                        Environmental analysis uses FortyGuard API to gather real-time and historical environmental data:
                    </p>
                    <ul class="text-sm text-green-800 dark:text-green-200 list-disc list-inside space-y-2">
                        <li><strong>Heat Index:</strong> Combines temperature and humidity to measure perceived heat</li>
                        <li><strong>Air Quality Index:</strong> Measures air pollution levels affecting health</li>
                        <li><strong>Relative Humidity:</strong> Percentage of moisture in the air</li>
                        <li><strong>Apparent Temperature:</strong> "Feels like" temperature accounting for heat, humidity and wind</li>
                    </ul>
                    <p class="text-xs text-green-700 dark:text-green-300 mt-4 italic">
                        Data sourced from FortyGuard environmental monitoring stations and historical databases.
                    </p>
                </div>
                
                <!-- Show button only if no data and not loading AND (at least one section has data OR this step failed) -->
                <div v-if="parks.length > 0 && environmentalResults.length === 0 && !envLoading && (satelliteResults.length > 0 || priorityScores.length > 0 || interventionRecommendations.length > 0 || stepStatus.environmental === 'failed')" class="flex items-center gap-4 mb-4">
                    <Button 
                        @click="handleRunEnvironmental" 
                        :disabled="envLoading"
                        variant="default"
                        class="bg-green-600 hover:bg-green-700"
                    >
                        <Loader2 v-if="envLoading" class="mr-2 h-4 w-4 animate-spin" />
                        {{ stepStatus.environmental === 'failed' ? 'Retry Environmental Analysis' : 'Run Environmental Analysis' }}
                    </Button>
                </div>
                
                <!-- Show park processing progress -->
                <div v-if="envLoading && envProcessingParks.length > 0" class="mb-4 p-3 bg-green-50 dark:bg-green-950 rounded border border-green-200 dark:border-green-800">
                    <div class="text-sm font-medium text-green-900 dark:text-green-100 mb-2">Processing parks:</div>
                    <div class="space-y-1">
                        <div v-for="park in envProcessingParks" :key="park.name" class="flex items-center gap-2 text-xs">
                            <Loader2 v-if="park.status === 'processing'" class="h-3 w-3 animate-spin text-green-600" />
                            <span v-else-if="park.status === 'completed'" class="text-green-600">✓</span>
                            <span v-else-if="park.status === 'failed'" class="text-red-600">✗</span>
                            <span class="text-green-800 dark:text-green-200">{{ park.name }}</span>
                            <span class="text-muted-foreground">({{ park.status }})</span>
                        </div>
                    </div>
                </div>
                
                <!-- Show loading state (removed - button already has spinner) -->
                
                <div v-if="environmentalResults.length > 0" class="space-y-4">
                    <div v-for="result in environmentalResults" :key="result.id" class="border rounded-lg p-4">
                        <h3 class="font-semibold">{{ result.park_name }}</h3>
                        <div class="grid grid-cols-2 gap-4 mt-2 text-sm">
                            <div>
                                <span class="text-muted-foreground">Heat Index:</span>
                                <span class="ml-2">{{ getAverageValue(result.data?.locations?.[0]?.parameters?.heat_index_celsius) }}°C</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Air Quality Index:</span>
                                <span class="ml-2">{{ getAverageValue(result.data?.locations?.[0]?.parameters?.['air_quality:idx']) }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Relative Humidity:</span>
                                <span class="ml-2">{{ getAverageValue(result.data?.locations?.[0]?.parameters?.relative_humidity_percent) }}%</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Apparent Temp:</span>
                                <span class="ml-2">{{ getAverageValue(result.data?.locations?.[0]?.parameters?.apparent_temperature_celsius) }}°C</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-muted-foreground text-sm">
                    No environmental analysis data available. Click Run to start.
                </div>
            </CardContent>
        </Card>

        <!-- Satellite Analysis Section -->
        <Card class="mb-6">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Satellite class="h-5 w-5 text-blue-600" />
                    Satellite Analysis
                </CardTitle>
                <CardDescription>Analyze satellite imagery for selected parks</CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Toggle Methodology Button -->
                <Button 
                    @click="showSatelliteMethodology = !showSatelliteMethodology"
                    variant="outline"
                    class="mb-4"
                >
                    <InfoIcon class="mr-2 h-4 w-4" />
                    {{ showSatelliteMethodology ? 'Hide' : 'Show' }} Satellite Analysis Methodology
                </Button>
                
                <!-- Satellite Analysis Methodology Overview -->
                <div v-if="showSatelliteMethodology" class="mb-6 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                        <InfoIcon class="h-4 w-4" />
                        Satellite Analysis Methodology
                    </h4>
                    <p class="text-sm text-blue-800 dark:text-blue-200 mb-4">
                        Satellite analysis uses FortyGuard API to analyze imagery and land cover:
                    </p>
                    <ul class="text-sm text-blue-800 dark:text-blue-200 list-disc list-inside space-y-2">
                        <li><strong>Original Imagery:</strong> High-resolution satellite images of park areas</li>
                        <li><strong>Segmentation Analysis:</strong> AI-powered classification of land cover types</li>
                        <li><strong>Vegetation Coverage:</strong> Percentage of trees, plants, and grass</li>
                        <li><strong>Hard Surface Percentage:</strong> Buildings, roads, and paved areas</li>
                    </ul>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-4 italic">
                        Data sourced from FortyGuard satellite imagery processing and segmentation models.
                    </p>
                </div>
                
                <!-- Show button only if no data and not loading AND at least one section has data OR this step failed -->
                <div v-if="parks.length > 0 && satelliteResults.length === 0 && !satLoading && (environmentalResults.length > 0 || priorityScores.length > 0 || interventionRecommendations.length > 0 || stepStatus.satellite === 'failed')" class="flex items-center gap-4 mb-4">
                    <Button 
                        @click="handleRunSatellite" 
                        :disabled="satLoading"
                        variant="default"
                        class="bg-blue-600 hover:bg-blue-700"
                    >
                        <Loader2 v-if="satLoading" class="mr-2 h-4 w-4 animate-spin" />
                        {{ stepStatus.satellite === 'failed' ? 'Retry Satellite Analysis' : 'Run Satellite Analysis' }}
                    </Button>
                </div>
                
                <!-- Show park processing progress -->
                <div v-if="satLoading && satProcessingParks.length > 0" class="mb-4 p-3 bg-blue-50 dark:bg-blue-950 rounded border border-blue-200 dark:border-blue-800">
                    <div class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">Processing parks:</div>
                    <div class="space-y-1">
                        <div v-for="park in satProcessingParks" :key="park.name" class="flex items-center gap-2 text-xs">
                            <Loader2 v-if="park.status === 'processing'" class="h-3 w-3 animate-spin text-blue-600" />
                            <span v-else-if="park.status === 'completed'" class="text-blue-600">✓</span>
                            <span v-else-if="park.status === 'failed'" class="text-red-600">✗</span>
                            <span class="text-blue-800 dark:text-blue-200">{{ park.name }}</span>
                            <span class="text-muted-foreground">({{ park.status }})</span>
                        </div>
                    </div>
                </div>
                
                <!-- Show loading state (removed - button already has spinner) -->
                
                <div v-if="satelliteResults.length > 0" class="space-y-4">
                    <div v-for="result in satelliteResults" :key="result.id" class="border rounded-lg p-4">
                        <h3 class="font-semibold">{{ result.park_name }}</h3>
                        
                        <!-- Images Section -->
                        <div v-if="result.data?.original_image?.[0] || result.data?.orignal_image?.[0]" class="mt-4">
                            <p class="text-sm font-medium text-muted-foreground mb-2">Satellite Imagery</p>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <p class="text-xs text-muted-foreground mb-1">Original</p>
                                    <img 
                                        :src="`data:image/png;base64,${result.data?.original_image?.[0] || result.data?.orignal_image?.[0]}`" 
                                        :alt="`Original satellite imagery for ${result.park_name}`"
                                        class="w-full h-64 object-contain rounded-lg"
                                    />
                                </div>
                                <div v-if="result.data?.segmentation?.image_content" class="flex-1">
                                    <p class="text-xs text-muted-foreground mb-1">Segmented</p>
                                    <img 
                                        :src="`data:image/png;base64,${result.data.segmentation.image_content}`" 
                                        :alt="`Segmented satellite imagery for ${result.park_name}`"
                                        class="w-full h-64 object-contain rounded-lg"
                                    />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Segmentation Data Section -->
                        <div v-if="result.data?.segmentation?.segments" class="mt-6 pt-4 border-t">
                            <p class="text-sm font-medium text-muted-foreground mb-2">Land Cover Analysis</p>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-muted-foreground">Vegetation Cover:</span>
                                    <span class="ml-2">{{ calculateVegetationPercent(result.data.segmentation.segments) }}%</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Hard Surface:</span>
                                    <span class="ml-2">{{ calculateHardSurfacePercent(result.data.segmentation.segments) }}%</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Show raw segments for debugging if calculation fails -->
                        <div v-if="result.data?.segmentation?.segments" class="mt-4 pt-4 border-t">
                            <p class="text-xs text-muted-foreground mb-2">Available Segments:</p>
                            <div class="text-xs">
                                <span v-for="(value, key) in result.data.segmentation.segments" :key="key" class="inline-block mr-2">
                                    {{ key }}: {{ value }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-muted-foreground text-sm">
                    No satellite analysis data available. Click Run to start.
                </div>
            </CardContent>
        </Card>

        <!-- Priority Scoring Section -->
        <Card class="mb-6">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Star class="h-5 w-5 text-orange-600" />
                    Priority Scoring
                </CardTitle>
                <CardDescription>{{ priorityScoringOverview?.description || 'Calculate priority scores for parks based on multiple factors' }}</CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Toggle Methodology Button -->
                <Button 
                    @click="showPriorityMethodology = !showPriorityMethodology"
                    variant="outline"
                    class="mb-4"
                >
                    <InfoIcon class="mr-2 h-4 w-4" />
                    {{ showPriorityMethodology ? 'Hide' : 'Show' }} Priority Scoring Methodology
                </Button>
                
                <!-- Priority Scoring Methodology Overview -->
                <div v-if="showPriorityMethodology" class="mb-6 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-800">
                    <template v-if="priorityScoringOverview">
                        <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                            <InfoIcon class="h-4 w-4" />
                            {{ priorityScoringOverview.title }}
                        </h4>
                        <p class="text-sm text-blue-800 dark:text-blue-200 mb-4">{{ priorityScoringOverview.calculation_note }}</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="factor in priorityScoringOverview.factors" :key="factor.name" 
                                 class="p-3 bg-white dark:bg-gray-800 rounded border border-blue-100 dark:border-blue-900">
                                <div class="flex items-center justify-between mb-1">
                                    <h5 class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ factor.name }}</h5>
                                    <span class="text-xs px-2 py-0.5 rounded-full" 
                                          :class="factor.weight === 'High' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'">
                                        {{ factor.weight }} Weight
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ factor.description }}</p>
                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                    Range: {{ factor.range }}
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                            <InfoIcon class="h-4 w-4" />
                            Priority Scoring Methodology
                        </h4>
                        <p class="text-sm text-blue-800 dark:text-blue-200 mb-4">
                            Priority scores are calculated based on:
                        </p>
                        <ul class="text-sm text-blue-800 dark:text-blue-200 list-disc list-inside space-y-2">
                            <li><strong>Heat Exposure:</strong> Average temperature from heatmap analysis</li>
                            <li><strong>Environmental Factors:</strong> Heat index, air quality, humidity from environmental analysis</li>
                            <li><strong>Satellite Data:</strong> Vegetation coverage, hard surface percentage from satellite analysis</li>
                            <li><strong>Vulnerability:</strong> Park characteristics and community vulnerability factors</li>
                        </ul>
                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-4 italic">
                            For detailed methodology, please refer to the Priority Scoring documentation.
                        </p>
                    </template>
                </div>
                
                <!-- Priority Scoring Functionality -->
                <div v-if="parks.length > 0 && priorityScores.length === 0 && !prioLoading && (environmentalResults.length > 0 || satelliteResults.length > 0 || interventionRecommendations.length > 0 || stepStatus.priority === 'failed')" class="flex items-center gap-4 mb-4">
                    <Button 
                        @click="handleCalculatePriority" 
                        :disabled="prioLoading"
                        variant="default"
                        class="bg-orange-600 hover:bg-orange-700"
                    >
                        <Loader2 v-if="prioLoading" class="mr-2 h-4 w-4 animate-spin" />
                        {{ stepStatus.priority === 'failed' ? 'Retry Priority Scoring' : 'Calculate Priority Scores' }}
                    </Button>
                </div>
                
                <!-- Show loading state (removed - button already has spinner) -->
                
                <div v-else class="space-y-4">
                    <div v-for="score in priorityScores" :key="score.id" class="border rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <h3 class="font-semibold">{{ score.park_name }}</h3>
                            <Badge 
                                :class="{
                                    'bg-green-600 hover:bg-green-700': score.priority_score < 0.3,
                                    'bg-yellow-600 hover:bg-yellow-700': score.priority_score >= 0.3 && score.priority_score < 0.7,
                                    'bg-red-600 hover:bg-red-700': score.priority_score >= 0.7
                                }"
                            >
                                {{ score.priority_score.toFixed(2) }}
                            </Badge>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-2 text-sm">
                            <div>
                                <span class="text-muted-foreground">Heat Severity:</span>
                                <span class="ml-2">{{ score.heat_severity?.toFixed(2) || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Environmental Stress:</span>
                                <span class="ml-2">{{ score.environmental_stress?.toFixed(2) || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Physical Condition:</span>
                                <span class="ml-2">{{ score.physical_condition?.toFixed(2) || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Park Importance:</span>
                                <span class="ml-2">{{ score.park_importance?.toFixed(2) || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Intervention Opportunity:</span>
                                <span class="ml-2">{{ score.intervention_opportunity?.toFixed(2) || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Intervention Recommendations Section -->
        <Card class="mb-6">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Lightbulb class="h-5 w-5 text-purple-600" />
                    Intervention Recommendations
                </CardTitle>
                <CardDescription>{{ interventionRecommendationsOverview?.description || 'Generate intervention recommendations based on priority scores' }}</CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Warning if no parks selected -->
                <div v-if="parks.length === 0" class="mb-4 p-4 bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 rounded-lg flex items-center gap-3 text-amber-800 dark:text-amber-200">
                    <AlertTriangle class="h-5 w-5" />
                    <p class="text-sm">Please select at least one park to generate interventions.</p>
                </div>

                <!-- Toggle Methodology Button -->
                <Button 
                    @click="showInterventionMethodology = !showInterventionMethodology"
                    variant="outline"
                    class="mb-4"
                >
                    <InfoIcon class="mr-2 h-4 w-4" />
                    {{ showInterventionMethodology ? 'Hide' : 'Show' }} Intervention Recommendations Methodology
                </Button>
                
                <!-- Intervention Recommendations Methodology Overview -->
                <div v-if="showInterventionMethodology" class="mb-6 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg border border-purple-200 dark:border-purple-800">
                    <template v-if="interventionRecommendationsOverview">
                        <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-3 flex items-center gap-2">
                            <InfoIcon class="h-4 w-4" />
                            {{ interventionRecommendationsOverview.title }}
                        </h4>
                        <p class="text-sm text-purple-800 dark:text-purple-200 mb-4">{{ interventionRecommendationsOverview.calculation_note }}</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="factor in interventionRecommendationsOverview.factors" :key="factor.name" 
                                 class="p-3 bg-white dark:bg-gray-800 rounded border border-purple-100 dark:border-purple-900">
                                <div class="flex items-center justify-between mb-1">
                                    <h5 class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ factor.name }}</h5>
                                    <span class="text-xs px-2 py-0.5 rounded-full" 
                                          :class="factor.weight === 'High' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'">
                                        {{ factor.weight }} Weight
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ factor.description }}</p>
                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                    Range: {{ factor.range }}
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-3 flex items-center gap-2">
                            <InfoIcon class="h-4 w-4" />
                            Intervention Recommendations Methodology
                        </h4>
                        <p class="text-sm text-purple-800 dark:text-purple-200 mb-4">
                            Intervention recommendations are generated based on:
                        </p>
                        <ul class="text-sm text-purple-800 dark:text-purple-200 list-disc list-inside space-y-2">
                            <li><strong>Priority Scores:</strong> Parks with higher heat vulnerability and exposure receive priority</li>
                            <li><strong>Intervention Types:</strong> Trees, ramadas, cool pavement, and shade structures based on park characteristics</li>
                            <li><strong>Cooling Benefits:</strong> Phoenix research evidence on temperature reduction for each intervention type</li>
                            <li><strong>Cost Analysis:</strong> Upfront costs and maintenance considerations for Phoenix climate</li>
                        </ul>
                        <p class="text-xs text-purple-700 dark:text-purple-300 mt-4 italic">
                            For detailed methodology, please refer to the Cooling Solutions documentation.
                        </p>
                    </template>
                </div>
                
                <!-- Intervention Recommendations Functionality -->
                <div v-if="parks.length > 0 && interventionRecommendations.length === 0 && !intLoading && (environmentalResults.length > 0 || satelliteResults.length > 0 || priorityScores.length > 0 || stepStatus.interventions === 'failed')" class="flex items-center gap-4 mb-4">
                    <Button 
                        @click="handleGenerateRecommendations" 
                        :disabled="intLoading"
                        variant="default"
                        class="bg-purple-600 hover:bg-purple-700"
                    >
                        <Loader2 v-if="intLoading" class="mr-2 h-4 w-4 animate-spin" />
                        {{ stepStatus.interventions === 'failed' ? 'Retry Intervention Recommendations' : 'Generate Intervention Recommendations' }}
                    </Button>
                </div>
                
                <!-- Show loading state (removed - button already has spinner) -->
                
                <div v-else class="space-y-4">
                    <div v-for="group in interventionRecommendations" :key="group.park.id" class="border rounded-lg p-4">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="font-semibold">{{ group.park.name }}</h3>
                            <Badge variant="secondary">Priority: {{ group.priority_score?.toFixed(2) || 'N/A' }}</Badge>
                        </div>
                        <div class="space-y-2">
                            <div v-for="rec in group.recommendations" :key="rec.id" class="text-sm border-l-2 pl-3">
                                <div class="font-medium">{{ rec.name }}</div>
                                <div class="text-muted-foreground">
                                    {{ rec.quantity }} {{ rec.unit }} • ${{ rec.upfront_cost?.toFixed(2) || 'N/A' }}
                                </div>
                                
                                <!-- Intervention Scale (What ParkHeat Calculates) -->
                                <div class="mt-1 p-1 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                                    <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Intervention Scale</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ rec.quantity }} {{ rec.unit }} (ParkHeat recommendation)
                                    </div>
                                </div>
                                
                                <!-- Cooling Benefit Evidence (What Phoenix Research Shows) -->
                                <div v-if="rec.cooling_benefit" class="mt-1 p-2 bg-blue-50 dark:bg-blue-950 rounded border border-blue-200 dark:border-blue-800">
                                    <div class="text-xs font-medium text-blue-900 dark:text-blue-100 mb-1">Phoenix Research Evidence</div>
                                    
                                    <!-- Evidence Scale -->
                                    <div class="text-xs text-blue-800 dark:text-blue-200 mb-1 font-medium">
                                        {{ rec.cooling_benefit.scale === 'neighborhood' ? '🏘️ Neighborhood-scale evidence' : '' }}
                                        {{ rec.cooling_benefit.scale === 'local_shade' ? '🏗️ Local shade evidence' : '' }}
                                        {{ rec.cooling_benefit.scale === 'treated_surface' ? '🛣️ Treated surface evidence' : '' }}
                                    </div>
                                    
                                    <!-- Phoenix Research Reference -->
                                    <div class="text-xs text-blue-800 dark:text-blue-200 mb-1">{{ rec.cooling_benefit.description }}</div>
                                    
                                    <!-- Evidence Type Badge -->
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                            {{ formatEvidenceType(rec.cooling_benefit.evidence_type) }}
                                        </span>
                                        
                                        <!-- Temperature Values -->
                                        <span class="text-xs text-blue-600 dark:text-blue-400">
                                            <span v-if="rec.cooling_benefit.value">
                                                {{ rec.cooling_benefit.metric }}: {{ rec.cooling_benefit.value }}{{ rec.cooling_benefit.unit }}
                                            </span>
                                            <span v-else-if="rec.cooling_benefit.reference_10_percent_canopy_c">
                                                {{ rec.cooling_benefit.metric }}: {{ rec.cooling_benefit.reference_10_percent_canopy_c }}°C - {{ rec.cooling_benefit.reference_25_percent_canopy_c }}°C
                                            </span>
                                            <span v-else-if="rec.cooling_benefit.air_temperature_c">
                                                Air: {{ rec.cooling_benefit.air_temperature_c }}°C, Surface: {{ rec.cooling_benefit.surface_temperature_c }}°C, Radiant: {{ rec.cooling_benefit.radiant_temperature_c }}°C
                                            </span>
                                        </span>
                                    </div>
                                    
                                    <!-- Source -->
                                    <div class="text-xs text-blue-600 dark:text-blue-400">{{ rec.cooling_benefit.source }}</div>
                                    
                                    <!-- Important Note -->
                                    <div class="text-xs text-blue-500 dark:text-blue-500 italic font-medium bg-blue-100 dark:bg-blue-900 p-1 rounded">
                                        ⚠️ {{ rec.cooling_benefit.important_note }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Budget Optimization Section -->
        <Card class="mb-6">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <DollarSign class="h-5 w-5 text-emerald-600" />
                    Budget Optimization
                </CardTitle>
                <CardDescription>{{ budgetOptimizationOverview?.description || 'Optimize intervention investments within budget constraints' }}</CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Toggle Methodology Button -->
                <Button 
                    @click="showBudgetMethodology = !showBudgetMethodology"
                    variant="outline"
                    class="mb-4"
                >
                    <InfoIcon class="mr-2 h-4 w-4" />
                    {{ showBudgetMethodology ? 'Hide' : 'Show' }} Budget Optimization Methodology
                </Button>
                
                <!-- Budget Optimization Methodology Overview -->
                <div v-if="showBudgetMethodology" class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-950 rounded-lg border border-emerald-200 dark:border-emerald-800">
                    <template v-if="budgetOptimizationOverview">
                        <h4 class="font-semibold text-emerald-900 dark:text-emerald-100 mb-3 flex items-center gap-2">
                            <InfoIcon class="h-4 w-4" />
                            {{ budgetOptimizationOverview.title }}
                        </h4>
                        <p class="text-sm text-emerald-800 dark:text-emerald-200 mb-4">{{ budgetOptimizationOverview.calculation_note }}</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="factor in budgetOptimizationOverview.factors" :key="factor.name" 
                                 class="p-3 bg-white dark:bg-gray-800 rounded border border-emerald-100 dark:border-emerald-900">
                                <div class="flex items-center justify-between mb-1">
                                    <h5 class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ factor.name }}</h5>
                                    <span class="text-xs px-2 py-0.5 rounded-full" 
                                          :class="factor.weight === 'High' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'">
                                        {{ factor.weight }} Weight
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ factor.description }}</p>
                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                    Range: {{ factor.range }}
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <h4 class="font-semibold text-emerald-900 dark:text-emerald-100 mb-3 flex items-center gap-2">
                            <InfoIcon class="h-4 w-4" />
                            Budget Optimization Methodology
                        </h4>
                        <p class="text-sm text-emerald-800 dark:text-emerald-200 mb-4">
                            The budget optimization algorithm prioritizes interventions based on:
                        </p>
                        <ul class="text-sm text-emerald-800 dark:text-emerald-200 list-disc list-inside space-y-2">
                            <li><strong>Priority Score:</strong> Parks with higher heat exposure and vulnerability receive priority</li>
                            <li><strong>Cooling Benefit:</strong> Interventions with higher temperature reduction potential are prioritized</li>
                            <li><strong>Cost-Effectiveness:</strong> Maximizes cooling impact per dollar spent</li>
                            <li><strong>Budget Constraints:</strong> Allocates funds across multiple parks within the specified budget</li>
                        </ul>
                        <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-4 italic">
                            For detailed methodology, please refer to the Budget Optimization documentation.
                        </p>
                    </template>
                </div>
                
                <!-- Budget Optimization Functionality -->
                <div v-if="!investmentPlan" class="space-y-4 mb-4">
                    <!-- Budget Scenarios -->
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-950 rounded-lg border border-emerald-200 dark:border-emerald-800">
                        <h4 class="font-semibold text-emerald-900 dark:text-emerald-100 mb-3 flex items-center gap-2">
                            <InfoIcon class="h-4 w-4" />
                            Budget Scenarios (Phoenix References)
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <Button
                                v-for="scenario in budgetScenarios"
                                :key="scenario.name"
                                @click="setBudgetScenario(scenario.amount)"
                                :variant="budget === scenario.amount ? 'default' : 'outline'"
                                :class="budget === scenario.amount ? 'bg-emerald-600 hover:bg-emerald-700' : ''"
                                class="text-xs h-auto py-2 flex flex-col items-center gap-1"
                            >
                                <span class="font-medium">{{ scenario.name }}</span>
                                <span class="text-xs opacity-80">${{(scenario.amount / 1000).toFixed(0)}}K</span>
                            </Button>
                        </div>
                        <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-2">
                            {{ budgetScenarios.find(s => s.amount === budget)?.description || 'Custom budget' }}
                        </p>
                    </div>

                    <!-- Custom Budget Input -->
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label for="budget" class="text-sm font-medium">Custom Budget ($):</label>
                            <Input 
                                id="budget" 
                                v-model.number="budget" 
                                type="number" 
                                class="w-40"
                                :min="0"
                            />
                        </div>
                        <Button 
                            @click="handleOptimizeBudget" 
                            :disabled="budgetLoading"
                            variant="default"
                            class="bg-emerald-600 hover:bg-emerald-700"
                        >
                            <Loader2 v-if="budgetLoading" class="mr-2 h-4 w-4 animate-spin" />
                            Generate Investment Plan
                        </Button>
                    </div>
                    
                    <!-- Budget Basis Note -->
                    <div class="text-xs text-muted-foreground bg-gray-50 dark:bg-gray-800 p-3 rounded border">
                        <strong>Budget Source:</strong> 
                        <span v-if="budget === 1500000">
                            Phoenix's $1.5M Neighborhood Parks Enhancement Program
                        </span>
                        <span v-else>
                            Custom budget for testing different scenarios
                        </span>
                        <br>
                        <em class="mt-1 block">
                            <strong>Note:</strong> Phoenix's $1.5M program is used as a realistic reference. This doesn't mean Phoenix gives ParkHeat this budget - it's just for demonstration.
                        </em>
                    </div>
                </div>
                
                <div v-else class="border rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <span class="text-muted-foreground">Budget:</span>
                            <span class="ml-2 font-semibold">${{ Number(investmentPlan.budget)?.toFixed(2) || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Allocated:</span>
                            <span class="ml-2 font-semibold">${{ Number(investmentPlan.allocated_cost)?.toFixed(2) || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Remaining:</span>
                            <span class="ml-2 font-semibold">${{ Number(investmentPlan.remaining_budget)?.toFixed(2) || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Coverage:</span>
                            <span class="ml-2 font-semibold">{{ Number(investmentPlan.modeled_priority_coverage)?.toFixed(1) || 'N/A' }}%</span>
                        </div>
                    </div>
                    
                    <div v-if="investmentPlan.items && investmentPlan.items.length > 0" class="space-y-2">
                        <h4 class="font-semibold text-sm">Selected Interventions:</h4>
                        <div v-for="item in investmentPlan.items" :key="item.park_id" class="text-sm border-l-2 pl-3">
                            <div class="font-medium">{{ item.park_name }} - {{ item.intervention_type }}</div>
                            <div class="text-muted-foreground">
                                {{ item.quantity }} {{ item.unit }} • ${{ Number(item.total_cost)?.toFixed(2) || 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>