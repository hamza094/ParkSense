<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import GoogleMap from '@/components/GoogleMap.vue';
import { dashboard } from '@/routes';

// Google Maps API key from environment
const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';

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
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <GoogleMap :api-key="googleMapsApiKey" :parks="parksData" />
        </div>
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </div>
</template>
