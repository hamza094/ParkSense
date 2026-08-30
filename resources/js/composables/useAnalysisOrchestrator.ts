import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useEnvironmentalAnalysis } from './useEnvironmentalAnalysis';
import { useSatelliteAnalysis } from './useSatelliteAnalysis';
import { usePriorityScoring } from './usePriorityScoring';
import { useInterventionRecommendations } from './useInterventionRecommendations';

export function useAnalysisOrchestrator() {
    const currentStep = ref<string>('');
    const stepStatus = ref<Record<string, 'pending' | 'loading' | 'completed' | 'failed'>>({
        environmental: 'pending',
        satellite: 'pending',
        priority: 'pending',
        interventions: 'pending'
    });

    const { runEnvironmentalAnalysis, loading: environmentalLoading, cancelPolling: cancelEnvironmentalPolling } = useEnvironmentalAnalysis();
    const { runSatelliteAnalysis, loading: satelliteLoading, cancelPolling: cancelSatellitePolling } = useSatelliteAnalysis();
    const { calculatePriorityScores, loading: priorityLoading } = usePriorityScoring();
    const { generateRecommendations, loading: interventionLoading } = useInterventionRecommendations();

    const runSequentialAnalysis = async (heatmapAnalysisId: number, startFromStep?: string) => {
        const steps = [
            {
                name: 'environmental',
                execute: () => runEnvironmentalAnalysis(heatmapAnalysisId),
                loading: environmentalLoading,
                polling: true // Environmental and satellite use polling
            },
            {
                name: 'satellite',
                execute: () => runSatelliteAnalysis(heatmapAnalysisId),
                loading: satelliteLoading,
                polling: true // Environmental and satellite use polling
            },
            {
                name: 'priority',
                execute: () => calculatePriorityScores(heatmapAnalysisId),
                loading: priorityLoading,
                polling: false // Priority and interventions are synchronous
            },
            {
                name: 'interventions',
                execute: () => generateRecommendations(heatmapAnalysisId),
                loading: interventionLoading,
                polling: false // Priority and interventions are synchronous
            }
        ];

        // If startFromStep is specified, begin from that step
        const startIndex = startFromStep 
            ? steps.findIndex(s => s.name === startFromStep)
            : 0;
        
        const stepsToRun = startIndex >= 0 ? steps.slice(startIndex) : steps;

        for (const step of stepsToRun) {
            currentStep.value = step.name;
            stepStatus.value[step.name] = 'loading';

            try {
                await step.execute();
                // The composables handle their own completion logic
                // For polling steps, they resolve only after polling completes
                // For synchronous steps, they resolve immediately
                stepStatus.value[step.name] = 'completed';
            } catch (error: any) {
                stepStatus.value[step.name] = 'failed';
                console.error(`Step ${step.name} failed:`, error);
                
                // Check if the error is due to missing park heat analysis
                if (error.message && error.message.includes('No parks found in heat analysis')) {
                    console.error('Park heat analysis needs to be run first');
                    // Don't throw - let the user see the error and retry
                    return;
                }
                
                throw error; // Stop sequential execution on other failures
            }
        }

        currentStep.value = '';
        
        // Reload page after all steps complete to show final results
        router.reload();
    };

    const resetStatus = () => {
        currentStep.value = '';
        stepStatus.value = {
            environmental: 'pending',
            satellite: 'pending',
            priority: 'pending',
            interventions: 'pending'
        };
    };

    const getProgressPercentage = () => {
        const steps = ['environmental', 'satellite', 'priority', 'interventions'];
        const completedSteps = steps.filter(step => stepStatus.value[step] === 'completed').length;
        const loadingStep = currentStep.value ? 1 : 0;
        return ((completedSteps + loadingStep) / steps.length) * 100;
    };

    const getStepLabel = (step: string) => {
        const labels: Record<string, string> = {
            environmental: 'Environmental Analysis',
            satellite: 'Satellite Analysis',
            priority: 'Priority Scoring',
            interventions: 'Intervention Recommendations'
        };
        return labels[step] || step;
    };

    return {
        currentStep,
        stepStatus,
        runSequentialAnalysis,
        resetStatus,
        getProgressPercentage,
        getStepLabel,
        environmentalLoading,
        satelliteLoading,
        priorityLoading,
        interventionLoading,
        cancelAllPolling: () => {
            cancelEnvironmentalPolling();
            cancelSatellitePolling();
        }
    };
}