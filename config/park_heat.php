<?php

return [
    /*
     * ParkHeat Priority Model v2
     * 
     * A configurable planning model calibrated for Phoenix summer conditions using:
     * - Phoenix climate data (NWS Phoenix climate records/normals)
     * - Phoenix heat-response framework (City of Phoenix Heat Response Plan)
     * - Phoenix shade planning standards (Shade Phoenix Plan)
     * - Phoenix park planning standards (Parks Standards and Service Area)
     * - Thermal comfort references (ASHRAE Standard 55, CDC/NIOSH heat guidance)
     * 
     * IMPORTANT: Thresholds and weights are modeling parameters, not official City of Phoenix scoring standards.
     * This model represents ParkHeat's evidence-informed approach to park heat mitigation prioritization.
     */

    /*
     * Priority scoring weights.
     * Total must equal 1.0 (100%).
     * These weights determine how much each factor contributes to the final priority score.
     * ParkHeat planning model weights - heat severity is dominant as it measures the primary problem.
     */
    'priority_weights' => [
        'heat_severity' => 0.40,
        'environmental_stress' => 0.20,
        'physical_condition' => 0.15,
        'park_importance' => 0.15,
        'intervention_opportunity' => 0.10,
    ],

    /*
     * Environmental analysis window.
     * Matches heatmap analysis window (08:00-18:00).
     * Environmental data uses numeric array indices (0-14 representing hours 08:00-22:00).
     * Indices 0-10 correspond to 08:00-18:00 (11 hours total).
     */
    'analysis_window' => [
        'start_hour' => 8,
        'end_hour' => 18,
        'array_indices' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10], // 08:00-18:00
    ],

    /*
     * Temperature thresholds for heat severity normalization.
     * ParkHeat Priority Model v2 - calibrated for Phoenix summer conditions.
     * Based on Phoenix climate data (NWS Phoenix 1991-2020 July normal high: 41.1°C).
     * Uses absolute scale instead of relative min/max to avoid micro-variation amplification.
     */
    'temperature' => [
        'low' => 30.0,  // Below this = minimal heat severity (0 score)
        'high' => 45.0, // Above this = maximum heat severity (100 score)
    ],

    /*
     * Environmental thresholds for normalization.
     * ParkHeat Priority Model v2 - calibrated for Phoenix summer conditions.
     * These are application thresholds based on Phoenix climate data and monsoon conditions.
     * Used to convert raw values to 0-100 scores.
     */
    'environmental' => [
        'heat_index' => [
            'low' => 35.0,
            'high' => 45.0,
        ],
        'wet_bulb' => [
            'low' => 20.0,
            'high' => 25.0,
        ],
        'humidity' => [
            'low' => 10.0,
            'high' => 40.0, // Extended to 40% to account for monsoon humidity spikes
        ],
        'solar_irradiance' => [
            'low' => 400.0,
            'high' => 1000.0, // Extended to 1000 W/m² for better discrimination during peak solar exposure
        ],
    ],

    /*
     * Environmental component weights.
     * Determines how much each environmental factor contributes to environmental stress score.
     * Heat Index is dominant (50%) as it directly measures thermal stress.
     */
    'environmental_weights' => [
        'heat_index' => 0.50,
        'wet_bulb' => 0.25,
        'humidity' => 0.15,
        'solar_ghi' => 0.10,
    ],

    /*
     * Satellite segmentation class groupings.
     * Based on actual classes returned by FortyGuard API for our parks.
     */
    'satellite' => [
        'vegetation_classes' => ['tree', 'plant', 'grass'],
        'hard_surface_classes' => ['building', 'road', 'route'],
        'bare_ground_classes' => ['earth', 'ground'],
    ],

    /*
     * Park type importance scores.
     * Determines base importance score based on park classification.
     */
    'park_type_scores' => [
        'Regional' => 30,
        'Community' => 20,
        'Neighborhood' => 15,
        'Pocket' => 10,
        'Natural Park' => 15,
        'Linear' => 5,
        null => 5,
    ],

    /*
     * Facility importance scores.
     * Additional points for specific amenities that increase park value.
     */
    'facility_scores' => [
        'playground' => 20,
        'splash_pads' => 15,
        'swimming_pool' => 15,
        'sports_complex' => 10,
        'recreation_community_center' => 5,
        'shade_structures' => 5,
    ],

    /*
     * Model version for tracking algorithm changes.
     * Allows distinguishing old analyses from new ones when weights change.
     * v2: Fixed heat severity to use absolute thresholds instead of relative min/max
     */
    'model_version' => 'v2',

    /*
     * Intervention catalog and recommendation rules.
     * ParkHeat planning model with Phoenix-referenced costs.
     * 
     * Evidence Levels:
     * 🟢 Phoenix verified: Costs from official Phoenix municipal documents
     * 🟡 ParkHeat planning assumptions: Model parameters for optimization
     * 🔵 FortyGuard measurements: Actual park condition data
     */
    'interventions' => [
        'catalog' => [
            'tree_planting' => [
                'name' => 'Tree Planting',
                'category' => 'vegetation',
                'unit' => 'tree',
                'upfront_cost_per_unit' => 1050,
                'annual_maintenance' => 100,
                'annual_water' => 114.32,
                'cost_note' => 'Phoenix Shade Phoenix Plan estimate: $750 for a 24" box tree + labor + $300 irrigation supplies. Recurring references: $100/year maintenance + $114.32/year water.',
                'source' => 'City of Phoenix Shade Phoenix Plan',
                'source_url' => 'https://www.phoenix.gov/content/dam/phoenix/heatsite/documents/BP_ShadePhoenixPlan_Report_031025_EN.pdf',
            ],
            'ramada' => [
                'name' => 'Ramada / Built Shade',
                'category' => 'shade',
                'unit' => 'ramada',
                'min_cost' => 40000,
                'max_cost' => 80000,
                'planning_cost' => 60000, // ParkHeat optimization assumption (midpoint)
                'cost_note' => 'Phoenix Neighborhood Parks Enhancement Program lists ramadas at $40,000-$80,000. ParkHeat uses the $60,000 midpoint for planning.',
                'source' => 'City of Phoenix Parks',
                'source_url' => 'https://www.phoenix.gov/administration/departments/parks/about-us/improvement-projects/neighborhood-parks-enhancement-program.html',
            ],
            'cool_pavement' => [
                'name' => 'Cool-Pavement Treatment',
                'category' => 'surface',
                'unit' => 'sqft',
                'planning_cost_per_sqft' => 3.00,
                'cost_basis' => 'ParkHeat planning estimate informed by Phoenix roadway context; not a Phoenix park-specific construction cost.',
                'treatment_percent_of_hard_surface' => 10, // ParkHeat planning assumption
                'source' => 'City of Phoenix roadway reference',
                'source_url' => 'https://www.phoenix.gov/content/dam/phoenix/streetssite/documents/3rd%20st_lincoln%20st%20to%20washington%20st_design%20concept%20report.pdf',
            ],
        ],
        'tree_planning_packages' => [
            'small' => [
                'quantity' => 25,
                'name' => 'Small',
                'basis' => 'ParkHeat planning scenario',
            ],
            'medium' => [
                'quantity' => 50,
                'name' => 'Medium',
                'basis' => 'ParkHeat planning scenario',
            ],
            'large' => [
                'quantity' => 100,
                'name' => 'Large',
                'basis' => 'ParkHeat planning scenario',
            ],
        ],
        'tree_planning' => [
            'phoenix_planting_standard_sqft' => 600, // Phoenix Landscape Standards: 1 tree per 600 sq ft of total landscape area, excluding sidewalks, plazas, play areas, sight visibility and active turf zones
            'citywide_canopy_goal_percent' => 25, // Phoenix 2030 canopy goal
        ],
        'rules' => [
            'tree_planting' => [
                'priority' => 10,
                'when' => [
                    'heat_severity' => ['gte' => 50], // ParkHeat planning threshold
                    'vegetation_percent' => ['lte' => 20], // ParkHeat planning threshold
                ],
                'package_selection' => [
                    'small' => [
                        'when' => [
                            'intervention_opportunity' => ['lte' => 50],
                        ],
                    ],
                    'medium' => [
                        'when' => [
                            'intervention_opportunity' => ['gt' => 50, 'lte' => 65],
                        ],
                    ],
                    'large' => [
                        'when' => [
                            'intervention_opportunity' => ['gt' => 65],
                        ],
                    ],
                ],
            ],
            'cool_pavement' => [
                'priority' => 9,
                'when' => [
                    'heat_severity' => ['gte' => 50], // ParkHeat planning threshold
                    'hard_surface_percent' => ['gte' => 50], // ParkHeat planning threshold
                ],
            ],
            'ramada' => [
                'priority' => 8,
                'when' => [
                    'heat_severity' => ['gte' => 50], // ParkHeat planning threshold
                    'playground' => ['bool' => true],
                    'shade_structures' => ['bool' => false],
                ],
            ],
        ],
        'max_recommendations_per_park' => 2, // ParkHeat planning assumption
        'model_version' => 'v3', // Phoenix-referenced intervention planning model
        'model_note' => 'Phoenix-referenced intervention planning model with verified costs for trees and ramadas, planning estimates for cool pavement and rule thresholds.',
    ],
];
