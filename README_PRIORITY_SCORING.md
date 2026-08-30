# Priority Scoring System

> **Note**: Throughout this README, "intervention" refers to cooling solutions - practical strategies to make parks cooler and reduce temperature. The term "intervention" is used in the codebase and database for technical precision, but "cooling solutions" is used here for clearer understanding.

## 📍 What This Is
Our priority scoring system combines multiple data sources to rank Phoenix parks by their overall need for heat mitigation cooling solutions. This intelligent ranking ensures limited resources are focused on parks that will benefit most from cooling investments.

## 🎯 How Priority Scoring Works

### **5-Factor Analysis**
Each park receives scores across 5 key factors, combined into a single priority score:

**1. Heat Severity (40% weight)**
- **Source**: Heat analysis temperature data
- **What It Measures**: How hot the park actually is
- **Why Important**: This is the primary problem we're solving

**2. Environmental Stress (20% weight)**
- **Source**: Environmental analysis data
- **What It Measures**: Heat index, humidity, solar exposure
- **Why Important**: Environmental conditions compound heat effects

**3. Physical Condition (15% weight)**
- **Source**: Satellite imagery analysis
- **What It Measures**: Tree canopy, surface types, land cover
- **Why Important**: Current park condition affects improvement potential

**4. Park Importance (15% weight)**
- **Source**: Park characteristics data
- **What It Measures**: Park size, type, amenities, community value
- **Why Important**: More important parks deserve more attention

**5. Cooling Solution Opportunity (10% weight)**
- **Source**: Satellite imagery analysis
- **What It Measures**: Potential for improvement through cooling solutions
- **Why Important**: Some parks have greater improvement potential

## � Detailed Factor Analysis

### **1. Heat Severity (40% weight)**

**What It Measures:**
The actual temperature conditions in each park based on heat analysis data.

**Data Source:**
- **Primary**: Park heat analysis results (FortyGuard Heatmap API)
- **Metric**: Average temperature from heat tiles that overlap park boundaries
- **Time Period**: Past 7 days, 8AM-10PM daily (peak heat hours)

**How It's Calculated:**
- **Input**: Park's average temperature from heat analysis
- **Normalization**: 30°C = 0 score, 45°C = 100 score
- **Formula**: Linear normalization based on Phoenix summer climate range
- **Logic**: Parks with temperatures closer to 45°C get higher scores

**Why This Threshold:**
- **30°C**: Below Phoenix's normal summer temperatures (NWS July normal: 41.1°C)
- **45°C**: Approaches Phoenix's extreme heat conditions (113°F)
- **Based on**: NWS Phoenix climate records and Phoenix heat response plan

**Example:**
- Park at 35°C → Score: 33.3 (moderate heat severity)
- Park at 42°C → Score: 80.0 (high heat severity)
- Park at 44°C → Score: 93.3 (extreme heat severity)

---

### **2. Environmental Stress (20% weight)**

**What It Measures:**
How environmental conditions compound heat stress - humidity, heat index, solar exposure, and wet bulb temperature.

**Data Source:**
- **Primary**: Environmental analysis results (FortyGuard Environmental Parameters API)
- **Metrics**: Heat index, humidity, wet bulb temperature, solar irradiance
- **Time Period**: Past 7 days, 8AM-10PM daily (same as heat analysis)

**How It's Calculated:**
- **Heat Index (50% weight)**: Maximum value during 8AM-6PM window
- **Wet Bulb (25% weight)**: Average value during 8AM-6PM window  
- **Humidity (15% weight)**: Average value during 8AM-6PM window
- **Solar Irradiance (10% weight)**: Single clear-sky GHI value
- **Formula**: Weighted average of normalized environmental metrics

**Environmental Normalization Ranges:**
- **Heat Index**: 35-45°C (Phoenix thermal stress range)
- **Wet Bulb**: 20-25°C (Phoenix outdoor design conditions)
- **Humidity**: 10-40% (accounts for monsoon humidity spikes)
- **Solar GHI**: 400-1000 W/m² (Phoenix peak solar exposure)

**Why These Metrics:**
- **Heat Index**: Combines temperature + humidity for "feels like" temperature
- **Wet Bulb**: Measures cooling potential through evaporation
- **Humidity**: High humidity reduces cooling effectiveness
- **Solar**: Direct sun exposure increases heat load

**Example:**
- High heat index (42°C) + high solar (800 W/m²) → High environmental stress
- Low humidity (15%) + moderate solar (500 W/m²) → Lower environmental stress

---

### **3. Physical Condition (15% weight)**

**What It Measures:**
The current physical state of the park based on satellite imagery - tree canopy coverage, hard surfaces, and land cover classification.

