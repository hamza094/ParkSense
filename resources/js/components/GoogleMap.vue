<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { setOptions, importLibrary } from '@googlemaps/js-api-loader';

const props = defineProps<{
  apiKey: string;
}>();

const mapContainer = ref<HTMLDivElement>();
let map: any = null;

// Phoenix, Arizona coordinates
const phoenixCenter = { lat: 33.4484, lng: -112.0740 };

onMounted(async () => {
  console.log('GoogleMap mounting. apiKey present:', !!props.apiKey, 'length:', props.apiKey?.length);

  if (!mapContainer.value) {
    console.error('mapContainer ref is missing');
    return;
  }
  if (!props.apiKey) {
    console.error('Google Maps API key is missing — check VITE_GOOGLE_MAPS_API_KEY in .env and restart the Vite dev server');
    return;
  }

  try {
    console.log('Setting up Google Maps API...');
    setOptions({
      key: props.apiKey,
    });

    console.log('Importing Maps library...');
    const { Map } = await importLibrary('maps');
    console.log('Maps library imported, creating map...');

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
    console.log('Map created successfully');

    // Load the drawing polyfill after map is initialized
    console.log('Loading drawing polyfill...');
    const polyfillScript = document.createElement('script');
    polyfillScript.src = '/mcx-drawing-polyfill.js';
    polyfillScript.onload = () => {
      console.log('Drawing polyfill loaded, initializing DrawingManager...');
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
      console.log('Polygon completed:', polygon);
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
    }
  );
};

onUnmounted(() => {
  if (map) {
    map = null;
  }
});
</script>

<template>
  <div ref="mapContainer" class="w-full h-full" />
</template>
