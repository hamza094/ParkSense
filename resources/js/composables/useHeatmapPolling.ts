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
                
                if (status?.toLowerCase() === 'completed' || status?.toLowerCase() === 'failed') {
                    if (status?.toLowerCase() === 'completed' && data?.data?.result) {
                        heatmapResult.value = data.data.result;
                        
                        // Run park heat analysis automatically before redirecting
                        const heatmapAnalysisId = data?.data?.heatmap_analysis_id;
                        if (heatmapAnalysisId) {
                            router.flash('message', 'Heatmap processing completed! Running park heat analysis...');
                            
                            // Run park heat analysis
                            const analysisHttp = useHttp({});
                            analysisHttp.post(`/parks/run-heat-analysis?heatmap_analysis_id=${heatmapAnalysisId}`, {
                                onSuccess: (parkData: any) => {
                                    router.flash('message', 'Park heat analysis completed! Redirecting to analysis detail...');
                                    setTimeout(() => {
                                        router.visit(`/heat-analyses/${heatmapAnalysisId}`);
                                    }, 1500);
                                },
                                onHttpException: (response: any) => {
                                    console.error('Park heat analysis failed:', response.status);
                                    router.flash('error', 'Park heat analysis failed. Redirecting anyway...');
                                    setTimeout(() => {
                                        router.visit(`/heat-analyses/${heatmapAnalysisId}`);
                                    }, 1500);
                                },
                                onNetworkError: (error: any) => {
                                    console.error('Network error during park heat analysis:', error.message);
                                    router.flash('error', 'Network error during park heat analysis. Redirecting anyway...');
                                    setTimeout(() => {
                                        router.visit(`/heat-analyses/${heatmapAnalysisId}`);
                                    }, 1500);
                                }
                            });
                        } else {
                            // Fallback to reload if ID is not available
                            router.flash('message', 'Heatmap processing completed! Reloading to show new analysis...');
                            setTimeout(() => {
                                router.reload();
                            }, 1500);
                        }
                    } else if (status?.toLowerCase() === 'failed') {
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
        // Don't cancel pollHttp - it causes HttpCancelledError on navigation
        // The polling will naturally stop when the component unmounts
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
