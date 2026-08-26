import { ref, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

export function useSatellitePolling(initialResults: any[] = []) {
    const satelliteResults = ref<any[]>(initialResults);
    const satelliteSubmissions = ref<any[]>([]);
    const satellitePollingInterval = ref<Record<string, number> | null>(null);

    const startSatellitePolling = (activityId: string, parkId: number) => {
        const interval = window.setInterval(() => {
            checkSatelliteStatus(activityId, parkId);
        }, 5000); // 5000ms polling interval
        
        if (!satellitePollingInterval.value) {
            satellitePollingInterval.value = {};
        }
        satellitePollingInterval.value[activityId] = interval;
        
        checkSatelliteStatus(activityId, parkId);
    };

    const checkSatelliteStatus = async (activityId: string, parkId: number) => {
        try {
            const response = await fetch('/satellite/status/' + activityId, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                console.error('HTTP Error:', response.status);
                clearSpecificInterval(activityId);
                return;
            }

            const data = await response.json();
            const status = data?.data?.status;
            
            if (status === 'Completed' || status === 'Failed') {
                clearSpecificInterval(activityId);
                
                if (status === 'Completed' && data?.data?.result) {
                    if (!satelliteResults.value) {
                        satelliteResults.value = [];
                    }
                    
                    const submission = satelliteSubmissions.value.find(s => s.activity_id === activityId);
                    
                    satelliteResults.value.push({
                        park_id: parkId,
                        park_name: submission?.park_name || 'Unknown Park',
                        average_temperature: submission?.average_temperature,
                        satellite_data: data.data.result,
                    });
                    
                    router.flash('message', 'Satellite analysis completed for park!');
                } else if (status === 'Failed') {
                    router.flash('error', 'Satellite analysis failed for park.');
                }
            }
        } catch (error: any) {
            console.error('Network error:', error.message);
            clearSpecificInterval(activityId);
        }
    };

    const clearSpecificInterval = (activityId: string) => {
        const interval = satellitePollingInterval.value?.[activityId];
        if (interval) {
            clearInterval(interval);
            delete satellitePollingInterval.value![activityId];
        }
    };

    const stopAllPolling = () => {
        if (satellitePollingInterval.value) {
            Object.values(satellitePollingInterval.value).forEach((interval: number) => {
                clearInterval(interval);
            });
            satellitePollingInterval.value = null;
        }
    };

    onUnmounted(() => {
        stopAllPolling();
    });

    return {
        satelliteResults,
        satelliteSubmissions,
        startSatellitePolling,
        stopAllPolling,
    };
}
