# DUPLICATE TRIGGER BUG EXPLANATION

## The Problem

You have **TWO sets of triggers** running on the same action:

```
When you INSERT a TDC appointment:
┌─────────────────────────────────────────┐
│  INSERT INTO appointments               │
│  (tdc_session_id = 5, ...)             │
└─────────────────────────────────────────┘
                 │
                 ├──> Trigger 1: update_tdc_enrollment_after_insert
                 │    UPDATE tdc_sessions 
                 │    SET current_enrollments = current_enrollments + 1
                 │    WHERE id = 5;
                 │    Result: 10 → 11 ✓
                 │
                 └──> Trigger 2: update_tdc_session_after_insert (DUPLICATE!)
                      UPDATE tdc_sessions 
                      SET current_enrollments = current_enrollments + 1
                      WHERE id = 5;
                      Result: 11 → 12 ❌ WRONG!
```

## The Result

- **Expected:** Booking count increases by 1 (10 → 11)
- **Actual:** Booking count increases by 2 (10 → 12)

## The Fix

1. **Drop duplicate triggers:**
   - `update_tdc_session_after_insert`
   - `update_tdc_session_after_update`
   - `update_tdc_session_after_delete`

2. **Keep these triggers:**
   - `update_tdc_enrollment_after_insert` ✓
   - `update_tdc_enrollment_after_update` ✓
   - `update_tdc_enrollment_after_delete` ✓

3. **Recalculate counts** to fix any wrong numbers

## How to Fix

Run the SQL file: `fix_duplicate_triggers.sql` in HeidiSQL

## After the Fix

```
When you INSERT a TDC appointment:
┌─────────────────────────────────────────┐
│  INSERT INTO appointments               │
│  (tdc_session_id = 5, ...)             │
└─────────────────────────────────────────┘
                 │
                 └──> Trigger 1: update_tdc_enrollment_after_insert
                      UPDATE tdc_sessions 
                      SET current_enrollments = current_enrollments + 1
                      WHERE id = 5;
                      Result: 10 → 11 ✓ CORRECT!
```

Now each booking only increments by 1 as expected!
