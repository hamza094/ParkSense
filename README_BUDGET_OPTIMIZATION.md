# Budget Optimization

> **Note**: Throughout this README, "intervention" refers to cooling solutions - practical strategies to make parks cooler and reduce temperature. The term "intervention" is used in the codebase and database for technical precision, but "cooling solutions" is used here for clearer understanding.

## 📍 What This Is
After we recommend cooling solutions for the top priority parks, our budget optimization system helps decide how to spend a limited budget to get the most cooling impact. It uses an algorithm to choose the best combination of recommendations that fits within the budget.

## 🎯 How Budget Optimization Works

### **The Process Flow**
1. **Generate Options**: Convert cooling solution recommendations into budget options with costs and benefits
2. **Calculate Benefits**: Model the benefit of each option based on park priority score and intervention scale
3. **Optimize Allocation**: Use knapsack algorithm to maximize benefit within budget constraints
4. **Create Investment Plan**: Store the optimized allocation with full transparency

### **Why Budget Optimization Matters**
- **Limited Money**: Cities have fixed budgets for cooling projects
- **Maximum Impact**: Make sure every dollar does the most good
- **Clear Decisions**: Use a proven algorithm instead of guessing
- **Test Different Scenarios**: See what happens with different budget amounts

## 💰 **Budget Scenarios and Phoenix References**

### **Why We Use Phoenix Budget References**
ParkSense uses real Phoenix budget numbers to make the demo more realistic. These aren't budgets that Phoenix gives to ParkSense - they're just official Phoenix amounts that help show how the system would work with real municipal funding.

### **Phoenix Budget Reference We Use**

