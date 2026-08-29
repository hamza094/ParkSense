<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Loader2, Leaf, Satellite, Star, Lightbulb, DollarSign, Info } from '@lucide/vue';
import GoogleMap from '@/components/GoogleMap.vue';
import { useEnvironmentalAnalysis } from '@/composables/useEnvironmentalAnalysis';
import { useSatelliteAnalysis } from '@/composables/useSatelliteAnalysis';
import { usePriorityScoring } from '@/composables/usePriorityScoring';
import { useInterventionRecommendations } from '@/composables/useInterventionRecommendations';
import { useBudgetOptimization } from '@/composables/useBudgetOptimization';

const props = defineProps<{
    heatAnalysis: {
        id: number;
        park_id: number | null;
        park_name: string | null;
        created_at: string;
        status: string;
        activity_id: string;
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

const budget = ref<number>(100000);
const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';

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
const { runEnvironmentalAnalysis, loading: environmentalLoading } = useEnvironmentalAnalysis();
const { runSatelliteAnalysis, loading: satelliteLoading } = useSatelliteAnalysis();
const { calculatePriorityScores, loading: priorityLoading } = usePriorityScoring();
const { generateRecommendations, loading: interventionLoading } = useInterventionRecommendations();
const { optimizeBudget, loading: budgetLoading } = useBudgetOptimization();

// Handlers
const handleRunEnvironmental = () => {
    runEnvironmentalAnalysis(props.heatAnalysis.id);
};

const handleRunSatellite = () => {
    runSatelliteAnalysis(props.heatAnalysis.id);
};

const handleCalculatePriority = () => {
    calculatePriorityScores(props.heatAnalysis.id);
};

const handleGenerateRecommendations = () => {
    generateRecommendations(props.heatAnalysis.id);
};

const handleOptimizeBudget = () => {
    optimizeBudget(props.heatAnalysis.id, budget.value);
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
            
            <h1 class="text-3xl font-bold">Heat Analysis #{{ heatAnalysis.id }}</h1>
            <div class="flex items-center gap-4 mt-2 text-sm text-muted-foreground">
                <span>Created: {{ formatDate(heatAnalysis.created_at) }}</span>
                <span>•</span>
                <span>Park: {{ heatAnalysis.park_name || 'Multiple Parks' }}</span>
                <span>•</span>
                <Badge :variant="heatAnalysis.status === 'Completed' ? 'default' : 'secondary'">
                    {{ heatAnalysis.status }}
                </Badge>
            </div>
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
                <div v-if="environmentalResults.length === 0" class="flex items-center gap-4 mb-4">
                    <Button 
                        @click="handleRunEnvironmental" 
                        :disabled="environmentalLoading"
                        variant="default"
                        class="bg-green-600 hover:bg-green-700"
                    >
                        <Loader2 v-if="environmentalLoading" class="mr-2 h-4 w-4 animate-spin" />
                        Run Environmental Analysis
                    </Button>
                </div>
                
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
                                <span class="text-muted-foreground">Surface Temp:</span>
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
                <div v-if="satelliteResults.length === 0" class="flex items-center gap-4 mb-4">
                    <Button 
                        @click="handleRunSatellite" 
                        :disabled="satelliteLoading"
                        variant="default"
                        class="bg-blue-600 hover:bg-blue-700"
                    >
                        <Loader2 v-if="satelliteLoading" class="mr-2 h-4 w-4 animate-spin" />
                        Run Satellite Analysis
                    </Button>
                </div>
                
                <div v-if="satelliteResults.length > 0" class="space-y-4">
                    <div v-for="result in satelliteResults" :key="result.id" class="border rounded-lg p-4">
                        <h3 class="font-semibold">{{ result.park_name }}</h3>
                        
                        <!-- Images Section -->
                        <div v-if="result.data?.original_image?.[0]" class="mt-4">
                            <p class="text-sm font-medium text-muted-foreground mb-2">Satellite Imagery</p>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <p class="text-xs text-muted-foreground mb-1">Original</p>
                                    <img 
                                        :src="`data:image/png;base64,${result.data.original_image[0]}`" 
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
                                    <span class="text-muted-foreground">Tree Canopy:</span>
                                    <span class="ml-2">{{ result.data.segmentation.segments.building || 'N/A' }}%</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Impervious Surface:</span>
                                    <span class="ml-2">{{ result.data.segmentation.segments['road, route'] || 'N/A' }}%</span>
                                </div>
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
                <!-- Priority Scoring Methodology Overview -->
                <div v-if="priorityScoringOverview" class="mb-6 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                        <Info class="h-4 w-4" />
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
                </div>
                
                <!-- Priority Scoring Functionality -->
                <div v-if="priorityScores.length === 0" class="flex items-center gap-4 mb-4">
                    <Button 
                        @click="handleCalculatePriority" 
                        :disabled="priorityLoading"
                        variant="default"
                        class="bg-orange-600 hover:bg-orange-700"
                    >
                        <Loader2 v-if="priorityLoading" class="mr-2 h-4 w-4 animate-spin" />
                        Calculate Priority Scores
                    </Button>
                </div>
                
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
                <!-- Intervention Recommendations Methodology Overview -->
                <div v-if="interventionRecommendationsOverview" class="mb-6 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg border border-purple-200 dark:border-purple-800">
                    <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-3 flex items-center gap-2">
                        <Info class="h-4 w-4" />
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
                </div>
                
                <!-- Intervention Recommendations Functionality -->
                <div v-if="interventionRecommendations.length === 0" class="flex items-center gap-4 mb-4">
                    <Button 
                        @click="handleGenerateRecommendations" 
                        :disabled="interventionLoading"
                        variant="default"
                        class="bg-purple-600 hover:bg-purple-700"
                    >
                        <Loader2 v-if="interventionLoading" class="mr-2 h-4 w-4 animate-spin" />
                        Generate Recommendations
                    </Button>
                </div>
                
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
                <!-- Budget Optimization Methodology Overview -->
                <div v-if="budgetOptimizationOverview" class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-950 rounded-lg border border-emerald-200 dark:border-emerald-800">
                    <h4 class="font-semibold text-emerald-900 dark:text-emerald-100 mb-3 flex items-center gap-2">
                        <Info class="h-4 w-4" />
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
                </div>
                
                <!-- Budget Optimization Functionality -->
                <div v-if="!investmentPlan" class="flex items-center gap-4 mb-4">
                    <div class="flex items-center gap-2">
                        <label for="budget" class="text-sm font-medium">Budget ($):</label>
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
                            <span class="ml-2 font-semibold">{{ (Number(investmentPlan.modeled_priority_coverage) * 100)?.toFixed(1) || 'N/A' }}%</span>
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