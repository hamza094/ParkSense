# Cooling Solution Recommendations

> **Note**: Throughout this README, "intervention" refers to cooling solutions - practical strategies to make parks cooler and reduce temperature. The term "intervention" is used in the codebase and database for technical precision, but "cooling solutions" is used here for clearer understanding.

## 📍 What This Is
After priority scoring identifies which parks need help most, our cooling solution recommendation system suggests specific, actionable strategies to reduce heat in those parks. This rule-based system matches park conditions to appropriate cooling investments using Phoenix-referenced costs.

## 🎯 Evidence Levels in Our Model

Our cooling solution recommendation system uses three levels of evidence:

**🟢 Phoenix Verified**: Costs and standards from official Phoenix municipal documents
- Tree planting costs ($1,050/tree)
- Ramada cost range ($40K-$80K)
- Phoenix landscape standards (600 sq ft/tree)
- Phoenix canopy goals (25% by 2030)

**🟡 ParkSense Planning Assumptions**: Model parameters for optimization and planning
- Tree package sizes (25/50/100 trees)
- Ramada midpoint planning value ($60K)
- Cool pavement cost ($3/sq ft)
- Treatment coverage (10% of hard surface)
- Rule thresholds (heat ≥50, vegetation ≤45%, hard surface ≥25%)

**🔵 FortyGuard Measurements**: Actual park condition data from satellite and environmental analysis
- Park temperature and heat severity
- Vegetation and hard surface percentages
- Environmental metrics
- Priority scores

## 🎯 How Cooling Solution Recommendations Work

### **The Process Flow**
1. **Priority Scoring**: Rank all parks by heat need (see Priority Scoring README)
2. **Top 5 Selection**: Focus on the 5 highest-priority parks for detailed recommendations
3. **Rule Evaluation**: Check each park against cooling solution rules
4. **Recommendation Generation**: Suggest specific cooling solutions with costs and quantities
5. **Budget Optimization**: Create investment scenarios across the top parks

### **Why Top 5 Only?**
- **Practical Focus**: Limited resources mean we can't help all parks at once
- **Maximum Impact**: Focus on parks that will benefit most from cooling investments
- **Detailed Planning**: Provides specific, actionable recommendations rather than generic suggestions

## 🛠️ **Cooling Solution Types (The "Intervention Catalog")**

Our system recommends 3 types of cooling solutions based on Phoenix-specific costs and standards:

### **1. Tree Planting**
**What It Is**: Planting new trees to provide natural shade and cooling through evapotranspiration.

**How It Works**:
- Trees reduce surface and air temperatures through shade
- Evapotranspiration cools surrounding air
- Long-term cooling effect that grows as trees mature

**Cost Breakdown**:
- **Phoenix Estimate**: $750 per 24" box tree + labor (Phoenix Shade Phoenix Plan)
- **Irrigation Supplies**: $300 per tree (Phoenix Shade Phoenix Plan)
- **Total Upfront**: $1,050 per tree (Phoenix-verified)
- **Annual Maintenance**: $100 per tree (Phoenix reference)
- **Annual Water**: $114.32 per tree (Phoenix reference)

**Evidence Level**: 🟢 Phoenix verified - costs from official Phoenix Shade Phoenix Plan

**Planning Packages**:
- **Small Package**: 25 trees ($26,250 upfront)
- **Medium Package**: 50 trees ($52,500 upfront)
- **Large Package**: 100 trees ($105,000 upfront)

**Evidence Level**: 🟡 ParkSense planning assumption - package sizes for optimization scenarios (costs per tree are Phoenix-verified)

**Package Selection Rules**:
- **Small**: When intervention opportunity score ≤ 50 (limited space)
- **Medium**: When intervention opportunity score 50-65 (moderate space)
- **Large**: When intervention opportunity score > 65 (ample space)

**Where Intervention Opportunity Score Comes From:**
- **Source**: Priority scoring system - one of the 5 component scores
- **Calculation**: (Vegetation Opportunity × 0.35) + (Hard Surface Opportunity × 0.35) + (Acreage Opportunity × 0.30)
- **Data Sources**: Satellite analysis (vegetation, hard surface) + Park database (acreage)
- **Purpose**: Measures how much room for improvement the park has
- **Evidence Level**: 🔵 FortyGuard measurements (satellite and park data)