**Phoenix Neighborhood Parks Enhancement Program (NPEP)**
- **Amount**: $1.5 million
- **What it's for**: Minor improvements to neighborhood parks
- **Source**: Phoenix voter-approved bond program
- **Evidence**: 🟢 Phoenix verified
- **Link**: [City of Phoenix NPEP](https://www.phoenix.gov/administration/departments/parks/about-us/improvement-projects/neighborhood-parks-enhancement-program.html)

### **Other Phoenix Budgets (For Context Only)**

**Phoenix Parks & Recreation Department Budget**
- **Amount**: ~$160 million per year
- **Why we don't use it**: This is the entire department budget (staff, operations, maintenance) - not just for cooling projects
- **Evidence**: 🟢 Phoenix verified

**Phoenix Shade Phoenix Plan**
- **Amount**: $60+ million over 5 years
- **Why we don't use it**: This is a 5-year citywide investment plan, not a single project budget
- **Evidence**: 🟢 Phoenix verified

### **Budget Scenarios Available**

| Scenario | Amount | Description | Type |
|----------|--------|-------------|------|
| Small Project | $250,000 | Good for testing with smaller budgets | 🟡 Planning scenario |
| Medium Investment | $500,000 | Moderate budget for several park improvements | 🟡 Planning scenario |
| Phoenix NPEP Reference | $1,500,000 | Based on Phoenix's $1.5M neighborhood park program | 🟢 Phoenix verified |

### **Default Budget**
We use **$1.5 million** as the default because it matches Phoenix's actual neighborhood park improvement program. This makes the demo more realistic while still letting you test with different amounts.

### **Important Note**
**The $1.5M budget is based on Phoenix's real program, but Phoenix doesn't give this money to ParkSense. We use it as a realistic example to show how the system works.**

### **Budget Flexibility**
You can:
- Choose from preset scenarios (including the Phoenix reference)
- Type any custom amount you want to test
- Compare results across different budget levels
- See how budget size affects which parks get selected

**Evidence**: 🟢 Phoenix verified for the $1.5M amount, 🟡 Planning assumptions for the smaller scenarios

## 💰 **How Benefits Are Calculated**

### **Modeled Benefit Formula**
```
Modeled Benefit = Park Priority Score × Scale Factor
```

### **Scale Factors by Intervention Size**
These factors represent the relative impact of different intervention scales:

- **Small Package**: 0.25 (25% of full potential benefit)
- **Medium Package**: 0.50 (50% of full potential benefit)
- **Large Package**: 0.90 (90% of full potential benefit)
- **Default (if no scenario)**: 0.50

### **Example Calculations**

**High-Priority Park (Score: 80) with Large Tree Package:**
```
Modeled Benefit = 80 × 0.90 = 72.0
```

**Medium-Priority Park (Score: 60) with Medium Tree Package:**
```
Modeled Benefit = 60 × 0.50 = 30.0
```

**Low-Priority Park (Score: 40) with Small Tree Package:**
```
Modeled Benefit = 40 × 0.25 = 10.0
```

### **How Scale Factors Are Determined**

**Scale factors come from the `scenario` field set during intervention recommendation:**

**For Tree Planting:**
- `scenario = 'small'` → scale factor = 0.25 (intervention opportunity ≤ 50)
- `scenario = 'medium'` → scale factor = 0.50 (intervention opportunity 50-65)
- `scenario = 'large'` → scale factor = 0.90 (intervention opportunity > 65)

**For Ramada and Cool Pavement:**
- `scenario = null` → scale factor = 0.50 (default, no package sizes)

**Stage Connection:**
1. **Intervention Stage**: System determines scenario based on intervention opportunity score
2. **Budget Stage**: System reads scenario field and applies corresponding scale factor
3. **Result**: Same benefit calculation approach for all intervention types

**Evidence Level**: 🟡 ParkSense planning assumption - scale factors for benefit modeling

### **Why These Scale Factors**

**Tree Package Scale Factors:**
- **Small (0.25)**: Limited impact due to small scale (25 trees)
- **Medium (0.50)**: Moderate impact at medium scale (50 trees)
- **Large (0.90)**: High impact at large scale (100 trees)
- **Not 1.0**: Even large packages don't achieve 100% of theoretical benefit due to practical constraints

**Default Scale Factor (0.50):**
- **Applied to**: Ramada and cool pavement interventions
- **Rationale**: These interventions have moderate impact but no package size variants
- **Consistency**: Provides baseline benefit calculation for non-tree interventions

**Evidence Level**: 🟡 ParkSense planning assumption - scale factors for benefit modeling

## 🧮 **How the Algorithm Works**

### **The Knapsack Algorithm**
We use a **knapsack algorithm** - this is a proven method for choosing the best items within a budget limit.

### **What It Does**
**Goal**: Get the most cooling benefit for the money
**Rules**:
- Can't spend more than the budget allows
- Each park can receive multiple different cooling solutions if budget permits
- Every valid recommendation is treated as an independent candidate

### **Algorithm Steps**

**Step 1: Flatten All Options**
```
Park A Tree, Park A Pavement, Park B Tree, Park C Tree, Park C Pavement
```

**Step 2: Initialize States**
```
State 0: Cost = $0, Benefit = 0, Selected = []
```

**Step 3: Process Each Option Independently**
For each option (e.g., Park A Tree), consider adding it to all existing valid states:

```
If adding option to state cost > budget: Skip
If adding option to state cost ≤ budget: Create new state
```

**Step 4: Keep Best Option per Cost Level**
```
For each cost level, keep the combination of options with maximum benefit
```

**Step 5: Select Final Solution**
```
Choose the state with maximum benefit within budget
```

### **Example Optimization**

**Available Options:**
- Park A: Tree Large ($105K, benefit 72)
- Park B: Tree Medium ($52K, benefit 30)
- Park C: Cool Pavement ($39K, benefit 48)

**Budget: $150,000**

**Possible Combinations:**
1. Park A + Park B: $157K (exceeds budget) ❌
2. Park A + Park C: $144K (within budget) → Benefit: 72 + 48 = 120 ✅
3. Park B + Park C: $91K (within budget) → Benefit: 30 + 48 = 78 ✅

**Optimal Solution**: Park A + Park C ($144K, benefit 120)

## 📊 **Budget Optimization Metrics**

### **Key Output Metrics**

**Total Cost**: Sum of selected intervention costs
- Must be ≤ available budget
- Represents actual investment required

**Remaining Budget**: Budget - Total Cost
- Shows budget utilization
- Identifies if budget is underutilized

**Total Modeled Benefit**: Sum of individual intervention benefits
- Represents total heat mitigation impact
- Used for comparison across budget scenarios

**Best Possible Benefit**: Sum of all valid recommended interventions
- Theoretical maximum if budget were unlimited to fund every single recommendation
- Used to calculate coverage percentage

**Modeled Priority Coverage**: (Total Benefit / Best Possible Benefit) × 100
- Percentage of maximum possible benefit achieved
- Shows how well the budget covers the top parks

### **Example Output**

**Budget: $200,000**

**Optimized Plan:**
- Park A: Tree Large ($105K, benefit 72)
- Park B: Tree Medium ($52K, benefit 30)
- Park C: Cool Pavement ($39K, benefit 48)

**Metrics:**
- Total Cost: $196,000
- Remaining Budget: $4,000
- Total Modeled Benefit: 150
- Best Possible Benefit: 160
- Modeled Priority Coverage: 93.75%

## 🎯 **Budget Optimization Constraints**

### **Hard Constraints**
1. **Never Exceed Budget**: Total cost must be ≤ available budget
2. **No Duplicates**: The same exact intervention candidate cannot be selected twice
3. **Integer Selection**: Either select an intervention or not (no partial selections)

### **Flexible Selection**
- **Multiple Interventions**: A single park can receive multiple different cooling solutions (e.g., Tree Planting AND Cool Pavement) if both are recommended and the budget allows.
- **Independent Evaluation**: Every recommendation competes independently based on its modeled benefit and cost.

## 💡 **Why This Matters**

**It helps you:**
✅ **Spend Money Wisely**: Use data to get the most cooling impact
✅ **Test Different Budgets**: See what happens with more or less money
✅ **Make Clear Decisions**: Understand exactly why certain parks were chosen
✅ **Show the Value**: Demonstrate the benefit per dollar spent
✅ **Prioritize Effectively**: Focus on parks that need it most

## 🔧 **Technical Implementation**

### **Optimization Pipeline**
1. **Generate Options**: Convert recommendations to budget options
2. **Calculate Benefits**: Apply scale factors to priority scores
3. **Run Algorithm**: Execute knapsack optimization
4. **Calculate Metrics**: Compute coverage and efficiency metrics
5. **Store Results**: Save investment plan with full transparency

### **Configuration**
All settings are in `config/park_heat.php`:
- **Scale Factors**: 0.25, 0.50, 0.90 for small/medium/large projects
- **No Duplicates**: Cannot select the exact same candidate twice
- **Budget Limit**: Never spend more than the available budget
- **Budget Scenarios**: Phoenix reference amounts ($250K, $500K, $1.5M) for realistic testing

## 🎯 **Important Notes**

**This Is a Planning Model**
Real-world results will vary based on:
- Specific park conditions
- How well projects are implemented
- Ongoing maintenance
- Weather and climate factors

**Modeled vs. Real Benefits**
- **Modeled Benefit**: Our calculated estimate (priority score × scale factor)
- **Real Benefit**: Actual temperature reduction and comfort improvement
- **The Connection**: Modeled benefit helps us estimate expected impact

**Algorithm Limitations**
- **Whole Projects Only**: Can't do partial projects
- **Simple Scaling**: Assumes benefits scale steadily with cost
- **Current Priorities**: Uses current park scores, not future changes

## 🎯 **Bottom Line**

After we recommend cooling solutions for priority parks, our system helps decide how to spend a limited budget to get the most cooling impact. It uses a smart 0/1 knapsack algorithm to pick the best combination of projects that fits within the budget, allowing multiple different solutions per park if funding permits.

---

*Powered by ParkSense Budget Optimization Model v1 - Smart budget allocation for maximum cooling impact. Uses 0/1 knapsack optimization with priority-based benefit modeling and Phoenix budget scenarios.*