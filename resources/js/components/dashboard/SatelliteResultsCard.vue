<script setup lang="ts">
defineProps<{
    satelliteResults: any[];
}>();
</script>

<template>
    <div v-if="satelliteResults && satelliteResults.length > 0" class="bg-white border border-sidebar-border/70 rounded-xl p-6 dark:border-sidebar-border">
        <h3 class="text-lg font-semibold mb-4">Satellite Segmentation</h3>
        <div class="space-y-4">
            <div 
                v-for="(item, index) in satelliteResults" 
                :key="item.park_id"
                class="p-4 bg-gray-50 rounded-lg"
            >
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium">{{ item.park_name }}</h4>
                    <span class="text-sm text-gray-500">Avg Temp: {{ item.average_temperature }}°C</span>
                </div>
                
                <div v-if="item.satellite_data" class="space-y-3">
                    <!-- Coordinates -->
                    <div v-if="item.satellite_data.coordinates" class="bg-white p-2 rounded text-sm">
                        <span class="text-gray-500">Coordinates:</span>
                        <span class="font-medium ml-2">
                            {{ item.satellite_data.coordinates.latitude }}, {{ item.satellite_data.coordinates.longitude }}
                        </span>
                    </div>

                    <!-- Image Year -->
                    <div v-if="item.satellite_data.image_year" class="bg-white p-2 rounded text-sm">
                        <span class="text-gray-500">Image Year:</span>
                        <span class="font-medium ml-2">{{ item.satellite_data.image_year }}</span>
                    </div>

                    <!-- Original Image -->
                    <div v-if="item.satellite_data.orignal_image && item.satellite_data.orignal_image.length > 0" class="bg-white p-2 rounded">
                        <span class="text-gray-500 text-sm block mb-2">Original Satellite Image:</span>
                        <img 
                            v-for="(img, imgIndex) in item.satellite_data.orignal_image" 
                            :key="imgIndex"
                            :src="'data:image/png;base64,' + img"
                            class="max-w-full h-auto rounded border"
                            alt="Original satellite image"
                        />
                    </div>

                    <!-- Segmentation Data -->
                    <div v-if="item.satellite_data.segmentation" class="space-y-2">
                        <!-- Image Dimensions -->
                        <div v-if="item.satellite_data.segmentation.image_dimensions" class="bg-white p-2 rounded text-sm">
                            <span class="text-gray-500">Dimensions:</span>
                            <span class="font-medium ml-2">
                                {{ item.satellite_data.segmentation.image_dimensions.width }}x{{ item.satellite_data.segmentation.image_dimensions.height }}px
                            </span>
                        </div>

                        <!-- Processing Time -->
                        <div v-if="item.satellite_data.segmentation.processing_time_seconds" class="bg-white p-2 rounded text-sm">
                            <span class="text-gray-500">Processing Time:</span>
                            <span class="font-medium ml-2">{{ item.satellite_data.segmentation.processing_time_seconds }}s</span>
                        </div>

                        <!-- Segments (Class Coverage) -->
                        <div v-if="item.satellite_data.segmentation.segments && Object.keys(item.satellite_data.segmentation.segments).length > 0" class="bg-white p-2 rounded text-sm">
                            <span class="text-gray-500 block mb-2">Class Coverage:</span>
                            <div class="grid grid-cols-2 gap-2">
                                <div v-for="(value, key) in item.satellite_data.segmentation.segments" :key="key" class="text-xs">
                                    <span class="text-gray-600">{{ key }}:</span>
                                    <span class="font-medium">{{ value }}%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Segmentation Mask -->
                        <div v-if="item.satellite_data.segmentation.image_content" class="bg-white p-2 rounded">
                            <span class="text-gray-500 text-sm block mb-2">Segmentation Mask:</span>
                            <img 
                                :src="'data:image/png;base64,' + item.satellite_data.segmentation.image_content"
                                class="max-w-full h-auto rounded border"
                                alt="Segmentation mask"
                            />
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-sm text-gray-500">
                    No satellite data available
                </div>
            </div>
        </div>
    </div>
</template>
