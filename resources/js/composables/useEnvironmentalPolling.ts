import { ref, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

export function useEnvironmentalPolling(initialResults: any[] = []) {
    const environmentalResults = ref<any[]>(initialResults);
    const environmentalSubmissions = ref<any[]>([]);
    const environmentalPollingInterval = ref<Record<string, number> | null>(null);

    const startEnvironmentalPolling = (activityId: string, parkId: number) => {
        const interval = window.setInterval(() => {
            checkEnvironmentalStatus(activityId, parkId);
        }, 5000); // Increased from 3000ms to 5000ms for environmental params
        
        if (!environmentalPollingInterval.value) {
            environmentalPollingInterval.value = {};
        }
        environmentalPollingInterval.value[activityId] = interval;
        
        checkEnvironmentalStatus(activityId, parkId);
    };

    const checkEnvironmentalStatus = async (activityId: string, parkId: number) => {
        try {
            const response = await fetch('/environmental/status/' + activityId, {
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
                    if (!environmentalResults.value) {
                        environmentalResults.value = [];
                    }
                    
                    const submission = environmentalSubmissions.value.find(s => s.activity_id === activityId);
                    
                    environmentalResults.value.push({
                        park_id: parkId,
                        park_name: submission?.park_name || 'Unknown Park',
                        average_temperature: submission?.average_temperature,
                        environmental_data: data.data.result,
                    });
                    
                    router.flash('message', 'Environmental analysis completed for park!');
                } else if (status === 'Failed') {
                    router.flash('error', 'Environmental analysis failed for park.');
                }
            }
        } catch (error: any) {
            console.error('Network error:', error.message);
            clearSpecificInterval(activityId);
        }
    };

    const clearSpecificInterval = (activityId: string) => {
        const interval = environmentalPollingInterval.value?.[activityId];
        if (interval) {
            clearInterval(interval);
            delete environmentalPollingInterval.value![activityId];
        }
    };

    const stopAllPolling = () => {
        if (environmentalPollingInterval.value) {
            Object.values(environmentalPollingInterval.value).forEach((interval: number) => {
                clearInterval(interval);
            });
            environmentalPollingInterval.value = null;
        }
    };

    onUnmounted(() => {
        stopAllPolling();
    });

    return {
        environmentalResults,
        environmentalSubmissions,
        startEnvironmentalPolling,
        stopAllPolling,
    };
}