**Logical Connection:**
Parks that scored high on "intervention opportunity" in priority scoring get larger tree packages because they have more space and potential for improvement.

**When Recommended**:
- Heat severity ≥ 50 (ParkSense planning threshold)
- Vegetation ≤ 45% (ParkSense planning threshold — relaxed to include parks with partial grass coverage)
- Priority: 10 (highest priority cooling solution)

**Evidence Level**: 🟡 ParkSense planning threshold for rule matching

**Source**: City of Phoenix Shade Phoenix Plan - official Phoenix tree planting program data

---

### **2. Ramada / Built Shade**
**What It Is**: Constructing shade structures (ramadas) to provide immediate shade in high-activity areas.

**How It Works**:
- Built shade structures provide instant cooling
- Particularly valuable in playgrounds and gathering areas
- No waiting period for trees to grow

**Cost Breakdown**:
- **Phoenix Range**: $40,000 - $80,000 per ramada (Neighborhood Parks Enhancement Program)
- **Planning Value**: $60,000 per ramada (ParkSense midpoint for budget calculations)
- **Includes**: Design, construction, installation

**Evidence Level**: 🟢 Phoenix verified for cost range, 🟡 ParkSense planning assumption for midpoint value

**When Recommended**:
- Heat severity ≥ 50 (ParkSense planning threshold)
- Playground present (bool = true)
- No existing shade structures (bool = false)
- Priority: 8 (high priority for visitor comfort)

**Evidence Level**: 🟡 ParkSense planning threshold for rule matching

**Source**: City of Phoenix Parks - Neighborhood Parks Enhancement Program data

---

### **3. Cool Pavement Treatment**
**What It Is**: Applying reflective coatings or using permeable materials on hard surfaces to reduce heat absorption.

**How It Works**:
- Reflective coatings bounce solar radiation instead of absorbing it
- Permeable materials allow water evaporation cooling
- Reduces surface temperatures by 10-20°F in Phoenix studies

**Cost Breakdown**:
- **Planning Cost**: $3.00 per square foot
- **Coverage Assumption**: 10% of hard surface area (ParkSense planning scenario)
- **Rationale**: Treating all hard surfaces may not be cost-effective; strategic treatment targets high-impact areas

**Evidence Level**: 🟡 ParkSense planning estimate - informed by Phoenix roadway context, not a Phoenix park-specific construction cost

**When Recommended**:
- Heat severity ≥ 50 (ParkSense planning threshold)
- Hard surface ≥ 25% (ParkSense planning threshold — relaxed to include parks with significant parking or pathways)
- Priority: 9 (high priority for heat reduction)

**Evidence Level**: 🟡 ParkSense planning threshold for rule matching

**Source**: City of Phoenix cool-pavement feasibility study (roadway reference, adapted for parks)

---

## 🎯 **Rule-Based Recommendation System**

### **How Rules Work**
Each cooling solution has specific "trigger conditions" based on park data:

**Rule Evaluation Process**:
1. **Check Heat Severity**: All solutions require heat severity ≥ 50
2. **Check Specific Conditions**: Additional requirements based on park characteristics
3. **Match Priority**: Higher priority solutions are recommended first
4. **Limit to 2 per Park**: Maximum 2 cooling solutions per park to avoid overwhelming recommendations

### **Where Rule Values Come From**

**Threshold Values (The "When" Conditions):**
- **Source**: `config/park_heat.php` - ParkSense planning assumptions
- **Heat severity ≥ 50**: ParkSense planning threshold (not Phoenix-verified)
- **Vegetation ≤ 45%**: ParkSense planning threshold (not Phoenix-verified, relaxed from 20%)
- **Hard surface ≥ 25%**: ParkSense planning threshold (not Phoenix-verified, relaxed from 50%)
- **Purpose**: Define when each cooling solution should be recommended

