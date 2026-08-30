# Environmental & Satellite Analysis

> **Note**: Throughout this README, "intervention" refers to cooling solutions - practical strategies to make parks cooler and reduce temperature. The term "intervention" is used in the codebase and database for technical precision, but "cooling solutions" is used here for clearer understanding.

## 📍 What This Is
After identifying the hottest parks through heat analysis, we perform detailed environmental and satellite analysis specifically for these critical parks. This provides the comprehensive data needed for intelligent priority scoring and targeted cooling solution recommendations.

## 🎯 Why These Specific Parks

**Focus on Critical Areas:**
- **Heat Analysis Results**: We analyze the top parks identified as hottest from the heat analysis
- **Resource Efficiency**: Concentrate detailed analysis on parks that need immediate attention
- **Data Consistency**: Use the same time periods across all analysis types for accurate comparison
- **Strategic Prioritization**: Focus resources where heat impact is greatest

**Why Not All Parks:**
- **Cost Efficiency**: Environmental and satellite analysis requires API resources
- **Time Savings**: Detailed analysis takes time - focus on high-impact areas first
- **Actionable Results**: Detailed data is most valuable for the hottest parks requiring cooling solutions

## 🌡️ Environmental Analysis

### What We Analyze
**Comprehensive Environmental Parameters for Each Critical Park:**
- **Heat Index**: "Feels like" temperature considering humidity
- **Apparent Temperature**: Perceived temperature for human comfort
- **Wet Bulb Temperature**: Cooling potential through evaporation
- **Relative Humidity**: Moisture levels affecting heat stress
- **Air Quality**: PM2.5, PM10, ozone, and other pollutants
- **Solar Irradiance**: Solar energy potential for cooling solutions

### How It Works
**For Each Critical Park:**
1. **Park Selection**: Use coordinates from hottest parks identified in heat analysis
2. **Temperature Data**: Send the park's average temperature from heat analysis
3. **Time Consistency**: Use same time parameters as heat analysis (past 7 days, 8AM-10PM)
4. **API Request**: Send to FortyGuard Environmental Parameters API
5. **Data Collection**: Receive comprehensive environmental metrics
6. **Storage**: Save results linked to park and heat analysis

### API Parameters Used
```json
{
  "latitude": "park coordinates",
  "longitude": "park coordinates", 
  "temperature": "average from heat analysis",
  "date_time": {
    "start_date": "past 7 days",
    "start_time": "08:00",
    "end_time": "22:00",
    "filter_type": 2
  }
}
```

## 🛰️ Satellite Analysis

### What We Analyze
**Satellite Imagery with Detailed Segmentation:**
- **Tree Canopy Coverage**: Percentage of vegetation and green space
- **Impervious Surfaces**: Roads, buildings, and hard surfaces
- **Land Cover Classification**: Detailed breakdown of surface types
- **Vegetation Health**: Assessment of plant condition and density
- **Surface Materials**: Understanding of heat-absorbing vs. cooling surfaces

### How It Works
**For Each Critical Park:**
1. **Park Selection**: Use same hottest parks from heat analysis
2. **Satellite Imagery**: Request satellite view for park coordinates
3. **Time Consistency**: Use same time parameters as heat analysis
4. **AI Segmentation**: FortyGuard AI analyzes satellite imagery
5. **Classification**: Break down land cover into categories
6. **Storage**: Save segmentation data linked to park and heat analysis

### API Parameters Used
```json
{
  "sat": {
    "latitude": "park coordinates",
    "longitude": "park coordinates"
  },
  "date_time": {
    "start_date": "past 7 days", 
    "start_time": "08:00",
    "end_time": "22:00",
    "filter_type": 2
  },
  "granularity": 80
}
```

## 🎯 How This Helps Priority Scoring

### Environmental Data → Environmental Stress Score
**Environmental Stress Component:**
- **Heat Index**: High heat index = higher environmental stress
- **Humidity**: High humidity = less effective cooling = higher stress
- **Air Quality**: Poor air quality compounds heat stress
- **Solar Irradiance**: High solar exposure = higher heat load

**Why It Matters:**
> "Parks with high environmental stress need different cooling solutions than parks with low stress. A park with poor air quality and high humidity requires more aggressive cooling strategies than a park with clean air and good ventilation."

