> **Note**: Throughout this README, "intervention" refers to cooling solutions - practical strategies to make parks cooler and reduce temperature. The term "intervention" is used in the codebase and database for technical precision, but "cooling solutions" is used here for clearer understanding.

# Heat Analysis System

## 📍 What This Is
Our heat analysis system identifies hot spots in Phoenix parks by analyzing temperature data across selected geographic areas. This is the foundation for prioritizing which parks need heat mitigation cooling solutions.

## 🗺️ How It Works

### 1. Area Selection
**Users Define Analysis Zones**
- Draw polygons on the map to select areas for heat analysis
- System identifies which Phoenix parks fall within the selected area
- Handles slight mismatches between official park boundaries and Google Maps positioning

### 2. Heat Analysis Request
**Send to FortyGuard API with Optimized Parameters**
- **Spatial Resolution**: 60-meter granularity for detailed temperature mapping
- **Analysis Type**: Temperature snapshot (TCM) for actual temperature readings
- **Time Range**: Past 7 days during peak heat hours (8AM-10PM daily)
- **Area**: Polygon boundary defining the analysis zone

### 3. Tile-Park Matching
**Connect Heat Data to Park Boundaries**
- API returns heat map tiles covering the selected area
- System matches tiles to park boundaries using spatial intersection
- Each park gets temperature readings from overlapping tiles
- Uses efficient bounding box method for fast, reliable matching

### 4. Park Heat Metrics
**Calculate Temperature Profiles for Each Park**
- **Average Temperature**: Overall heat level across the park
- **Minimum Temperature**: Coolest areas within the park
- **Maximum Temperature**: Hottest spots in the park
- **Tile Count**: Number of temperature data points (indicates data density)

### 5. Critical Park Selection
**Identify Parks Requiring Immediate Attention**
- Parks ranked by average temperature (hottest first)
- Top parks selected for deeper environmental and satellite analysis
- Currently analyzes top 3 hottest parks (configurable)
- These parks become focus for cooling solution recommendations

## 🎯 Why This Approach

**Optimized API Parameters:**
- **60m Resolution**: Detailed enough for park-level analysis, efficient for processing
- **Temperature Snapshot (TCM)**: Actual temperature readings, not modeled data
- **Peak Hours**: Captures worst-case heat scenarios for realistic analysis

**Efficient Matching:**
- **Bounding Box Method**: Fast, reliable intersection for tile-park matching
- **Future-Ready**: Can upgrade to exact polygon intersection for pixel-perfect accuracy
- **Scalable**: Handles large analysis areas efficiently

**Strategic Selection:**
- **Hottest Parks First**: Focus resources where heat impact is greatest
- **Data-Driven**: Prioritization based on actual temperature measurements
- **Actionable**: Identifies parks that will benefit most from cooling solutions

## 📊 What We Get

### For Each Analysis Area
- **Heat Map Tiles**: Detailed temperature data across the selected zone
- **Temperature Statistics**: Overall heat patterns and distribution
- **Affected Parks**: List of parks within the analysis area

### For Each Park
- **Heat Profile**: Average, minimum, maximum temperatures
- **Data Density**: Number of temperature measurements
- **Ranking**: Position in heat severity ranking

## 🔧 Technical Implementation

### API Request Structure
```json
{
  "polygon_aoi": {
    "type": "FeatureCollection",
    "features": [{
      "type": "Feature",
      "geometry": {
        "type": "Polygon",
        "coordinates": [[/* user-drawn polygon */]]
      }
    }]
  },
  "date_time": {
    "start_date": "YYYY-MM-DD",
    "start_time": "08:00",
    "end_time": "22:00",
    "filter_type": 2
  },
  "granularity": 60,
  "analytic_type": "tcm"
}
```

### Data Processing Pipeline
1. **User Input**: Draw polygon on map
2. **Park Selection**: Find parks within the area
3. **API Request**: Send optimized parameters to FortyGuard
4. **Tile Matching**: Match heat tiles to park boundaries
5. **Metric Calculation**: Calculate temperature statistics per park
6. **Ranking**: Sort parks by temperature severity
7. **Selection**: Identify top parks for detailed analysis

## 💡 Business Value

**Enables:**
✅ **Accurate Heat Mapping**: Precise temperature data across Phoenix parks
✅ **Data-Driven Prioritization**: Rank parks by actual heat severity
✅ **Focused Analysis**: Concentrate resources on hottest areas
✅ **Intervention Targeting**: Match solutions to specific heat profiles
✅ **Scalable System**: Analyze any geographic area in Phoenix

## 🎯 Integration with Analysis System

**Foundation for Subsequent Analysis:**
- **Environmental Analysis**: Critical parks get detailed environmental assessment
- **Satellite Analysis**: Satellite imagery for hottest parks
- **Priority Scoring**: Heat data feeds into overall park priority scores
- **Intervention Recommendations**: Heat-specific cooling strategies
- **Budget Optimization**: Focus investments on highest-heat areas

## 📋 Key Parameters

| Parameter | Value | Purpose |
|-----------|-------|---------|
| **Granularity** | 60m | Spatial resolution for detailed analysis |
| **Analytic Type** | TCM | Temperature snapshot (actual readings) |
| **Time Range** | 7 days | Representative heat patterns |
| **Peak Hours** | 8AM-10PM | Worst-case heat scenarios |
| **Matching Method** | Bounding Box | Efficient tile-park intersection |
| **Critical Parks** | Top 3 | Focus on hottest areas |

## 🔍 Data Quality

**Source Authority:**
- **API**: FortyGuard Heatmap API (industry-standard thermal data)
- **Methodology**: Large Temperature Models (LTMs) for accurate predictions
- **Resolution**: High-resolution thermal mapping
- **Currency**: Near real-time data with historical analysis capability

## 🎯 Bottom Line

**Simple Version**: We draw areas on the map, get detailed temperature data from the FortyGuard API, match it to our park boundaries, and identify the hottest parks that need immediate cooling solutions.

**Technical Version**: Our system uses 60-meter resolution temperature snapshots from the FortyGuard API, matches heat tiles to park boundaries using efficient spatial intersection, calculates comprehensive temperature metrics for each park, and ranks them by heat severity to identify the critical parks requiring focused environmental and satellite analysis.

---

*Powered by FortyGuard Heatmap API with optimized parameters for urban heat analysis. Uses bounding box intersection for efficient tile-park matching with future upgrade path to exact polygon intersection.*