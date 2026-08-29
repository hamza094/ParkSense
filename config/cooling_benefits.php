<?php

return [
    /*
     * Cooling benefit evidence from Phoenix research.
     * These are research references, not temperature predictions.
     * Different metrics for different interventions - do not combine.
     * 
     * Evidence Levels:
     * 🟢 Measured Reference: Actual Phoenix field measurements
     * 🟡 Research Reference: Phoenix research studies
     * 🔵 Planning Assumption: ParkHeat planning estimates
     */
    
    'tree_planting' => [
        'metric' => 'air_temperature_reduction',
        'unit' => '°C',
        'scale' => 'neighborhood',
        'value' => 4.4,
        'basis' => 'Phoenix Cool Urban Spaces Project',
        'reference_10_percent_canopy_c' => 2.0,
        'reference_25_percent_canopy_c' => 4.4,
        'evidence_type' => 'research_reference',
        'source' => 'City of Phoenix Cool Urban Spaces Project',
        'source_url' => 'https://www.phoenix.gov/administration/departments/heat/tree-shade-programs.html',
        'description' => 'Phoenix research found substantial neighborhood-scale cooling as tree canopy increases. ~2°C cooling at 10% canopy increase; ~4.4°C at 25% canopy.',
        'important_note' => 'Phoenix research reference - not a park-specific temperature prediction. This is neighborhood-scale evidence, not a prediction for individual parks.',
    ],
    
    'ramada' => [
        'metric' => 'shade_reference',
        'unit' => '°C',
        'scale' => 'local_shade',
        'air_temperature_c' => 1.1,
        'surface_temperature_c' => 11.1,
        'radiant_temperature_c' => 16.7,
        'basis' => 'Phoenix Shade Phoenix Plan',
        'evidence_type' => 'research_reference',
        'source' => 'City of Phoenix Shade Phoenix Plan',
        'source_url' => 'https://www.phoenix.gov/content/dam/phoenix/heatsite/documents/BP_ShadePhoenixPlan_Report_031025_EN.pdf',
        'description' => 'Phoenix Shade Plan illustrates localized thermal relief: 1.1°C air-temperature difference, 11.1°C surface-temperature difference, 16.7°C radiant-temperature difference between sun and shade.',
        'important_note' => 'Phoenix research reference - illustrative sun/shade comparison, not a ramada-specific temperature prediction.',
    ],
    
    'cool_pavement' => [
        'metric' => 'surface_temperature_reduction',
        'unit' => '°C',
        'scale' => 'treated_surface',
        'value' => 6.7,
        'basis' => 'Phoenix Cool Pavement Program',
        'evidence_type' => 'measured_reference',
        'source' => 'City of Phoenix Cool Pavement Program / ASU Study',
        'source_url' => 'https://www.phoenix.gov/content/dam/phoenix/streetssite/documents/3rd%20st_lincoln%20st%20to%20washington%20st_design%20concept%20report.pdf',
        'description' => 'Phoenix reports up to 12°F (6.7°C) lower pavement surface temperature compared with conventional aged pavement during the day.',
        'important_note' => 'Phoenix research reference - surface-temperature reduction does not equal park-wide air-temperature reduction.',
    ],
];