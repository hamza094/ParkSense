<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { setOptions, importLibrary } from '@googlemaps/js-api-loader';
import { useHttp } from '@inertiajs/vue3';

const http = useHttp({
  coordinates: [] as any[],
});

const props = defineProps<{
  apiKey: string;
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
}>();

const emit = defineEmits<{
  'polygon-submitted': [activityId: string];
}>();

const mapContainer = ref<HTMLDivElement>();
let map: any = null;
let heatmapLayer: any = null;
const mapReady = ref(false);

// Phoenix, Arizona coordinates
const phoenixCenter = { lat: 33.4484, lng: -112.0740 };

// Function to get color based on temperature
const getTemperatureColor = (temperature: number) => {
  // Use tight range for maximum sensitivity to small differences
  const minTemp = 34.5;
  const maxTemp = 34.7;
  const normalized = (temperature - minTemp) / (maxTemp - minTemp);
  
  // Clamp to 0-1 range
  const clamped = Math.max(0, Math.min(1, normalized));
  
  // Use a more dramatic color gradient for better contrast
  // Deep blue (cool) → Blue → Cyan → Green → Yellow → Orange → Red → Deep red (hot)
  const hue = (1 - clamped) * 280; // 280 (deep purple/blue) to 0 (red)
  const saturation = 85 + (clamped * 15); // 85-100% saturation
  const lightness = 40 + (clamped * 15); // 40-55% lightness
  
  return `hsl(${hue}, ${saturation}%, ${lightness}%)`;
};

// Function to render GeoJSON heatmap tiles
const renderHeatmapTiles = (geoJson: any) => {
  
  if (!map) {
    console.error('Map not initialized');
    return;
  }
  
  if (!geoJson || !geoJson.features) {
    console.error('Invalid GeoJSON data');
    return;
  }
  
  // Clear existing heatmap layer
  if (heatmapLayer) {
    heatmapLayer.setMap(null);
    heatmapLayer = null;
  }
  
  // Log temperature range to debug color issues
  const temperatures = geoJson.features
    .filter((f: any) => f.properties.min_temperature)
    .map((f: any) => f.properties.min_temperature);
  
  // Create polygon for each feature
  geoJson.features.forEach((feature: any, index: number) => {
    
    if (feature.geometry.type === 'Polygon' && feature.properties.min_temperature) {
      const coordinates = feature.geometry.coordinates[0].map((coord: number[]) => ({
        lat: coord[1],
        lng: coord[0],
      }));
      
      // Use min_temperature for coloring
      const temperature = feature.properties.min_temperature;
      const color = getTemperatureColor(temperature);
      
      const polygon = new (window as any).google.maps.Polygon({
        paths: coordinates,
        strokeColor: color,
        strokeOpacity: 0.8,
        strokeWeight: 1,
        fillColor: color,
        fillOpacity: 0.5,
        map: map,
      });
      
      // Add click event to show temperature info
      polygon.addListener('click', () => {
        const infoWindow = new (window as any).google.maps.InfoWindow({
          content: `
            <div style="padding: 8px;">
              <strong>Tile ${feature.properties.tile_id}</strong><br>
              Avg Temp: ${temperature.toFixed(1)}°C<br>
              Min Temp: ${feature.properties.min_temperature.toFixed(1)}°C<br>
              Max Temp: ${feature.properties.max_temperature.toFixed(1)}°C
            </div>
          `,
          position: coordinates[0],
        });
        infoWindow.open(map);
      });
    } else {
      console.warn(`Feature ${index} skipped - type: ${feature.geometry.type}, has temp: ${!!feature.properties.average_temperature}`);
    }
  });
};

onMounted(async () => {
  if (!mapContainer.value) {
    console.error('mapContainer ref is missing');
    return;
  }
  if (!props.apiKey) {
    console.error('Google Maps API key is missing — check VITE_GOOGLE_MAPS_API_KEY in .env and restart the Vite dev server');
    return;
  }

  try {
    setOptions({
      key: props.apiKey,
    });

    const { Map } = await importLibrary('maps');

    map = new Map(mapContainer.value, {
      center: phoenixCenter,
      zoom: 12,
      minZoom: 10,
      maxZoom: 18,
      restriction: {
        latLngBounds: { north: 34.5, south: 32.5, east: -111.0, west: -113.5 },
        strictBounds: true,
      },
      mapTypeId: 'roadmap',
    });

    mapReady.value = true;

    // Add markers for each park
    if (props.parks && props.parks.length > 0) {
      
      props.parks.forEach((park) => {
        const lat = parseFloat(park.latitude?.toString() || '0');
        const lng = parseFloat(park.longitude?.toString() || '0');
        
        if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
          const marker = new (window as any).google.maps.Marker({
            map: map,
            position: { lat, lng },
            title: park.name,
          });
        }
      });
    }

    // Load the drawing polyfill after map is initialized
    const polyfillScript = document.createElement('script');
    polyfillScript.src = '/mcx-drawing-polyfill.js';
    polyfillScript.onload = () => {
      initDrawingManager();
    };
    polyfillScript.onerror = () => {
      console.error('Failed to load drawing polyfill');
    };
    document.head.appendChild(polyfillScript);
  } catch (error) {
    console.error('Error loading Google Maps:', error);
  }
});

const initDrawingManager = () => {
  if (!map || !(window as any).google?.maps?.drawing) {
    console.error('Map or drawing library not available');
    return;
  }

  const drawingManager = new (window as any).google.maps.drawing.DrawingManager({
    drawingMode: (window as any).google.maps.drawing.OverlayType.POLYGON,
    drawingControl: true,
    drawingControlOptions: {
      position: (window as any).google.maps.ControlPosition.TOP_CENTER,
      drawingModes: [
        (window as any).google.maps.drawing.OverlayType.POLYGON,
        (window as any).google.maps.drawing.OverlayType.MARKER,
      ],
    },
    polygonOptions: {
      editable: true,
      draggable: true,
      strokeColor: '#FF0000',
      strokeOpacity: 0.8,
      strokeWeight: 2,
      fillColor: '#FF0000',
      fillOpacity: 0.35,
    },
  });

  drawingManager.setMap(map);

  // Listen for polygon completion
  (window as any).google.maps.event.addListener(
    drawingManager,
    'polygoncomplete',
    (polygon: any) => {
      const path = polygon.getPath();
      const coordinates = [];
      for (let i = 0; i < path.getLength(); i++) {
        const point = path.getAt(i);
        coordinates.push({
          lat: point.lat(),
          lng: point.lng(),
        });
      }
      console.log('Polygon coordinates:', coordinates);
      
      // Send polygon data to backend using useHttp
      http.coordinates = coordinates;
      http.post('/parks/polygon', {
        onSuccess: (data: any) => {
          const activityId = data.activity_id;
          
          if (activityId) {
            // Emit event for parent component to handle polling
            emit('polygon-submitted', activityId);
          } else {
            console.error('No activity_id in response');
          }
        },
        onError: (errors: any) => {
          console.error('Error submitting polygon:', errors);
        },
      });
    }
  );
};

// Watch for both map readiness and GeoJSON data
watch([() => mapReady.value, () => props.heatmapGeoJson], ([ready, geoJson]) => {
  
  if (ready && geoJson) {
    
    try {
      renderHeatmapTiles(geoJson);
    } catch (error) {
      console.error('Error rendering heatmap tiles:', error);
    }
  }
}, { deep: true });

onUnmounted(() => {
  if (map) {
    map = null;
  }
});

</script>

<template>
  <div ref="mapContainer" class="w-full h-full" />
</template>
