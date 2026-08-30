import { ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';

export function useEnvironmentalAnalysis() {
    const loading = ref(false);
    const environmentalHttp = useHttp({});
    let activePoll: NodeJS.Timeout | null = null;
    const processingParks = ref<Array<{ name: string; status: string }>>([]);

    const runEnvironmentalAnalysis = (heatmapAnalysisId: number): Promise<void> => {
        return new Promise((resolve, reject) => {
            loading.value = true;
            
            environmentalHttp.post(`/environmental/run-analysis/${heatmapAnalysisId}`, {
                onSuccess: async (data: any) => {
                    const submissions = data?.submissions || [];
                    
                    if (submissions.length > 0) {
                        router.flash('message', `Environmental analysis submitted for ${submissions.length} parks. Processing...`);
                        try {
                            await pollAllEnvironmentalSubmissions(submissions);
                            loading.value = false; // Set loading to false only after polling completes
                            resolve();
                        } catch (err) {
                            loading.value = false; // Set loading to false on error
                            reject(err);
                        }
                    } else {
                        // Already exists or no parks
                        loading.value = false;
                        resolve();
                    }
                },
                onHttpException: (response: any) => {
                    console.error('HTTP Error:', response.status);
                    const errorMessage = response.data?.error || response.data?.message || '';
                    
                    // If already exists, treat as success so pipeline continues
                    if (errorMessage.includes('already exists') || errorMessage.includes('Data is available')) {
                        console.log('Environmental analysis already exists, marking as completed for pipeline...');
                        loading.value = false;
                        resolve();
                    } else {
                        router.flash('error', errorMessage || 'Failed to run environmental analysis');
                        loading.value = false;
                        reject(new Error('HTTP Error'));
                    }
                },
                onNetworkError: (error: any) => {
                    console.error('Network error:', error.message);
                    router.flash('error', 'Network error while running environmental analysis');
                    loading.value = false;
                    reject(error);
                },
                onFinish: () => {
                    // Don't set loading.value = false here - let the polling completion handle it
                    // onFinish fires immediately after POST, but polling may still be running
                }
            });
        });
    };

    const pollAllEnvironmentalSubmissions = (submissions: Array<{ activity_id: string; park_name: string }>): Promise<void> => {
        const pendingActivityIds = new Set(submissions.map(s => s.activity_id));
        const maxAttempts = 60; // 5 minutes (60 * 5s)
        let attempts = 0;
        
        // Initialize processing parks tracking
        processingParks.value = submissions.map(s => ({ name: s.park_name, status: 'processing' }));

        return new Promise((resolve, reject) => {
            activePoll = setInterval(async () => {
                attempts++;

                for (const activityId of Array.from(pendingActivityIds)) {
                    try {
                        const response = await fetch(`/environmental/status/${activityId}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (response.ok) {
                            const resData = await response.json();
                            const status = resData?.data?.status;

                            // When completed or failed, remove from pending set and update status
                            if (status?.toLowerCase() === 'completed' || status?.toLowerCase() === 'failed') {
                                pendingActivityIds.delete(activityId);
                                // Update park status
                                const parkName = submissions.find(s => s.activity_id === activityId)?.park_name;
                                if (parkName) {
                                    const parkIndex = processingParks.value.findIndex(p => p.name === parkName);
                                    if (parkIndex >= 0) {
                                        processingParks.value[parkIndex].status = status?.toLowerCase() === 'completed' ? 'completed' : 'failed';
                                    }
                                }
                            }
                        }
                    } catch (error) {
                        console.error('Polling error for', activityId, error);
                    }
                }

                // When all submissions have finished
                if (pendingActivityIds.size === 0) {
                    clearInterval(activePoll!);
                    activePoll = null;
                    processingParks.value = [];
                    router.flash('message', 'Environmental analysis completed successfully for all parks!');
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(activePoll!);
                    activePoll = null;
                    processingParks.value = [];
                    router.flash('error', 'Environmental analysis timed out');
                    reject(new Error('Timeout'));
                }
            }, 5000); // Poll every 5 seconds
        });
    };

    return {
        loading,
        processingParks,
        runEnvironmentalAnalysis,
        cancelPolling: () => {
            if (activePoll) {
                clearInterval(activePoll);
                activePoll = null;
            }
        }
    };
}