**Actual Values Being Compared:**
- **Heat severity**: From priority scoring system (calculated from temperature data)
- **Vegetation %**: From satellite analysis (calculated from satellite imagery)
- **Hard surface %**: From satellite analysis (calculated from satellite imagery)
- **Playground/Shade**: From park database (Phoenix GIS data)

**Evidence Level**: 🟡 ParkSense planning threshold for rule matching

### **Complete Data Flow**

```
Step 1: Earlier Analyses
├─ Heat Analysis → Heat Severity Score (0-100) → Stored in ParkPriorityScore.heat_severity
├─ Satellite Analysis → Vegetation % and Hard Surface % → Stored in SatelliteMetric.data
└─ Park Database → Playground, Shade Structures → Stored in Park model

Step 2: Intervention Selection
├─ Retrieve heat severity from ParkPriorityScore
├─ Retrieve vegetation/hard surface from SatelliteMetric
├─ Retrieve playground/shade from Park database
└─ Compare against thresholds from config/park_heat.php

Step 3: Rule Evaluation
├─ Tree Planting: heat_severity ≥ 50 AND vegetation ≤ 45%
├─ Cool Pavement: heat_severity ≥ 50 AND hard_surface ≥ 25%
└─ Ramada: heat_severity ≥ 50 AND playground = true AND shade = false

Step 4: Recommendation Generation
├─ Sort by priority (10, 9, 8)
├─ Limit to top 2 per park
└─ Calculate costs and quantities
```

### **Rule Priority Order**
1. **Tree Planting (Priority 10)**: Most impactful long-term solution
2. **Cool Pavement (Priority 9)**: Effective for large paved areas
3. **Ramada (Priority 8)**: Important for playground visitor comfort

### **Example Rule Matching**

**Park A**: Heat severity score of 70, 15% vegetation, 60% hard surface, playground, no shade
- ✅ Tree Planting: Matches (heat severity ≥ 50, vegetation ≤ 20%)
- ✅ Cool Pavement: Matches (heat severity ≥ 50, hard surface ≥ 50%)
- ✅ Ramada: Matches (heat severity ≥ 50, playground = true, shade = false)
- **Result**: All 3 recommended (limited to top 2 by priority)