### Satellite Data → Physical Condition & Cooling Solution Opportunity

**Physical Condition Component:**
- **Tree Canopy**: Low vegetation = poor current condition = higher priority
- **Impervious Surfaces**: High hard surfaces = heat absorption = higher priority
- **Land Cover Mix**: Balance of surfaces affects current park condition

**Cooling Solution Opportunity Component:**
- **Vegetation Potential**: Parks with low tree canopy have high improvement potential
- **Surface Optimization**: High impervious surfaces = opportunity for cool pavement
- **Space Availability**: Parks with space for new cooling solutions get higher opportunity scores

**Why It Matters:**
> "Satellite analysis tells us not just the current condition, but also the potential for improvement. A park with 10% tree canopy has much higher cooling solution opportunity than a park with 50% canopy, even if both have similar heat levels."

## 📊 Data Integration

### Complete Picture for Each Park
**Before Priority Scoring:**
- **Heat Data**: Average, min, max temperatures from heat analysis
- **Environmental Data**: Heat index, humidity, air quality, solar data
- **Satellite Data**: Tree canopy, impervious surfaces, land cover classification
- **Park Characteristics**: Amenities, size, type from original park data

### Priority Scoring Formula
**5 Components Combined:**
1. **Heat Severity** (from heat analysis)
2. **Environmental Stress** (from environmental analysis)
3. **Physical Condition** (from satellite analysis)
4. **Park Importance** (from park data)
5. **Cooling Solution Opportunity** (from satellite analysis)

## 🔧 Technical Implementation

### Analysis Pipeline
1. **Heat Analysis Completion**: Must finish before environmental/satellite analysis
2. **Top Park Selection**: Get hottest parks from heat analysis results
3. **Parallel Processing**: Environmental and satellite analysis run concurrently
4. **API Polling**: Automatically check status until results are ready
5. **Data Storage**: Save results linked to park and heat analysis
6. **Validation**: Ensure complete data before priority scoring

### Database Storage
**Environmental Metrics Table:**
- Park ID, heat analysis ID, activity ID
- Complete environmental parameter data
- Status tracking (pending/completed/failed)

**Satellite Metrics Table:**
- Park ID, heat analysis ID, activity ID  
- Satellite imagery and segmentation data
- Status tracking (pending/completed/failed)

## 💡 Business Value

**Enables:**
✅ **Intelligent Prioritization**: Multi-factor analysis for accurate park ranking
✅ **Targeted Cooling Solutions**: Match solutions to specific environmental conditions
✅ **Resource Optimization**: Focus analysis on parks that need it most
✅ **Comprehensive Understanding**: Complete picture of each park's heat situation
✅ **Data-Driven Decisions**: Priorities based on multiple data sources, not just temperature

## 📋 Key Parameters

| Analysis | Time Range | Filter Type | Purpose |
|----------|-----------|-------------|---------|
| **Environmental** | Past 7 days | Range of hours (2) | Comprehensive environmental profile |
| **Satellite** | Past 7 days | Range of hours (2) | Land cover and vegetation analysis |
| **Heat Analysis** | Past 7 days | Range of hours (2) | Temperature mapping |

## 🔍 Data Quality

**Source Authority:**
- **Environmental API**: FortyGuard Environmental Parameters (comprehensive weather data)
- **Satellite API**: FortyGuard Satellite Segmentation (AI-powered land cover analysis)
- **Consistency**: Same time periods across all analysis types
- **Current Data**: Near real-time with historical analysis capability

## 🎯 Integration with Priority Scoring

**Required Components:**
- **Environmental Analysis**: Required for environmental stress scoring
- **Satellite Analysis**: Required for physical condition and cooling solution opportunity scoring
- **Heat Analysis**: Required for heat severity scoring
- **Park Data**: Required for park importance scoring

**Validation:**
- Priority scoring only runs when all analyses are complete
- Parks missing any data are skipped from priority calculation
- Ensures comprehensive, fair park comparisons

## 🎯 Bottom Line

After finding the hottest parks, we analyze their environmental conditions and satellite imagery to understand why they're hot and what can be done about it. This detailed data helps us rank parks by their overall need for cooling solutions and recommend the most effective strategies.

---

*Powered by FortyGuard Environmental Parameters API and Satellite Segmentation API.*