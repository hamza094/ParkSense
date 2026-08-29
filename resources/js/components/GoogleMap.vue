<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
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
    property_type: string | null;
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
  readonly?: boolean;
}>();

const emit = defineEmits<{
  'polygon-submitted': [activityId: string];
}>();

const mapContainer = ref<HTMLDivElement>();
let map: any = null;
let heatmapLayer: any = null;
const mapReady = ref(false);
const mapError = ref<string | null>(null);

// Module-level singleton: ensures setOptions() is called exactly once
// across all component instances and Inertia navigations.
let _mapsApiPromise: Promise<any> | null = null;
function getGoogleMapsApi(): Promise<any> {
  if (!_mapsApiPromise) {
    setOptions({
      key: import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '',
    });
    _mapsApiPromise = importLibrary('maps');
  }
  return _mapsApiPromise;
}

// Phoenix, Arizona coordinates (default)
const phoenixCenter = { lat: 33.4484, lng: -112.0740 };

// Calculate center from heatmap data if available
const getHeatmapCenter = () => {
  if (props.heatmapGeoJson && props.heatmapGeoJson.features && props.heatmapGeoJson.features.length > 0) {
    const feature = props.heatmapGeoJson.features[0];
    if (feature.geometry && feature.geometry.coordinates && feature.geometry.coordinates[0]) {
      const coords = feature.geometry.coordinates[0];
      // Calculate average of first polygon's coordinates
      let totalLat = 0, totalLng = 0, count = 0;
      coords.forEach((coord: number[]) => {
        if (coord.length >= 2) {
          totalLat += coord[1]; // GeoJSON is [lng, lat]
          totalLng += coord[0];
          count++;
        }
      });
      if (count > 0) {
        const center = { lat: totalLat / count, lng: totalLng / count };
        console.log('Calculated heatmap center:', center);
        return center;
      }
    }
  }
  console.log('Using default Phoenix center');
  return phoenixCenter;
};

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
  console.log('renderHeatmapTiles called with:', geoJson);
  console.log('Number of features:', geoJson?.features?.length);

  if (!map) {
    console.error('Map not initialized');
    return;
  }

  if (!geoJson || !geoJson.features) {
    console.error('Invalid GeoJSON data:', geoJson);
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
  let renderedCount = 0;
  let skippedCount = 0;

  geoJson.features.forEach((feature: any, index: number) => {
    if (index === 0) {
      console.log('First feature structure:', feature);
    }

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

      renderedCount++;

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
      skippedCount++;
      if (index < 5) { // Only log first 5 skipped features to avoid spam
        console.warn(`Feature ${index} skipped - type: ${feature.geometry.type}, has temp: ${!!feature.properties.min_temperature}`);
      }
    }
  });

  console.log(`Rendered ${renderedCount} polygons, skipped ${skippedCount} features`);

  // Fit map bounds to rendered polygons
  if (renderedCount > 0) {
    const bounds = new (window as any).google.maps.LatLngBounds();
    let boundsCount = 0;
    
    geoJson.features.forEach((feature: any) => {
      if (feature.geometry.type === 'Polygon' && feature.geometry.coordinates[0]) {
        feature.geometry.coordinates[0].forEach((coord: number[]) => {
          bounds.extend({ lat: coord[1], lng: coord[0] });
          boundsCount++;
        });
      }
    });
    
    if (boundsCount > 0) {
      map.fitBounds(bounds);
      console.log('Map bounds fitted to polygons, boundsCount:', boundsCount);
      
      // After fitting bounds, enforce minimum zoom of 12
      setTimeout(() => {
        const currentZoom = map.getZoom();
        console.log('Current zoom after bounds fitting:', currentZoom);
        if (currentZoom > 15) {
          map.setZoom(15); // Don't zoom in too much
          console.log('Adjusted zoom to 15');
        } else if (currentZoom < 12) {
          map.setZoom(12); // Ensure minimum zoom of 12
          console.log('Adjusted zoom to minimum 12');
        }
      }, 500);
    } else {
      console.log('No valid coordinates for bounds fitting');
    }
  } else {
    console.log('No polygons rendered, cannot fit bounds');
  }
};

onMounted(async () => {
  // Wait for next tick to ensure DOM is fully rendered and has dimensions
  await nextTick();

  if (!mapContainer.value) {
    console.error('mapContainer ref is missing');
    mapError.value = 'Map container not found';
    return;
  }

  try {
    // Use singleton to ensure setOptions() + importLibrary() only happens once
    const { Map } = await getGoogleMapsApi();

    const mapCenter = getHeatmapCenter();

    map = new Map(mapContainer.value, {
      center: mapCenter,
      zoom: 12,
      minZoom: 12, // Prevent zooming out below current level
      maxZoom: 18,
      restriction: {
        latLngBounds: { north: 34.5, south: 32.5, east: -111.0, west: -113.5 },
        strictBounds: true,
      },
      mapTypeId: 'roadmap',
      disableDefaultUI: true, // Disable all default UI controls
      zoomControl: false, // Disable zoom control buttons
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: false,
    });

    mapReady.value = true;
    console.log('Map initialized successfully');

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
    // Only load once — check if script is already in the DOM
    if (!document.querySelector('script[src="/mcx-drawing-polyfill.js"]')) {
      const polyfillScript = document.createElement('script');
      polyfillScript.src = '/mcx-drawing-polyfill.js';
      polyfillScript.onload = () => {
        initDrawingManager();
      };
      polyfillScript.onerror = () => {
        console.error('Failed to load drawing polyfill');
      };
      document.head.appendChild(polyfillScript);
    } else {
      // Script already loaded from a previous mount, just init drawing
      initDrawingManager();
    }
  } catch (error) {
    console.error('Error loading Google Maps:', error);
    mapError.value = 'Failed to load Google Maps';
  }
});

const initDrawingManager = () => {
  if (!map || !(window as any).google?.maps?.drawing) {
    console.error('Map or drawing library not available');
    return;
  }

  // Don't initialize drawing manager if readonly
  if (props.readonly) {
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
  console.log('Watch triggered - mapReady:', ready, 'geoJson:', !!geoJson);

  if (ready && geoJson) {
    console.log('Conditions met, calling renderHeatmapTiles');
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
  <!-- Use explicit height (not h-full) so the map container gets real pixels.
       min-h-[400px] alone does NOT establish a definite height for percentage children. -->
  <div class="relative w-full min-h-[400px] bg-gray-100" style="height: 400px;">
    <div v-if="mapError" class="absolute inset-0 flex items-center justify-center bg-red-50 text-red-600">
      <div class="text-center p-4">
        <p class="font-medium">Map Error</p>
        <p class="text-sm">{{ mapError }}</p>
      </div>
    </div>
    <div v-else-if="!mapReady" class="absolute inset-0 flex items-center justify-center bg-gray-100 text-gray-500">
      <div class="text-center p-4">
        <p class="font-medium">Loading map...</p>
      </div>
    </div>
    <!-- Use absolute positioning to fill the parent regardless of CSS height resolution -->
    <div ref="mapContainer" class="absolute inset-0" />
  </div>
</template>