**Park B**: Heat severity score of 45, 30% vegetation, 20% hard surface, no playground
- ❌ Tree Planting: Fails (heat severity < 50)
- ❌ Cool Pavement: Fails (heat severity < 50)
- ❌ Ramada: Fails (heat severity < 50, no playground)
- **Result**: No recommendations (doesn't meet minimum heat threshold)
- **Note**: This park has relatively low heat stress compared to priority parks, so resources should focus on hotter parks

**Park C**: Heat severity score of 55, 25% vegetation, 25% hard surface, playground, existing shade
- ✅ Tree Planting: Matches (heat severity ≥ 50, vegetation ≤ 45%)
- ✅ Cool Pavement: Matches (heat severity ≥ 50, hard surface ≥ 25%)
- ❌ Ramada: Fails (existing shade = true)
- **Result**: Tree planting + Cool pavement recommended (top 2 by priority)

## 📊 **Cost Calculation Logic**

### **Tree Planting Costs**
```
Upfront Cost = Tree Package Quantity × $1,050 per tree
Annual Maintenance = Tree Package Quantity × $100 per tree
Annual Water = Tree Package Quantity × $114.32 per tree
```

**Example - Medium Package (50 trees)**:
- Upfront: 50 × $1,050 = $52,500
- Annual Maintenance: 50 × $100 = $5,000
- Annual Water: 50 × $114.32 = $5,716

### **Ramada Costs**
```
Upfront Cost = $60,000 (Phoenix midpoint planning value)
Annual Maintenance = $0 (built structures have minimal ongoing costs)
Annual Water = $0 (no water requirements)
```

### **Cool Pavement Costs**
```
Treatable Area = (Park Acres × 43,560 sq ft/acre) × (Hard Surface % / 100) × 10% (coverage assumption)
Upfront Cost = Treatable Area × $3.00 per sq ft
```

**Example - 5-acre park, 60% hard surface**:
- Total Area: 5 × 43,560 = 217,800 sq ft
- Hard Surface Area: 217,800 × 0.60 = 130,680 sq ft
- Treatable Area (10%): 130,680 × 0.10 = 13,068 sq ft
- Upfront Cost: 13,068 × $3.00 = $39,204

## 🎯 **Where Cost Values Come From**

### **Phoenix Municipal Data Sources**
All cost values are based on official Phoenix city data for credibility and defensibility:

**Tree Planting Costs**:
- **Source**: City of Phoenix Shade Phoenix Plan
- **Data**: Official Phoenix tree planting program costs
- **Credibility**: Based on actual Phoenix municipal expenditures
- **URL**: https://www.phoenix.gov/content/dam/phoenix/heatsite/documents/BP_ShadePhoenixPlan_Report_031025_EN.pdf

**Ramada Costs**:
- **Source**: City of Phoenix Parks - Neighborhood Parks Enhancement Program
- **Data**: Actual Phoenix park improvement project costs
- **Range**: $40,000 - $80,000 per ramada
- **Planning Value**: $60,000 (midpoint for budget calculations)
- **URL**: https://www.phoenix.gov/administration/departments/parks/about-us/improvement-projects/neighborhood-parks-enhancement-program.html

**Cool Pavement Costs**:
- **Source**: City of Phoenix cool-pavement feasibility study
- **Data**: Phoenix roadway cool pavement treatment costs
- **Adaptation**: Applied to parks with planning assumptions
- **Coverage**: 10% of hard surface (strategic treatment approach)
- **URL**: https://www.phoenix.gov/content/dam/phoenix/streetssite/documents/3rd%20st_lincoln%20st%20to%20washington%20st_design%20concept%20report.pdf

### **Planning Assumptions**
Some values are planning assumptions clearly labeled as such:

**Cool Pavement Coverage (10%)**:
- **Rationale**: Treating all hard surfaces may not be cost-effective
- **Strategy**: Focus on high-impact areas (playgrounds, pathways, gathering spots)
- **Adjustable**: Can be modified based on budget constraints

**Tree Package Tiers (25/50/100 trees)**:
- **Rationale**: Provides graduated investment options
- **Based on**: Phoenix planting standard (1 tree per 600 sq ft of eligible landscape area)
- **Important**: Standard excludes sidewalks, plazas, play areas, sight visibility and active turf zones
- **Adjustable**: Can be customized based on park size and eligible landscape area

## 💡 **Business Value**

**Enables:**
✅ **Actionable Recommendations**: Specific cooling solutions with costs and quantities
✅ **Credible Costs**: Based on actual Phoenix municipal data
✅ **Rule-Based Consistency**: Systematic approach to recommendation logic
✅ **Budget Planning**: Clear cost breakdowns for investment decisions
✅ **Prioritized Focus**: Limited to top 5 parks for maximum impact

## 🔧 **Technical Implementation**

### **Recommendation Pipeline**
1. **Top 5 Selection**: Get priority scores for highest-priority parks
2. **Rule Evaluation**: Check each park against all cooling solution rules
3. **Priority Sorting**: Sort eligible solutions by rule priority
4. **Cost Calculation**: Calculate quantities and costs based on park characteristics
5. **Cooling Benefit Attachment**: Attach Phoenix research evidence to each recommendation
6. **Database Storage**: Save recommendations with full calculation evidence

### **Cooling Benefit Evidence System**

**What It Is:**
Each intervention recommendation includes Phoenix-specific cooling benefit evidence from research studies and field measurements, with clear separation between what ParkSense calculates (intervention scale) and what Phoenix research provides (cooling evidence).

**Evidence Types:**
- 🟢 **Measured Reference**: Actual Phoenix field measurements (cool pavement)
- 🟡 **Research Reference**: Phoenix research studies (trees, ramada)
- 🔵 **Planning Assumption**: ParkSense planning estimates

**Configuration:**
Stored in `config/cooling_benefits.php` with enhanced data structure:

**Tree Planting Evidence:**
- **Source**: Phoenix Cool Urban Spaces Project
- **Scale**: Neighborhood (neighborhood-scale evidence)
- **Metric**: Air temperature reduction
- **Reference**: ~2°C cooling at 10% canopy increase; ~4.4°C at 25% canopy
- **Evidence Type**: Research Reference
- **Important Note**: Phoenix research reference - not a park-specific temperature prediction

**Ramada Evidence:**
- **Source**: Phoenix Shade Phoenix Plan
- **Scale**: Local shade (local shade evidence)
- **Metric**: Shade reference
- **Reference**: 1.1°C air-temperature difference, 11.1°C surface-temperature difference, 16.7°C radiant-temperature difference
- **Evidence Type**: Research Reference
- **Important Note**: Phoenix research reference - illustrative sun/shade comparison, not a ramada-specific temperature prediction

**Cool Pavement Evidence:**
- **Source**: Phoenix Cool Pavement Program / ASU Study
- **Scale**: Treated surface (treated surface evidence)
- **Metric**: Surface temperature reduction
- **Reference**: Up to 6.7°C lower pavement surface temperature
- **Evidence Type**: Measured Reference
- **Important Note**: Phoenix research reference - surface-temperature reduction does not equal park-wide air-temperature reduction

**Implementation:**
- Evidence attached via `CoolingBenefitHelper` during recommendation generation
- Stored as JSON in `cooling_benefit` field with enhanced structure
- Displayed in UI with clear separation between intervention scale and Phoenix evidence
- Different metrics for different interventions (not combined)
- Evidence scale labels (neighborhood, local shade, treated surface) for clarity

### **Configuration**
All cooling solution data is configured in `config/park_heat.php`:
- **Catalog**: Cooling solution types, costs, sources
- **Rules**: Trigger conditions for each solution (ParkSense planning thresholds)
- **Planning Packages**: Tree planting package sizes (ParkSense planning scenarios)
- **Model Version**: v3 (Phoenix-referenced intervention planning model)

## 🎯 **Important Notes**

**Planning Model, Not Exact Quotes**:
This is a planning model for budgeting and prioritization. Actual project costs will vary based on:
- Site-specific conditions
- Contractor pricing
- Material availability
- Engineering requirements
- Permitting and regulatory costs

**Evidence Level Distinction**:
Our model clearly distinguishes between:
- **Phoenix-verified data**: Costs and standards from official Phoenix documents
- **ParkSense planning assumptions**: Model parameters for optimization scenarios
- **FortyGuard measurements**: Actual park condition data

This distinction makes our architecture scientifically honest and defensible when questioned about data sources.

**Recommendation Limit**:
- Maximum 2 cooling solutions per park (ParkSense planning assumption)
- Prevents overwhelming park managers
- Focuses on highest-impact solutions

**Phoenix-Specific Calibration**:
All costs and rules are specifically calibrated for Phoenix municipal standards and climate conditions. Different cities would require different calibration values.

## 🎯 **Bottom Line**

**Simple Version**: After we identify the top 5 parks that need help most, we recommend specific cooling solutions (trees, shade structures, cool pavement) based on each park's conditions. We use Phoenix's actual cost data for trees and ramadas, with planning estimates for other solutions, to make credible budget recommendations that help decision-makers understand investment requirements.

**Technical Version**: Our cooling solution recommendation system uses a rule-based approach to match park conditions to appropriate cooling investments. The system evaluates the top 5 priority parks against ParkSense planning thresholds for tree planting, ramada construction, and cool pavement treatment. Cost calculations use Phoenix-verified data for trees and ramadas, with planning estimates for cool pavement and other parameters. The model generates specific, actionable recommendations with upfront costs, annual maintenance, and water requirements to support budget optimization and investment decision-making.

---

*Powered by ParkSense Cooling Solution Recommendation Model v3 - Phoenix-referenced intervention planning model with verified costs for trees and ramadas, planning estimates for cool pavement and rule thresholds. Evidence levels: Phoenix-verified costs (🟢), ParkSense planning assumptions (🟡), FortyGuard measurements (🔵).*