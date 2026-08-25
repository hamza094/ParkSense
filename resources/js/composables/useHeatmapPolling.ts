import { ref, onUnmounted } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function useHeatmapPolling(initialResult: any = null) {
    const currentActivityId = ref<string | null>(null);
    const heatmapStatus = ref<any>(null);
    const heatmapResult = ref<any>(initialResult);
    const pollingInterval = ref<number | null>(null);
    const pollHttp = useHttp({});

    const handlePolygonSubmitted = (activityId: string) => {
        currentActivityId.value = activityId;
        startPolling(activityId);
        router.flash('message', 'Heatmap submitted successfully. Processing...');
    };

    const startPolling = (activityId: string) => {
        if (pollingInterval.value) {
            clearInterval(pollingInterval.value);
        }
        
        pollingInterval.value = window.setInterval(() => {
            checkHeatmapStatus(activityId);
        }, 3000);
        
        checkHeatmapStatus(activityId);
    };

    const checkHeatmapStatus = (activityId: string) => {
        pollHttp.get('/parks/heatmap-status/' + activityId, {
            onSuccess: (data: any) => {
                heatmapStatus.value = data;
                
                const status = data?.data?.status;
                
                if (status === 'Completed' || status === 'Failed') {
                    if (status === 'Completed' && data?.data?.result) {
                        heatmapResult.value = data.data.result;
                        router.flash('message', 'Heatmap processing completed!');
                    } else if (status === 'Failed') {
                        router.flash('error', 'Heatmap processing failed.');
                    }
                    
                    stopPolling();
                }
            },
            onHttpException: (response: any) => {
                console.error('HTTP Error:', response.status);
                router.flash('error', 'Failed to check heatmap status.');
                stopPolling();
            },
            onNetworkError: (error: any) => {
                console.error('Network error:', error.message);
                router.flash('error', 'Network error while checking heatmap status.');
                stopPolling();
            }
        });
    };

    const stopPolling = () => {
        if (pollingInterval.value) {
            clearInterval(pollingInterval.value);
            pollingInterval.value = null;
        }
        currentActivityId.value = null;
        pollHttp.cancel();
    };

    onUnmounted(() => {
        stopPolling();
    });

    return {
        currentActivityId,
        heatmapStatus,
        heatmapResult,
        handlePolygonSubmitted,
        stopPolling,
    };
}
