<script setup lang="ts">
defineProps<{
    environmentalResults: any[];
}>();
</script>

<template>
    <div v-if="environmentalResults && environmentalResults.length > 0" class="bg-white border border-sidebar-border/70 rounded-xl p-6 dark:border-sidebar-border">
        <h3 class="text-lg font-semibold mb-4">Environmental Parameters</h3>
        <div class="space-y-4">
            <div 
                v-for="(item, index) in environmentalResults" 
                :key="item.park_id"
                class="p-4 bg-gray-50 rounded-lg"
            >
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium">{{ item.park_name }}</h4>
                    <span class="text-sm text-gray-500">Avg Temp: {{ item.average_temperature }}°C</span>
                </div>
                
                <div v-if="item.environmental_data?.locations?.[0]" class="space-y-3">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                        <div v-if="item.environmental_data.locations[0].parameters?.heat_index_celsius?.[0] !== null" class="bg-white p-2 rounded">
                            <span class="text-gray-500">Heat Index:</span>
                            <span class="font-medium">{{ item.environmental_data.locations[0].parameters.heat_index_celsius[0] }}°C</span>
                        </div>
                        <div v-if="item.environmental_data.locations[0].parameters?.apparent_temperature_celsius?.[0] !== null" class="bg-white p-2 rounded">
                            <span class="text-gray-500">Apparent Temp:</span>
                            <span class="font-medium">{{ item.environmental_data.locations[0].parameters.apparent_temperature_celsius[0] }}°C</span>
                        </div>
                        <div v-if="item.environmental_data.locations[0].parameters?.wet_bulb_temperature_celsius?.[0] !== null" class="bg-white p-2 rounded">
                            <span class="text-gray-500">Wet Bulb:</span>
                            <span class="font-medium">{{ item.environmental_data.locations[0].parameters.wet_bulb_temperature_celsius[0] }}°C</span>
                        </div>
                        <div v-if="item.environmental_data.locations[0].parameters?.relative_humidity_percent?.[0] !== null" class="bg-white p-2 rounded">
                            <span class="text-gray-500">Humidity:</span>
                            <span class="font-medium">{{ item.environmental_data.locations[0].parameters.relative_humidity_percent[0] }}%</span>
                        </div>
                        <div v-if="item.environmental_data.locations[0].parameters?.['air_quality:idx']?.[0] !== null" class="bg-white p-2 rounded">
                            <span class="text-gray-500">AQI:</span>
                            <span class="font-medium">{{ item.environmental_data.locations[0].parameters['air_quality:idx'][0] }}</span>
                        </div>
                        <div v-if="item.environmental_data.locations[0].parameters?.precipitation_mm?.[0] !== null" class="bg-white p-2 rounded">
                            <span class="text-gray-500">Precipitation:</span>
                            <span class="font-medium">{{ item.environmental_data.locations[0].parameters.precipitation_mm[0] }} mm</span>
                        </div>
                    </div>
                    
                    <div v-if="item.environmental_data.locations[0].solar_irradiance?.clear_sky" class="bg-white p-2 rounded text-sm">
                        <span class="text-gray-500">Solar Irradiance:</span>
                        <span class="font-medium ml-2">
                            GHI: {{ item.environmental_data.locations[0].solar_irradiance.clear_sky.ghi }} | 
                            DNI: {{ item.environmental_data.locations[0].solar_irradiance.clear_sky.dni }} | 
                            DHI: {{ item.environmental_data.locations[0].solar_irradiance.clear_sky.dhi }}
                        </span>
                    </div>
                </div>
                
                <div v-else class="text-sm text-gray-500">
                    No environmental data available
                </div>
            </div>
        </div>
    </div>
</template>
