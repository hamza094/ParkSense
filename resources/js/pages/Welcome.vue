<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <header
            class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl"
        >
            <nav class="flex items-center justify-end gap-4">
                <Link
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
            </nav>
        </header>
        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row"
            >
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <h1 class="mb-1 text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">ParkSense</h1>
                    <p class="mb-4 text-[#706f6c] dark:text-[#A1A09A]">
                        Data-driven park management platform for urban heat analysis and intervention planning.
                    </p>
                    <p class="mb-6 text-[#706f6c] dark:text-[#A1A09A]">
                        ParkSense helps city planners and park managers analyze urban heat islands, prioritize interventions, and optimize budget allocation for park improvements.
                    </p>
                    <ul class="mb-6 flex flex-col lg:mb-8">
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:top-1/2 before:bottom-0 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span
                                class="relative bg-white py-1 dark:bg-[#161615]"
                            >
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-[#10b981] dark:bg-[#10b981]"
                                    />
                                </span>
                            </span>
                            <span>
                                <span class="font-medium">Heat Analysis</span> - Identify urban heat islands using satellite imagery
                            </span>
                        </li>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span
                                class="relative bg-white py-1 dark:bg-[#161615]"
                            >
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-[#3b82f6] dark:bg-[#3b82f6]"
                                    />
                                </span>
                            </span>
                            <span>
                                <span class="font-medium">Priority Scoring</span> - Rank parks based on heat vulnerability and socioeconomic factors
                            </span>
                        </li>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span
                                class="relative bg-white py-1 dark:bg-[#161615]"
                            >
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-[#f59e0b] dark:bg-[#f59e0b]"
                                    />
                                </span>
                            </span>
                            <span>
                                <span class="font-medium">Intervention Planning</span> - Generate data-driven recommendations for park improvements
                            </span>
                        </li>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span
                                class="relative bg-white py-1 dark:bg-[#161615]"
                            >
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-[#8b5cf6] dark:bg-[#8b5cf6]"
                                    />
                                </span>
                            </span>
                            <span>
                                <span class="font-medium">Budget Optimization</span> - Maximize impact within budget constraints using knapsack algorithm
                            </span>
                        </li>
                    </ul>
                    <ul class="flex gap-3 text-sm leading-normal">
                        <li>
                            <Link
                                :href="dashboard()"
                                class="inline-block rounded-sm border border-[#10b981] bg-[#10b981] px-5 py-1.5 text-sm leading-normal text-white hover:border-[#059669] hover:bg-[#059669] dark:border-[#10b981] dark:bg-[#10b981] dark:hover:border-[#059669] dark:hover:bg-[#059669]"
                            >
                                Go to Dashboard
                            </Link>
                        </li>
                    </ul>
                </div>
                <div
                    class="relative -mb-px aspect-[335/364] w-full shrink-0 overflow-hidden rounded-t-lg bg-gradient-to-br from-[#10b981] to-[#3b82f6] lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:from-[#059669] dark:to-[#2563eb]"
                >
                    <!-- ParkSense Logo/Icon -->
                    <div class="flex h-full items-center justify-center p-8">
                        <div class="text-center">
                            <div class="mb-4 flex justify-center">
                                <svg
                                    class="w-24 h-24 text-white opacity-90"
                                    viewBox="0 0 100 100"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <!-- Tree Icon -->
                                    <path
                                        d="M50 10 L20 40 L35 40 L15 65 L40 65 L25 85 L75 85 L60 65 L85 65 L65 40 L80 40 Z"
                                        fill="currentColor"
                                        stroke="white"
                                        stroke-width="2"
                                    />
                                    <rect
                                        x="45"
                                        y="85"
                                        width="10"
                                        height="15"
                                        fill="currentColor"
                                        stroke="white"
                                        stroke-width="2"
                                    />
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold text-white mb-2">ParkSense</h2>
                            <p class="text-white/80 text-sm">Smart Park Management</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
