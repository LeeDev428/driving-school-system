# FIX INSTRUCTIONS - Vehicle Type Database Issue

## 🔧 ISSUES FIXED:

### 1. ✅ ReferenceError: vehicleType is not defined
**Problem:** Variable was declared inside inner try-catch block
**Fixed:** Moved `vehicleType` declaration to outer scope in gameStats.js line 267

### 2. 🚗 Vehicle Type Always Showing 'car'
**Problem:** Need to verify SimulationConfig.vehicleType is set correctly
**Solution:** Added debug logging to trace vehicle type through entire flow

---

## 📋 STEPS TO COMPLETE FIX:

### Step 1: Check if vehicle_type column exists
1. Open browser: http://localhost/driving-school-system/check_vehicle_type_column.php
2. Look for "✅ vehicle_type column EXISTS!" message

**If column is MISSING:**
- Open HeidiSQL
- Select `driving_school_system` database
- Go to Query tab
- Run this SQL:

```sql
ALTER TABLE `simulation_results` 
ADD COLUMN `vehicle_type` VARCHAR(20) NOT NULL DEFAULT 'car' 
AFTER `simulation_type`;
```

### Step 2: Clear existing test data (RECOMMENDED)
Since you have test data with wrong vehicle types, clear them:

```sql
-- Delete test records
DELETE FROM simulation_results WHERE id IN (1, 8);

-- Or reset the table completely
TRUNCATE TABLE simulation_results;
```

### Step 3: Test the simulation
1. Open: http://localhost/driving-school-system/user/simulation.php
2. **SELECT MOTORCYCLE** (to test it saves correctly)
3. Open browser console (F12)
4. You should see these logs:
   ```
   ✅ SimulationConfig.vehicleType set to: motorcycle
   📋 SimulationConfig.vehicleType: motorcycle
   🚗 Vehicle type from SimulationConfig: motorcycle
   ```
5. Complete all 5 scenarios (wait 3 seconds between each)
6. Click "Proceed" on completion screen
7. Check console for:
   ```
   🚗 Saving simulation results with vehicle type: motorcycle
   ✅ Simulation results saved with vehicle type!
   ```

### Step 4: Verify database
1. Open HeidiSQL
2. Run query:
```sql
SELECT id, user_id, simulation_type, vehicle_type, correct_answers, wrong_answers, created_at 
FROM simulation_results 
ORDER BY id DESC LIMIT 5;
```
3. **Verify:** The new record should show `vehicle_type = 'motorcycle'`

---

## 🔍 DEBUGGING CHECKLIST:

If vehicle_type is still wrong:

1. **Check console logs** - Should show:
   - Vehicle selection: `✅ SimulationConfig.vehicleType set to: motorcycle`
   - Before save: `🚗 Vehicle type from SimulationConfig: motorcycle`

2. **Check if vehicle_type column exists:**
   ```sql
   DESCRIBE simulation_results;
   ```
   Should show `vehicle_type` column

3. **Check save_simulation.php** is receiving correct data:
   - Look for errors in PHP error log
   - Check if $vehicle_type variable is set correctly

4. **Verify session data:**
   - Make sure user is logged in (user_id exists)
   - Check $_SESSION variables

---

## 📝 FILES MODIFIED:

✅ `assets/js/modules/gameStats.js` (Line 267-273)
   - Moved vehicleType declaration to outer scope
   - Added debug logging for SimulationConfig

✅ `check_vehicle_type_column.php` (NEW FILE)
   - Utility to verify database structure

✅ `save_simulation.php` (Already has vehicle_type support)
   - Line 50: Gets vehicle_type from JSON input
   - Line 67: Includes vehicle_type in INSERT query

---

## 🎯 EXPECTED BEHAVIOR:

### When you select MOTORCYCLE:
1. Vehicle renders as blue motorcycle with rider
2. SimulationConfig.vehicleType = 'motorcycle'
3. All 5 scenarios completed
4. Database saves with vehicle_type = 'motorcycle'

### When you select CAR:
1. Vehicle renders as gray/silver car
2. SimulationConfig.vehicleType = 'car'
3. All 5 scenarios completed
4. Database saves with vehicle_type = 'car'

---

## ⚡ QUICK TEST:

```bash
# 1. Check column exists
Open: http://localhost/driving-school-system/check_vehicle_type_column.php

# 2. Clear test data (in HeidiSQL)
DELETE FROM simulation_results WHERE user_id = 3;

# 3. Test simulation
- Select MOTORCYCLE
- Complete 5 scenarios
- Check database: Should show 'motorcycle'

# 4. Test again with CAR
- Refresh page
- Select CAR
- Complete 5 scenarios
- Check database: Should show 'car'
```

---

## 🚨 COMMON ERRORS:

**Error:** "Column 'vehicle_type' doesn't exist"
**Fix:** Run ALTER TABLE command in Step 1

**Error:** "vehicleType is not defined"
**Fix:** Already fixed in gameStats.js - just refresh browser

**Error:** "Always saves as 'car' even when motorcycle selected"
**Fix:** Check console logs - SimulationConfig.vehicleType should match selection

---

## ✅ SUCCESS CRITERIA:

- ✅ No JavaScript errors in console
- ✅ Vehicle type logs show correct vehicle ('car' or 'motorcycle')
- ✅ Database `simulation_results` has `vehicle_type` column
- ✅ New records save with correct vehicle_type value
- ✅ Scenarios appear every 3 seconds
- ✅ All 5 scenarios complete successfully

