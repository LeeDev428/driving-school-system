# Assessment & Quiz System Setup Guide

## 📋 Overview
This guide will help you set up the complete Assessment and Quiz system for your driving school.

---

## 🗄️ Database Setup (CRITICAL - Must be done first!)

### Step 1: Run Assessment Tables SQL
1. Open **HeidiSQL**
2. Connect to your database
3. Open the file: `create_assessment_tables.sql`
4. **Execute the entire script** (Click "Run" or press F9)
5. ✅ Verify these tables were created:
   - `assessments` (20 TRUE/FALSE questions)
   - `user_assessment_sessions` (tracks attempts)
   - `user_assessment_responses` (individual answers)

### Step 2: Run Quiz Tables SQL
1. Still in **HeidiSQL**
2. Open the file: `create_quiz_tables.sql`
3. **Execute the entire script**
4. ✅ Verify these tables were created:
   - `quizzes` (50 multiple choice questions)
   - `user_quiz_sessions` (tracks attempts)
   - `user_quiz_responses` (individual answers)

### Step 3: Verify Questions Were Inserted
```sql
-- Check Assessment questions (should return 20)
SELECT COUNT(*) FROM assessments;

-- Check Quiz questions (should return 50)
SELECT COUNT(*) FROM quizzes;
```

---

## 🎯 System Features

### Assessment System (`user/assessments.php`)
- **Format**: TRUE or FALSE
- **Total Questions**: 20
- **Categories**:
  - Traffic Signs (5 questions)
  - Road Markings (5 questions)
  - Traffic Rules (5 questions)
  - Emergency Response (5 questions)
- **Passing Score**: 70% (14 out of 20)
- **Time Limit**: None
- **Features**:
  - Progress bar showing completion
  - Instant feedback on pass/fail
  - View all previous attempts
  - See correct answers after submission

### Quiz System (`user/quizzes.php`)
- **Format**: Multiple Choice (A, B, C, D)
- **Total Questions**: 50
- **Categories**:
  - Traffic Lights (3 questions)
  - Road Signs (17 questions)
  - Road Markings (4 questions)
  - Emergency Response (6 questions)
  - Driving Rules (10 questions)
  - Protocol Plates (7 questions)
  - Safety Rules (3 questions)
- **Passing Score**: 70% (35 out of 50)
- **Time Limit**: None
- **Prerequisites**: 🔒 Must pass Assessment first!
- **Features**:
  - Locked until assessment passed
  - Progress bar and question navigation
  - Instant results with detailed breakdown
  - Attempt history tracking

---

## 🔄 User Flow

### Step-by-Step Journey:
1. **User Dashboard** → Click "E-Learning"
2. **E-Learning Portal** → Click "E-Learning (TDC)" button
3. **Quizzes & Assessments Page** → See 2 cards:
   - 📝 **Assessment Card** (Always available)
     - Shows "✓ Must complete first" badge
     - Blue button: "Take Assessment"
   
   - 📚 **Quiz Card** (Locked initially)
     - Shows "⚠ Requires Assessment completion" badge
     - Button says "Take Quiz"

4. **Take Assessment** → Answer 20 TRUE/FALSE questions
5. **Pass Assessment** (≥70%) → Quiz unlocks automatically!
6. **Take Quiz** → Answer 50 Multiple Choice questions
7. **Pass Quiz** (≥70%) → Congratulations! 🎉

---

## 🔐 Lock/Unlock Logic

### Assessment Status Check (in `quizzes.php`):
```php
// Checks if user has passed assessment
SELECT passed FROM user_assessment_sessions 
WHERE user_id = ? 
  AND status = 'completed' 
  AND passed = 1 
ORDER BY time_completed DESC 
LIMIT 1
```

### What happens if NOT passed:
- 🔒 Quiz page shows lock icon
- Message: "You must pass the Assessment first"
- Button: "Go to Assessment" (redirects to assessments.php)

### What happens if PASSED:
- ✅ Quiz page loads normally
- Shows all 50 questions
- User can submit answers

---

## 📊 Sample Questions

### Assessment Example (TRUE/FALSE):
**Question 1**: "A red traffic light means you must stop completely."
- **Answer**: TRUE
- **Category**: Traffic Signs

**Question 11**: "Enforcers must show valid ID before conducting traffic enforcement."
- **Answer**: TRUE
- **Category**: Traffic Rules

