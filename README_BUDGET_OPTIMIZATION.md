# Budget Optimization

> **Note**: Throughout this README, "intervention" refers to cooling solutions - practical strategies to make parks cooler and reduce temperature. The term "intervention" is used in the codebase and database for technical precision, but "cooling solutions" is used here for clearer understanding.

## 📍 What This Is
After cooling solution recommendations are generated for the top 5 priority parks, our budget optimization system helps decision-makers allocate limited budgets across these recommendations to maximize impact. This algorithmic approach ensures every dollar spent achieves the greatest possible heat mitigation benefit.

## 🎯 How Budget Optimization Works

### **The Process Flow**
1. **Generate Options**: Convert cooling solution recommendations into budget options with costs and benefits
2. **Calculate Benefits**: Model the benefit of each option based on park priority score and intervention scale
3. **Optimize Allocation**: Use knapsack algorithm to maximize benefit within budget constraints
4. **Create Investment Plan**: Store the optimized allocation with full transparency

### **Why Budget Optimization Matters**
- **Limited Resources**: Cities have finite budgets for heat mitigation
- **Maximum Impact**: Ensure every dollar achieves the greatest cooling benefit
- **Transparent Decisions**: Clear algorithmic approach rather than arbitrary choices
- **Scenario Planning**: Test different budget levels and see trade-offs

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

**Evidence Level**: 🟡 ParkHeat planning assumption - scale factors for benefit modeling

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

**Evidence Level**: 🟡 ParkHeat planning assumption - scale factors for benefit modeling

## 🧮 **How the Algorithm Works**

### **Knapsack Algorithm Approach**
Our system uses a **multiple-choice knapsack algorithm** to solve the budget optimization problem.

### **The Problem It Solves**
**Objective**: Maximize total benefit
**Constraints**:
- Total cost ≤ available budget
- Maximum 1 intervention per park
- Each park has multiple intervention options (tree packages, cool pavement, ramada)

### **Algorithm Steps**

**Step 1: Group Options by Park**
```
Park A: [Tree Small, Tree Medium, Tree Large, Cool Pavement]
Park B: [Tree Small, Tree Medium, Tree Large, Ramada]
Park C: [Tree Small, Tree Medium, Cool Pavement]
```

**Step 2: Initialize States**
```
State 0: Cost = $0, Benefit = 0, Selected = []
```

**Step 3: Process Each Park's Options**
For each park, consider each option and update states:

```
For Park A:
- Tree Small: Cost $26,250, Benefit 20 → New State 1
- Tree Medium: Cost $52,500, Benefit 40 → New State 2
- Tree Large: Cost $105,000, Benefit 72 → New State 3
- Cool Pavement: Cost $39,204, Benefit 48 → New State 4
```

**Step 4: Apply Budget Constraint**
```
If new_cost > budget: Skip this option
If new_cost ≤ budget: Update state
```

**Step 5: Keep Best Option per Cost Level**
```
For each cost level, keep the option with maximum benefit
```

**Step 6: Select Final Solution**
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

**Best Possible Benefit**: Sum of best option per park (unconstrained)
- Theoretical maximum if budget were unlimited
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
2. **One Intervention Per Park**: Maximum 1 cooling solution per park
3. **Integer Selection**: Either select an intervention or not (no partial selections)

### **Why One Intervention Per Park**
- **Practical Implementation**: Parks can't handle multiple simultaneous projects
- **Focused Impact**: Concentrate resources on the highest-impact solution per park
- **Manageable Scale**: Keeps implementation realistic

### **Alternative Approaches**
- **Relaxed Constraint**: Allow multiple interventions per park (if budget permits)
- **Minimum Impact**: Require minimum benefit threshold for any selection
- **Geographic Distribution**: Ensure geographic spread across city

## 💡 **Business Value**

**Enables:**
✅ **Data-Driven Budgeting**: Algorithmic approach to maximize impact
✅ **Scenario Planning**: Test different budget levels and outcomes
✅ **Transparent Decisions**: Clear optimization logic and trade-offs
✅ **Budget Justification**: Quantified benefit per dollar spent
✅ **Prioritization Evidence**: Shows which parks provide most value

## 🔧 **Technical Implementation**

### **Optimization Pipeline**
1. **Generate Options**: Convert recommendations to budget options
2. **Calculate Benefits**: Apply scale factors to priority scores
3. **Run Algorithm**: Execute knapsack optimization
4. **Calculate Metrics**: Compute coverage and efficiency metrics
5. **Store Results**: Save investment plan with full transparency

### **Configuration**
All optimization parameters are configured in `config/park_heat.php`:
- **Scale Factors**: 0.25, 0.50, 0.90 for small/medium/large packages
- **One Per Park Constraint**: Hard constraint in algorithm
- **Budget Constraint**: Never exceed available budget

## 🎯 **Important Notes**

**Planning Model, Not Exact Benefit:**
This is a planning model for budget optimization. Actual cooling benefits will vary based on:
- Site-specific conditions
- Implementation quality
- Maintenance effectiveness
- Climate variability
- Other environmental factors

**Modeled vs. Actual Benefits:**
- **Modeled Benefit**: Priority score × scale factor (planning assumption)
- **Actual Benefit**: Real temperature reduction, user comfort improvement
- **Relationship**: Modeled benefit is a proxy for expected impact

**Algorithm Limitations:**
- **Integer Constraint**: Can't select partial interventions
- **Linear Benefits**: Assumes benefits scale linearly with cost
- **Static Priority**: Uses snapshot priority scores, not dynamic changes

## 🎯 **Bottom Line**

**Simple Version**: After we know which cooling solutions to recommend for the top 5 parks, our budget optimization system helps decision-makers figure out how to spend a limited budget to get the most cooling impact. It uses an algorithm to choose the best combination of recommendations that fits within the budget while maximizing benefit.

**Technical Version**: Our budget optimization system uses a multiple-choice knapsack algorithm to maximize modeled benefit within budget constraints. The system converts cooling solution recommendations into budget options with costs (Phoenix-verified where possible) and modeled benefits (priority score × scale factor). The algorithm respects hard constraints (never exceed budget, one intervention per park) and generates optimized investment plans with transparency metrics including total cost, remaining budget, total modeled benefit, and priority coverage percentage.

---

*Powered by ParkHeat Budget Optimization Model v1 - Algorithmic budget allocation for maximum heat mitigation impact. Uses knapsack optimization with priority-based benefit modeling and Phoenix-referenced cost data.*