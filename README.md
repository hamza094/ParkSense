# ParkSense 🌳☀️

> **Urban Heat Intelligence Platform for Phoenix Parks**
> 
> ParkSense analyzes urban heat in Phoenix parks using satellite thermal data, AI land-cover segmentation, and environmental metrics — then recommends budget-optimized cooling solutions (trees, shade structures, cool pavement) backed by real Phoenix municipal costs.

> [!NOTE]
> Throughout this project, **"intervention"** in the codebase and database refers to **cooling solutions** — practical strategies like tree planting, shade structures (ramadas), and cool pavement to reduce park temperatures.

## 🎬 Demo

[![Watch Demo](https://img.shields.io/badge/Watch-3min%20Demo-red?logo=loom)](https://www.loom.com/share/0175db19449b4d37a1ccee5d8501cd9a)
[![Live Demo](https://img.shields.io/badge/Live-Demo-brightgreen)](https://parksense-production-n7ta4y.laravel.cloud/)

---

## ⚠️ Important Notes for Evaluators & Local Development

1. **Google Maps API Key**: A valid Google Maps API Key is **required** for map and polygon-drawing functionality.
2. **Live FortyGuard API**: This app queries live FortyGuard APIs. Occasionally, requests may need a retry if the server is slow.
3. **Environmental API**: The Environmental Parameters API polling may sometimes return incomplete results (e.g., data for 2 out of 3 parks). This is a limitation of the external FortyGuard API, not our application. **If this happens, refresh the page and re-select the polygon.**
4. **Satellite Processing**: The Satellite AI Segmentation API works correctly but takes longer to process. **Please be patient** — the latency is from FortyGuard's processing, not our end.
5. **Top 3 Parks Limit**: By default, ParkSense analyzes the **top 3 hottest parks** for environmental and satellite analysis. To change this, edit the limit in:
   - `app/Http/Controllers/EnvironmentalAnalysisController.php`
   - `app/Http/Controllers/SatelliteAnalysisController.php`
6. **Flatland Parks Only**: The dataset covers **189 flatland municipal parks** in Phoenix. Mountain preserves and desert parks are excluded — cooling solutions like ramadas and cool pavement are only feasible on flat terrain.

---

## 🏆 Hackathon Judging Alignment

| Criterion | Weight | How ParkSense Addresses It |
|-----------|--------|----------------------------|
| **Impact** | 40% | Solves real Phoenix heat mortality risk for 189 parks. Output is a ready-to-use investment plan referencing Phoenix's $1.5M Neighborhood Parks Enhancement Program budget |
| **Technical Execution** | 35% | Laravel 11 service architecture, MySQL spatial ST_Intersects queries, async multi-park FortyGuard polling, 5-factor weighted scorer, Knapsack DP budget optimizer |
| **Innovation** | 15% | End-to-end pipeline from thermal map → satellite segmentation → priority score → budget plan. Smart budget allocation engine that maximizes cooling impact within municipal funding limits |
| **Communication** | 10% | 3-tier evidence framework (🟢🟡🔵), transparent config, 6 specialized READMEs, every number linked to source |

---

## 🔵 FortyGuard API Integration

| FortyGuard API | How ParkSense Uses It |
|---------------|----------------------|
| **Heatmap / TCM** | 60m thermal tiles submitted via polygon AOI → matched to park boundaries → average temp per park |
| **Environmental Parameters** | Heat index, humidity, wet bulb, solar GHI per park → feeds Environmental Stress score (20% weight) |
| **Satellite Segmentation** | AI land-cover analysis → tree %, hard surface %, bare ground % → feeds Physical Condition (15%) + Opportunity (10%) scores |
| **Status Polling `/status/{id}`** | Async polling for all 3 parks in parallel using `Set`-based activity ID tracking — resolves only when all parks complete |

---

## 💡 How ParkSense Works (6-Step Pipeline)

ParkSense runs a 6-step analysis pipeline, each step building on the previous:

### Step 1: Heat Analysis
Draw a polygon on the map → ParkSense finds which parks fall inside it → FortyGuard returns 60m thermal tiles → we calculate average temperature per park.

### Step 2: Environmental Analysis
For the top 3 hottest parks → FortyGuard returns heat index, humidity, wet bulb temperature, and solar irradiance data → used for Environmental Stress scoring.

### Step 3: Satellite Analysis
For the same top 3 parks → FortyGuard AI segments satellite imagery into land-cover classes (tree, grass, road, building, bare ground) → used for Physical Condition and Cooling Opportunity scoring.

### Step 4: Priority Scoring (5-Factor Weighted Model)
Each park gets a score from 0–100 combining:

| Factor | Weight | Data Source |
|--------|--------|-------------|
| Heat Severity | 40% | Heat analysis temperature |
| Environmental Stress | 20% | Environmental analysis metrics |
| Physical Condition | 15% | Satellite land-cover analysis |
| Park Importance | 15% | Park type, size, amenities |
| Cooling Opportunity | 10% | Satellite + park size data |

### Step 5: Cooling Solution Recommendations
For the top 5 priority parks, ParkSense recommends specific cooling solutions based on rule matching:

| Cooling Solution | When Recommended | Cost Source |
|-----------------|-----------------|-------------|
| 🌳 **Tree Planting** (25/50/100 trees) | Heat severity ≥ 50, vegetation ≤ 45% | $1,050/tree — Phoenix Shade Plan 🟢 |
| 🏗️ **Shade Ramada** | Heat ≥ 50, has playground, no existing shade | $60K — Phoenix NPEP 🟢 |
| 🛣️ **Cool Pavement** | Heat ≥ 50, hard surface ≥ 25% | $3/sqft on 10% of hard surface 🟡 |

### Step 6: Budget Optimization (Knapsack Algorithm)
Given a budget (default: $1.5M from Phoenix's real NPEP program), ParkSense uses a **Multiple-Choice Knapsack Algorithm** to select the combination of cooling solutions across all parks that maximizes total cooling benefit while staying within budget. One solution per park, never exceeds budget.

---

## 🌍 Global Potential: Real-World Impact in South Asia

While built for Phoenix, ParkSense is designed for universal geographic adaptability — especially impactful in South Asia (India, Pakistan, Bangladesh):

* **Extreme Heat Vulnerability**: South Asian cities face deadly heatwaves exceeding 45–50°C, with dense populations lacking indoor cooling.
* **Budget Scarcity**: The Knapsack Budget Optimizer is most valuable where budgets are tightest — it mathematically ensures every dollar achieves maximum cooling impact.
* **Socioeconomic Targeting**: By adjusting scoring weights, cities can route cooling resources to communities that need them most.

---

## 🏗️ Architecture & Technology Stack

```
┌─────────────────────────────────────────────────────────────────┐
│                       Frontend Layer                            │
│    Vue 3 (Composition API) + TypeScript + Tailwind CSS v4       │
│    Shadcn/UI Components + Google Maps JavaScript API            │
└─────────────────────────────────┬───────────────────────────────┘
                                  │ Inertia.js Bridge
┌─────────────────────────────────▼───────────────────────────────┐
│                       Backend Layer (PHP 8.2+)                  │
│    Laravel 11 + Service-Calculator Pattern                      │
│    Eloquent Spatial (MySQL ST_Intersects / Geometry)            │
└──────────────┬──────────────────────────────────┬───────────────┘
               │                                  │
┌──────────────▼──────────────┐    ┌──────────────▼───────────────┐
│      External APIs          │    │       Database Layer         │
│  - FortyGuard Heatmap API   │    │  - MySQL 8.0+ / Spatial GIS  │
│  - FortyGuard Satellite API │    │  - Spatial Geometry Indexing │
│  - FortyGuard Env API       │    │  - Full Relational Cascade   │
│  - Google Maps Drawing      │    └──────────────────────────────┘
└─────────────────────────────┘
```

### Core Technologies
- **Backend**: [Laravel 11](https://laravel.com/) with Action classes and Domain Services
- **Frontend**: [Vue 3](https://vuejs.org/) (Composition API, TypeScript)
- **SPA Bridge**: [Inertia.js v2](https://inertiajs.com/)
- **Styling**: [Tailwind CSS v4](https://tailwindcss.com/) + shadcn-vue
- **Spatial**: `matanyadaev/laravel-eloquent-spatial` for MySQL OpenGIS
- **APIs**: FortyGuard Client with retry handlers + async polling composables

---

## 📊 Data Sources & Evidence Framework

All numbers in ParkSense are classified by evidence level:

| Level | Label | Examples |
|-------|-------|----------|
| 🟢 | **Phoenix Verified** | $1,050/tree, $40K–$80K ramadas, $1.5M NPEP budget |
| 🟡 | **Planning Assumptions** | 10% cool pavement coverage, tree package sizes, rule thresholds |
| 🔵 | **FortyGuard Measured** | 60m thermal tiles, land-cover %, solar GHI |

**Primary References:**
1. [Phoenix Shade Plan](https://www.phoenix.gov/content/dam/phoenix/heatsite/documents/BP_ShadePhoenixPlan_Report_031025_EN.pdf) — Tree costs ($750 + $300 irrigation)
2. [Phoenix NPEP](https://www.phoenix.gov/administration/departments/parks/about-us/improvement-projects/neighborhood-parks-enhancement-program.html) — Ramada costs, $1.5M program budget
3. ASU / City of Phoenix Cool Pavement Study — Surface temperature reduction data

---

## 📁 Project Structure

```
parksense/
├── app/
│   ├── Actions/        # Single-purpose action classes (Heatmap requests & management)
│   ├── Http/           # HTTP controllers (Analysis triggers, priority scoring, budget optimization)
│   ├── Models/         # Eloquent Models & spatial GIS geometry casts
│   └── Services/       # Core domain services (Knapsack Budget Optimizer, Priority Scoring, FortyGuard Client)
├── config/             # Weights, temperature thresholds, and cooling benefit evidence maps
├── database/           # Schema migrations & Phoenix GeoJSON park GIS boundary seeders
├── resources/js/       # SPA application layer (Vue 3, Shadcn-Vue, Google Maps API)
│   ├── components/     # Reusable dashboard widgets, layout containers, and drawing map components
│   ├── composables/    # State management hooks for asynchronous FortyGuard API polling
│   └── pages/          # Primary SPA pages (Dashboard project list and analysis detail cockpit)
└── routes/web.php      # Application routes and web endpoints
```

---

## 💻 Local Development Setup

### Prerequisites
- **PHP**: 8.2+, **Composer**: 2.x, **Node.js**: 20+, **MySQL**: 8.0+

### Installation

```bash
git clone https://github.com/hamza094/parksense.git
cd parksense
composer install && npm install
cp .env.example .env && php artisan key:generate
```

Set in `.env`:
```env
VITE_GOOGLE_MAPS_API_KEY=your_google_maps_api_key
FORTYGUARD_API_KEY=your_fortyguard_api_key
FORTYGUARD_BASE_URL=https://api.fortyguard.com
```

```bash
php artisan migrate --seed    # Seeds 189 Phoenix flatland parks
npm run dev                   # Start Vite dev server
php artisan serve             # Start Laravel server
```

---

## 📚 Specialized Documentation

For detailed technical deep-dives into each pipeline step:

- [📍 Phoenix Parks Data](README_PARKS_DATA.md) — 189 flatland parks, GIS boundaries, filtering rationale
- [🗺️ Heat Analysis](README_HEAT_ANALYSIS.md) — 60m TCM thermal tiles, tile-park matching
- [🌡️ Environmental & Satellite Analysis](README_ENV_SAT_ANALYSIS.md) — Microclimate metrics, AI land-cover segmentation
- [🎯 Priority Scoring](README_PRIORITY_SCORING.md) — 5-factor weighted scoring model
- [🛠️ Cooling Solutions](README_COOLING_SOLUTIONS.md) — Rule-based recommendations, Phoenix-verified costs
- [💰 Budget Optimization](README_BUDGET_OPTIMIZATION.md) — Knapsack algorithm, $1.5M NPEP scenarios

---

## 📜 License & Attributions

- **License**: Proprietary / Educational Research Project
- **Municipal Data**: City of Phoenix Open GIS Data & Parks and Recreation Department
- **Thermal & Segmentation Models**: Powered by [FortyGuard](https://fortyguard.com/) Urban Thermal APIs
- **Mapping**: Google Maps JavaScript API