### Quiz Example (Multiple Choice):
**Question 1**: "What does a RED traffic light mean?"
- A) Slow down and prepare to stop
- B) Stop completely
- C) Proceed with caution
- D) Yield to pedestrians
- **Answer**: B
- **Category**: Traffic Lights

**Question 41**: "How many protocol plates does the President have?"
- A) 1
- B) 2
- C) 7
- D) 8
- **Answer**: A
- **Category**: Protocol Plates

---

## 🧪 Testing Checklist

### Before Testing:
- ✅ SQL scripts executed successfully
- ✅ 20 assessment questions in database
- ✅ 50 quiz questions in database
- ✅ Laragon/Apache is running

### Test Scenario 1: Fresh User (No Assessment)
1. Login as a student
2. Go to E-Learning → E-Learning (TDC)
3. **Expected**: See Assessment card (available) + Quiz card (NOT locked yet because e-learning doesn't check)
4. Click "Take Quiz"
5. **Expected**: Quiz page shows 🔒 lock icon and "Go to Assessment" button

### Test Scenario 2: Failed Assessment
1. Take Assessment
2. Answer randomly to get <70%
3. **Expected**: See "Failed" message with score
4. Go back and click "Take Quiz"
5. **Expected**: Still locked (must PASS assessment)

### Test Scenario 3: Passed Assessment
1. Take Assessment again
2. Answer correctly to get ≥70%
3. **Expected**: See "Passed!" message
4. Go back and click "Take Quiz"
5. **Expected**: Quiz loads with all 50 questions

### Test Scenario 4: Multiple Attempts
1. Check "Previous Attempts" table on both pages
2. **Expected**: See all attempts with timestamps and scores
3. Latest passing attempt should unlock quiz

---

## 🎨 Visual Design

### Assessment Page:
- **Color Theme**: Purple gradient (#667eea to #764ba2)
- **Layout**: Single column, card-based
- **Buttons**: Purple with white text, TRUE/FALSE options

### Quiz Page:
- **Color Theme**: Pink gradient (#f093fb to #f5576c)
- **Layout**: 2x2 grid for options (A, B, C, D)
- **Buttons**: Pink with white text, hover effects

### E-Learning Portal:
- **Assessment Card**: Purple accent, "Must complete first" badge
- **Quiz Card**: Pink accent, "Requires Assessment completion" badge
- **Hover Effect**: Cards lift up slightly on hover

---

## 🐛 Troubleshooting

### Problem: "Quiz is still locked after passing assessment"
**Solution**: 
- Check database: `SELECT * FROM user_assessment_sessions WHERE user_id = YOUR_ID AND passed = 1`
- Make sure `status = 'completed'` and `passed = 1`
- Clear browser cache and reload

### Problem: "Assessment/Quiz questions not showing"
**Solution**:
- Verify SQL scripts ran successfully
- Check: `SELECT COUNT(*) FROM assessments;` (should be 20)
- Check: `SELECT COUNT(*) FROM quizzes;` (should be 50)

### Problem: "Modules/Videos still showing in e-learning"
**Solution**:
- File `user/e-learning.php` was updated to remove these
- Hard refresh browser: Ctrl + Shift + R
- Check you're viewing the correct e-learning.php file

---

## 📁 File Reference

### Created Files:
1. `create_assessment_tables.sql` - Assessment database setup
2. `create_quiz_tables.sql` - Quiz database setup
3. `user/assessments.php` - Assessment interface
4. `user/quizzes.php` - Quiz interface (with lock logic)

### Modified Files:
1. `user/e-learning.php` - Removed Modules/Videos, added Assessment & Quiz cards

---

## ✅ Final Checklist

- [ ] Executed `create_assessment_tables.sql`
- [ ] Executed `create_quiz_tables.sql`
- [ ] Verified 20 assessment questions exist
- [ ] Verified 50 quiz questions exist
- [ ] Tested fresh user → Quiz locked
- [ ] Tested passed assessment → Quiz unlocked
- [ ] Verified attempt history tracking works
- [ ] Confirmed pass/fail thresholds (70%)

---

## 🎓 You're All Set!

Your driving school now has a complete Assessment and Quiz system with:
- ✅ 20 TRUE/FALSE Assessment questions
- ✅ 50 Multiple Choice Quiz questions
- ✅ Sequential unlock system (Assessment → Quiz)
- ✅ Attempt tracking and history
- ✅ Pass/Fail feedback
- ✅ Clean, modern interface

**Good luck with your students! 🚗💨**