**Data Source:**
- **Primary**: Satellite analysis results (FortyGuard Satellite Segmentation API)
- **Metrics**: Land cover classification percentages
- **Classes**: Vegetation (tree, plant, grass), Hard surfaces (building, road, route), Bare ground (earth, ground)

**How It's Calculated:**
- **Vegetation Coverage**: Sum of tree, plant, grass classes from satellite segmentation
- **Hard Surface Coverage**: Sum of building, road, route classes from satellite segmentation
- **Formula**: 
  - If no vegetation data: Score = hard surface percentage
  - If vegetation data exists: Score = (vegetation deficit × 0.5) + (hard surface × 0.5)
  - Vegetation deficit = 100 - vegetation percentage

**Why This Logic:**
- **Low Vegetation**: Parks with less natural shade have higher priority
- **High Hard Surfaces**: More pavement/buildings = more heat absorption
- **Combined Score**: Balances current vegetation deficit with hard surface impact

**Example:**
- Park with 10% vegetation, 60% hard surface → Score: 85 (poor condition)
- Park with 40% vegetation, 20% hard surface → Score: 45 (moderate condition)
- Park with 70% vegetation, 10% hard surface → Score: 20 (good condition)

---

### **4. Park Importance (15% weight)**

**What It Measures:**
The relative importance of each park based on its type, size, and amenities. Parks that serve more people or have greater community impact get higher importance scores.

**Data Source:**
- **Primary**: Park characteristics database (Phoenix GIS data)
- **Metrics**: Park type, acreage, facility amenities
- **Data**: Park type classification, amenity availability

**How It's Calculated:**
- **Base Score**: Park type importance (Regional=30, Community=20, Neighborhood=15, etc.)
- **Facility Points**: Playground (+20), Splash pads (+15), Swimming pool (+15), Sports complex (+10), Recreation center (+5), Shade structures (+5)
- **Formula**: Base park type score + sum of available facility points
- **Cap**: Maximum score of 100

**Park Type Scores:**
- **Regional (30)**: Largest service area, most users
- **Community (20)**: Medium service area, significant users
- **Neighborhood (15)**: Local service area, community focus
- **Pocket (10)**: Small area, limited users
- **Natural (15)**: Environmental value
- **Linear (5)**: Limited service area (trails, corridors)

**Why These Values:**
- **Park Type**: Based on Phoenix Parks Standards and service area classifications
- **Playground High**: Outdoor areas with concentrated user activity
- **Pool/Splash Pad**: Existing cooling/recreation assets with high summer use
- **Shade Structures**: Already has some cooling, lower immediate need

**Example:**
- Regional park with playground + pool → Score: 65 (high importance)
- Neighborhood park with playground → Score: 35 (moderate importance)
- Pocket park with no facilities → Score: 10 (low importance)

---

### **5. Cooling Solution Opportunity (10% weight)**

**What It Measures:**
The potential for improvement through cooling solutions based on current satellite imagery. Parks with poor current conditions but space for improvement get higher opportunity scores.

**Data Source:**
- **Primary**: Satellite analysis results (FortyGuard Satellite Segmentation API)
- **Metrics**: Vegetation percentage, hard surface percentage, park acreage
- **Derived**: Same satellite data used for physical condition, but interpreted for improvement potential

**How It's Calculated:**
- **Vegetation Opportunity**: 100 - current vegetation percentage (more room for improvement)
- **Hard Surface Opportunity**: Current hard surface percentage (opportunity for cool pavement)
- **Acreage Opportunity**: Park size / 20 × 100 (capped at 100)
- **Formula**: (vegetation opportunity × 0.35) + (hard surface opportunity × 0.35) + (acreage opportunity × 0.30)

**Why This Logic:**
- **Low Vegetation**: High opportunity for tree planting and green space
- **High Hard Surfaces**: Opportunity for cool pavement and reflective materials
- **Park Size**: Larger parks have more space for cooling investments

**Example:**
- 5-acre park, 10% vegetation, 60% hard surface → Score: 62.5 (high opportunity)
- 20-acre park, 30% vegetation, 30% hard surface → Score: 42.5 (moderate opportunity)
- 2-acre park, 50% vegetation, 20% hard surface → Score: 25.0 (lower opportunity)

---

## ⚖️ Physical Condition vs. Cooling Opportunity

- **Physical Condition (15%)** = "How bad is this park now?" — Low vegetation + high hard surfaces = higher score
- **Cooling Opportunity (10%)** = "How much can we improve it?" — Room for new trees + space for cool pavement + park size = higher score
- **Together**: They ensure we invest in parks that both *need* help and *can benefit* from it

---

## 📊 How Scores Are Combined

