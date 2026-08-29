

# Updated Detailed Implementation Plan: Heat Analysis-Based Navigation

## Corrected Flow

**Dashboard**:
- Generic map for polygon drawing
- List of existing heat analyses (clickable)
- Purpose: Create new heat analyses OR navigate to existing ones

**Heat Analysis Detail**:
- All activities happen here
- Map shows selected heatmap tiles (read-only)
- Environmental Analysis (RUN button + results)
- Satellite Analysis (RUN button + results)
- Priority Scoring (RUN button + results)
- Intervention Recommendations (GENERATE button + results)
- Budget Optimization (budget input + GENERATE button + results)
- Back to Dashboard button

---

## Phase 1: Backend Changes

### 1.1 Create HeatAnalysisDetailController
**File**: `app/Http/Controllers/HeatAnalysisDetailController.php`

**Purpose**: Load all data for a specific heat analysis detail page (initial state + existing results)

**Method**: [show($id)](cci:1://file:///c:/Users/Hamza/Herd/parksense/app/Http/Controllers/ParkController.php:470:4-476:5)

**Data to Load**:
1. **Heat Analysis Details**
   - HeatAnalysis model with park relationship
   - ID, date, status, park info

2. **Heatmap Tiles**
   - HeatmapTile records for this heat_analysis_id
   - Include: id, park_id, park_name, geometry, centroid_lat, centroid_lng
   - For map display (highlighted tiles)

3. **Environmental Analysis Results** (existing)
   - EnvironmentalAnalysis records for this heat_analysis_id
   - Include: id, park_id, park_name, activity_id, status, ndvi, ndwi, lst, surface_temperature, created_at
   - For initial display if already run

4. **Satellite Analysis Results** (existing)
   - SatelliteAnalysis records for this heat_analysis_id
   - Include: id, park_id, park_name, activity_id, status, tree_canopy_cover, impervious_surface, created_at
   - For initial display if already run

5. **Priority Scores** (existing)
   - ParkPriorityScore records for this heat_analysis_id
   - Include: id, park_id, park_name, priority_score, heat_vulnerability, socioeconomic_factor, created_at
   - For initial display if already calculated

6. **Intervention Recommendations** (existing)
   - InterventionRecommendation records for this heat_analysis_id
   - Group by park_id
   - Include: park info, priority_score, recommendations array (scenario, name, category, quantity, unit, costs, source, rule, justification)
   - For initial display if already generated

7. **Investment Plan** (existing)
   - InvestmentPlan record for this heat_analysis_id (latest)
   - Include: id, budget, allocated_cost, remaining_budget, total_modeled_benefit, modeled_priority_coverage, created_at
   - Include items: park_id, park_name, intervention_type, scenario, quantity, unit, total_cost, modeled_benefit
   - For initial display if already optimized

8. **Parks List**
   - All parks (for map boundaries)

**Return**: `Inertia::render('HeatAnalysisDetail', [...])` with all above data

---

### 1.2 Add Route
**File**: [routes/web.php](cci:7://file:///c:/Users/Hamza/Herd/parksense/routes/web.php:0:0-0:0)

**Add**:
```php
Route::get('heat-analyses/{id}', [HeatAnalysisDetailController::class, 'show'])->name('heat-analyses.show');
```

**Location**: Inside the Route::group with auth middleware

---

### 1.3 Update ParkController index()
**File**: [app/Http/Controllers/ParkController.php](cci:7://file:///c:/Users/Hamza/Herd/parksense/app/Http/Controllers/ParkController.php:0:0-0:0)

**Current State**: Loads all nested data (environmental, satellite, priority, interventions, investment)

**Changes**:
1. Remove all nested result loading (lines ~115-184)
2. Keep only:
   - Parks list
   - Heat analyses list (summary)
3. Heat analysis summary should include:
   - id
   - park_id
   - park_name
   - created_at
   - status
   - tile_count (count of HeatmapTile records for this analysis)

**Simplified Return**:
```php
return Inertia::render('Dashboard', [
    'parks' => $parks,
    'heatAnalyses' => $heatAnalysesSummary,
]);
```

---

## Phase 2: Frontend Structure

### 2.1 Create HeatAnalysisDetail.vue
**File**: `resources/js/pages/HeatAnalysisDetail.vue`

**Purpose**: Detail page with all activity features for a specific heat analysis

**Layout Structure**:
```
┌─────────────────────────────────────────┐
│ Header: Heat Analysis #123              │
│         Created: Aug 28, 2026           │
│         Park: Central Park              │
│         [← Back to Dashboard]           │
└─────────────────────────────────────────┘
├── Google Map Section
│   ├── Map with park boundaries
│   ├── Heatmap tiles highlighted (red/colored)
│   ∙ Read-only (no polygon drawing)
│   └── Tile info on hover
├── Environmental Analysis Section
│   ├── [Run Environmental Analysis] button
│   ├── Results cards (if any)
│   └── Loading state
├── Satellite Analysis Section
│   ├── [Run Satellite Analysis] button
│   ├── Results cards (if any)
│   └── Loading state
├── Priority Scoring Section
│   ├── [Calculate Priority Scores] button
│   ├── Scores table/list (if any)
│   └── Loading state
├── Intervention Recommendations Section
│   ├── [Generate Recommendations] button
│   ├── Recommendations cards grouped by park (if any)
│   └── Loading state
└── Budget Optimization Section
    ├── Budget input field
    ├── [Generate Investment Plan] button
    ├── Investment plan card (if any)
    └── Loading state
```

**Props Definition**:
```typescript
interface Props {
    heatAnalysis: {
        id: number;
        park_id: number;
        park_name: string;
        created_at: string;
        status: string;
    };
    heatmapTiles: Array<{
        id: number;
        park_id: number;
        park_name: string;
        geometry: string;
        centroid_lat: number;
        centroid_lng: number;
    }>;
    parks: Array<{
        id: number;
        park_id: string;
        name: string;
        geometry: string;
        latitude: number;
        longitude: number;
    }>;
    environmentalResults: any[];
    satelliteResults: any[];
    priorityScores: any[];
    interventionRecommendations: any[];
    investmentPlan: any;
}
```

**Composables to Use**:
- `useEnvironmentalAnalysis` - for running environmental analysis
- `useSatelliteAnalysis` - for running satellite analysis
- [usePriorityScoring](cci:1://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/usePriorityScoring.ts:3:0-57:1) - for calculating priority scores
- [useInterventionRecommendations](cci:1://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/useInterventionRecommendations.ts:3:0-40:1) - for generating recommendations
- [useBudgetOptimization](cci:1://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/useBudgetOptimization.ts:27:0-73:1) - for optimizing budget

**Key Features**:
- All sections have RUN/GENERATE buttons
- All sections show loading states
- All sections display existing results if available
- Map is read-only (shows selected tiles)
- Back button navigates to Dashboard

---

### 2.2 Update Dashboard.vue
**File**: [resources/js/pages/Dashboard.vue](cci:7://file:///c:/Users/Hamza/Herd/parksense/resources/js/pages/Dashboard.vue:0:0-0:0)

**Current State**: Shows all features (heatmap, environmental, satellite, priority, interventions, budget)

**Changes**:
1. **Remove all feature sections**:
   - Remove Environmental Analysis section
   - Remove Satellite Analysis section
   - Remove Priority Scoring section
   - Remove Intervention Recommendations section
   - Remove Budget Optimization section

2. **Keep only**:
   - Generic Google Map (for polygon drawing)
   - Heat Analyses List (clickable cards)

3. **Update Props**:
   - Remove: environmentalResults, satelliteResults, priorityScores, interventionRecommendations, investmentPlan
   - Keep: parks, heatAnalyses (summary list)

4. **Update Composables**:
   - Keep: useHeatmapPolling (for polygon submission)
   - Remove: useHeatAnalysis, useEnvironmentalAnalysis, useSatelliteAnalysis, usePriorityScoring, useInterventionRecommendations, useBudgetOptimization

5. **Update Polygon Submission Handler**:
   - Current: Submits polygon and shows results on same page
   - New: After successful submission, navigate to detail page
   - Use: `router.visit(route('heat-analyses.show', { id: newHeatAnalysisId }))`

6. **Heat Analysis List Display**:
   ```
   ┌─────────────────────────────────────┐
   │ Heat Analyses                       │
   ├─────────────────────────────────────┤
   │ ┌─────────────────────────────────┐ │
   │ │ Heat Analysis #123              │ │
   │ │ Park: Central Park              │ │
   │ │ Date: Aug 28, 2026             │ │
   │ │ Tiles: 5                        │ │
   │ │ [View Details →]                │ │
   │ └─────────────────────────────────┘ │
   │ ┌─────────────────────────────────┐ │
   │ │ Heat Analysis #124              │ │
   │ │ Park: Riverside Park            │ │
   │ │ Date: Aug 27, 2026             │ │
   │ │ Tiles: 3                        │ │
   │ │ [View Details →]                │ │
   │ └─────────────────────────────────┘ │
   └─────────────────────────────────────┘
   ```

7. **Click Handler**:
   - On "View Details" click: `router.visit(route('heat-analyses.show', { id: analysis.id }))`

---

### 2.3 Update useHeatmapPolling Composable
**File**: `resources/js/composables/useHeatmapPolling.ts`

**Current Behavior**: Polls for heatmap results and updates on Dashboard

**Changes**:
- Keep polling logic
- Update `handlePolygonSubmitted` callback:
  - Instead of updating local state, navigate to detail page
  - Pass the new heat analysis ID to the callback

---

## Phase 3: HeatAnalysisDetail Implementation Details

### 3.1 Map Section
**Implementation**:
- Reuse existing Google Maps setup
- Load park boundaries from parks prop
- Display heatmap tiles as highlighted polygons on map
- Use different color/style for selected tiles (e.g., red fill with opacity)
- Add tile info on hover (park name, coordinates)
- **Read-only** - no polygon drawing capability

**Data Needed**:
- Parks list (from prop)
- Heatmap tiles (from prop)

---

### 3.2 Back Button
**Implementation**:
- Simple button in header
- On click: `router.visit(route('dashboard'))`

---

### 3.3 Environmental Analysis Section
**Implementation**:
- Section header: "Environmental Analysis"
- **RUN button**: "Run Environmental Analysis"
- On click: Call `runEnvironmentalAnalysis(heatmapAnalysisId)`
- Show loading state while running
- If `environmentalResults` prop has data:
  - Display cards for each result
  - Show: park name, NDVI, NDWI, LST, surface temperature
- If empty:
  - Display: "No environmental analysis data available. Click Run to start."

**Composable**: `useEnvironmentalAnalysis`

---

### 3.4 Satellite Analysis Section
**Implementation**:
- Section header: "Satellite Analysis"
- **RUN button**: "Run Satellite Analysis"
- On click: Call `runSatelliteAnalysis(heatmapAnalysisId)`
- Show loading state while running
- If `satelliteResults` prop has data:
  - Display cards for each result
  - Show: park name, tree canopy cover, impervious surface
- If empty:
  - Display: "No satellite analysis data available. Click Run to start."

**Composable**: `useSatelliteAnalysis`

---

### 3.5 Priority Scoring Section
**Implementation**:
- Section header: "Priority Scoring"
- **RUN button**: "Calculate Priority Scores"
- On click: Call [calculatePriorityScores(heatmapAnalysisId)](cci:1://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/usePriorityScoring.ts:9:4-32:6)
- Show loading state while calculating
- If `priorityScores` prop has data:
  - Display table/list with: park name, priority score, heat vulnerability, socioeconomic factor
  - Sort by priority score (descending)
- If empty:
  - Display: "No priority scores available. Click Calculate to start."

**Composable**: [usePriorityScoring](cci:1://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/usePriorityScoring.ts:3:0-57:1)

---

### 3.6 Intervention Recommendations Section
**Implementation**:
- Section header: "Intervention Recommendations"
- **RUN button**: "Generate Recommendations"
- On click: Call [generateRecommendations(heatmapAnalysisId)](cci:1://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/useInterventionRecommendations.ts:9:4-32:6)
- Show loading state while generating
- If `interventionRecommendations` prop has data:
  - Display cards grouped by park
  - Each card shows: park name, priority score, recommendations list
  - Each recommendation shows: scenario, name, quantity, unit, costs
- If empty:
  - Display: "No intervention recommendations available. Click Generate to start."

**Composable**: [useInterventionRecommendations](cci:1://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/useInterventionRecommendations.ts:3:0-40:1)

---

### 3.7 Budget Optimization Section
**Implementation**:
- Section header: "Budget Optimization"
- **Budget input**: Number input field
- **RUN button**: "Generate Investment Plan"
- On click: Call `optimizeBudget(heatmapAnalysisId, budget)`
- Show loading state while optimizing
- If `investmentPlan` prop has data:
  - Display investment plan card (reuse InvestmentPlanCard.vue)
  - Show: budget, allocated cost, remaining budget, coverage, selected interventions
- If empty:
  - Display: "No investment plan available. Enter budget and click Generate."

**Composable**: [useBudgetOptimization](cci:1://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/useBudgetOptimization.ts:27:0-73:1)

---

## Phase 4: Testing Checklist

### 4.1 Dashboard Testing
- [ ] Dashboard loads with generic map
- [ ] Heat analyses list displays correctly
- [ ] Each heat analysis card shows: ID, park name, date, tile count
- [ ] "View Details" button navigates to correct detail page
- [ ] Polygon submission creates new heat analysis
- [ ] After polygon submission, automatically navigates to new detail page

### 4.2 Heat Analysis Detail Testing
- [ ] Detail page loads with correct heat analysis ID
- [ ] Header shows correct heat analysis info (ID, date, park)
- [ ] Back button navigates to Dashboard
- [ ] Map displays park boundaries
- [ ] Map highlights heatmap tiles for this analysis (read-only)
- [ ] Environmental RUN button works and displays results
- [ ] Satellite RUN button works and displays results
- [ ] Priority RUN button works and displays results
- [ ] Interventions GENERATE button works and displays results
- [ ] Budget GENERATE button works and displays results
- [ ] All sections show existing data if available
- [ ] All sections show loading states while running

### 4.3 Data Persistence Testing
- [ ] Refresh detail page → All data persists
- [ ] Navigate back to Dashboard → Navigate to same detail page → Data persists
- [ ] Different heat analyses show different data
- [ ] Running new analysis updates results on detail page

---

## File Structure Summary

### New Files
- `app/Http/Controllers/HeatAnalysisDetailController.php`
- `resources/js/pages/HeatAnalysisDetail.vue`

### Modified Files
- [routes/web.php](cci:7://file:///c:/Users/Hamza/Herd/parksense/routes/web.php:0:0-0:0) - Add heat analysis detail route
- [app/Http/Controllers/ParkController.php](cci:7://file:///c:/Users/Hamza/Herd/parksense/app/Http/Controllers/ParkController.php:0:0-0:0) - Simplify index() method
- [resources/js/pages/Dashboard.vue](cci:7://file:///c:/Users/Hamza/Herd/parksense/resources/js/pages/Dashboard.vue:0:0-0:0) - Simplify to overview view
- `resources/js/composables/useHeatmapPolling.ts` - Update navigation behavior

### Files to Reuse (No Changes)
- [resources/js/components/dashboard/InvestmentPlanCard.vue](cci:7://file:///c:/Users/Hamza/Herd/parksense/resources/js/components/dashboard/InvestmentPlanCard.vue:0:0-0:0) - Reuse in detail page
- `resources/js/components/dashboard/BudgetInput.vue` - Reuse in detail page
- `resources/js/composables/useEnvironmentalAnalysis.ts` - Reuse in detail page
- `resources/js/composables/useSatelliteAnalysis.ts` - Reuse in detail page
- [resources/js/composables/usePriorityScoring.ts](cci:7://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/usePriorityScoring.ts:0:0-0:0) - Reuse in detail page
- [resources/js/composables/useInterventionRecommendations.ts](cci:7://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/useInterventionRecommendations.ts:0:0-0:0) - Reuse in detail page
- [resources/js/composables/useBudgetOptimization.ts](cci:7://file:///c:/Users/Hamza/Herd/parksense/resources/js/composables/useBudgetOptimization.ts:0:0-0:0) - Reuse in detail page

---

## Implementation Order

1. **Phase 1 Backend** (1-2 hours)
   - Create HeatAnalysisDetailController
   - Add route
   - Update ParkController index()

2. **Phase 2 Frontend Structure** (2-3 hours)
   - Create HeatAnalysisDetail.vue skeleton
   - Update Dashboard.vue (remove sections, add heat analysis list)
   - Update useHeatmapPolling navigation

3. **Phase 3 Detail Page Implementation** (4-5 hours)
   - Implement map section (read-only)
   - Implement Environmental Analysis section (with RUN button)
   - Implement Satellite Analysis section (with RUN button)
   - Implement Priority Scoring section (with RUN button)
   - Implement Intervention Recommendations section (with GENERATE button)
   - Implement Budget Optimization section (with budget input + GENERATE button)
   - Add back button

4. **Phase 4 Testing** (1-2 hours)
   - Test navigation
   - Test data loading
   - Test all RUN/GENERATE buttons
   - Test data persistence

**Total Estimated Time**: 8-12 hours

---

## Important Notes

1. **Activity Location**: All activities (RUN/GENERATE buttons) happen on HeatAnalysisDetail page, NOT Dashboard.

2. **Dashboard Purpose**: Dashboard is only for:
   - Creating new heat analyses (polygon drawing)
   - Viewing list of existing heat analyses
   - Navigating to specific heat analysis detail pages

3. **Map Behavior**:
   - Dashboard map: Interactive (polygon drawing)
   - Detail page map: Read-only (shows selected tiles)

4. **Data Loading**: HeatAnalysisDetailController loads initial data. Composables handle new analysis runs.

5. **Composable Reuse**: All existing composables are reused in HeatAnalysisDetail page.

6. **Error Handling**: Add proper error handling in HeatAnalysisDetailController and all composable calls.

7. **Loading States**: All sections should show loading states while running analyses.

8. **Empty States**: All sections should have clear messages when no data exists, with instructions to run the analysis.

9. **Back Button**: Essential for navigation back to Dashboard.

10. **URL Structure**: Clean URLs like `/heat-analyses/123` for detail page.