### **Weighted Final Score**
**Formula:**
```
Final Priority Score = 
  (Heat Severity × 0.40) +
  (Environmental Stress × 0.20) +
  (Physical Condition × 0.15) +
  (Park Importance × 0.15) +
  (Cooling Solution Opportunity × 0.10)
```

**Example Calculation:**
- Heat Severity: 80.0
- Environmental Stress: 65.0
- Physical Condition: 45.0
- Park Importance: 70.0
- Cooling Solution Opportunity: 60.0

**Final Score:** (80 × 0.40) + (65 × 0.20) + (45 × 0.15) + (70 × 0.15) + (60 × 0.10) = 32 + 13 + 6.75 + 10.5 + 6 = **68.25**

## 🎯 Why This Approach

**Evidence-Based Calibration:**
- **Phoenix Climate Data**: Thresholds based on NWS Phoenix climate records
- **Phoenix Heat Response Framework**: Aligns with city's heat-risk approach
- **Phoenix Shade Planning Standards**: Incorporates city's urban forest goals
- **Thermal Comfort Standards**: Uses ASHRAE/CDC guidance for heat stress

**Balanced Weighting:**
- **Heat Dominant**: 40% weight ensures temperature is the primary factor
- **Context Matters**: Environmental and physical factors provide important context
- **Impact Considered**: Park importance and opportunity guide investment decisions

**Model Flexibility:**
- **Configurable**: Weights and thresholds can be adjusted based on results
- **Transparent**: Clear scoring methodology shows how decisions are made
- **Improvable**: Can be refined as more data becomes available

## 🔧 Technical Implementation

### **Scoring Pipeline**
1. **Data Validation**: Ensure complete environmental and satellite data for each park
2. **Component Scoring**: Calculate each of the 5 factor scores individually
3. **Weighted Combination**: Apply configured weights to final score
4. **Ranking**: Sort parks by final priority score (highest first)
5. **Storage**: Save results with full calculation evidence

### **Configuration**
All thresholds and weights are configured in `config/park_heat.php`:
- Temperature thresholds: 30-45°C (Phoenix summer range)
- Environmental thresholds: Calibrated for Phoenix desert climate
- Priority weights: ParkSense planning model (40% heat dominant)
- Satellite classifications: Based on FortyGuard API categories

## 💡 Business Value

**Enables:**
✅ **Data-Driven Prioritization**: Rank parks by comprehensive need, not just temperature
✅ **Resource Optimization**: Focus investments where they'll have most impact
✅ **Context-Aware Decisions**: Consider environmental conditions and park characteristics
✅ **Transparency**: Clear scoring methodology builds trust
✅ **Adaptability**: Configurable model can be refined over time

## 📚 References & Data Sources

**City of Phoenix Heat Mitigation:**
- [Shade Phoenix / Tree Shade Programs](https://www.phoenix.gov/administration/departments/heat/tree-shade-programs.html)
- [Phoenix Urban Forest / Shade Phoenix Plan](https://www.phoenix.gov/administration/departments/parks/about-us/phoenixs-urban-forest.html)
- [Phoenix Heat Response Plan](https://www.phoenix.gov/administration/departments/heat/heat-response-programs/heat-response-plan.html)

**Climate Data:**
- [NWS Phoenix Heat Information](https://www.weather.gov/psr/Heat)
- [NWS Phoenix Climate Records](https://www.weather.gov/psr/PHX_July_4th.html)

**Planning Standards:**
- [Phoenix Parks Standards](https://www.phoenix.gov/content/dam/phoenix/pddsite/documents/planning-zoning-pz/pdd_pz_pdf_00342.pdf)
- [Phoenix Sustainable Development](https://www.phoenix.gov/content/dam/phoenix/pddsite/documents/planning-zoning-pz/pdd_pz_pdf_00169.pdf)

**Thermal Standards:**
- [ASHRAE Standard 55 - Thermal Environmental Conditions](https://www.ashrae.org/technical-resources/bookstore/standard-55-thermal-environmental-conditions-for-human-occupancy)
- [CDC/NIOSH Heat Index Guidance](https://www.cdc.gov/niosh/bulletin/2017/heat-index.html)

## 🎯 Bottom Line

We combine heat data, environmental conditions, satellite imagery, and park characteristics to rank parks by their overall need for cooling solutions. Parks with higher priority scores get more attention and investment because they need it most and will benefit most from cooling improvements.

All thresholds are calibrated for Phoenix's desert climate. Different cities would require different calibration values — the model is fully configurable via `config/park_heat.php`.

---

*Powered by ParkSense Priority Model — Phoenix-calibrated planning model for urban heat mitigation.*