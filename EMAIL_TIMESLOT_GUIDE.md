# 📧 EMAIL NOTIFICATION & TIME SLOT SYSTEM - COMPLETE GUIDE

## 🎯 Overview

This system implements:
1. **Email Reminder System**: Sends automated emails 1 day before appointments
2. **PDC Time Slot Management**: Shows available time slots with booking limits
3. **TDC Calendar Display**: Visual calendar showing selected Friday/Saturday dates

---

## 📋 Requirements from Images

### Image 1 - PDC Booking:
✅ Student schedules PDC appointment  
✅ System shows available time slots in dropdown for selected date  
✅ Example: "oct 15 2-6pm" - if unavailable, student can't select it  
✅ System sends email notification **1 day before** appointment

### Image 2 - TDC Booking:
✅ Student selects TDC session (Friday/Saturday only)  
✅ After selecting: preferred instructor, preferred vehicle  
✅ System displays selected date in calendar view  
✅ System sends email notification **1 day before** appointment

---

## 🚀 INSTALLATION STEPS

### Step 1: Run Database Migration

Open phpMyAdmin and run this SQL file:
```
add_pdc_time_slots.sql
```

**This will:**
- Add `reminder_sent` and `reminder_sent_at` columns to `appointments` table
- Create `pdc_time_slots` table for managing PDC time availability
- Create triggers to auto-update booking counts
- Insert 40 sample time slots for October-November 2025

**Verify Installation:**
```sql
-- Check if columns were added
SHOW COLUMNS FROM appointments LIKE 'reminder%';

-- Check if table was created
DESCRIBE pdc_time_slots;

-- View sample time slots
SELECT * FROM pdc_time_slots ORDER BY slot_date, slot_time_start LIMIT 10;
```

---

## 📧 EMAIL NOTIFICATION SETUP

### File Created: `send_appointment_reminder.php`

**SMTP Credentials Used:**
- Host: smtp.gmail.com
- Port: 587
- Username: deduyoroy02@gmail.com
- Password: ntue ydcf abel nqnm (App Password)
- Encryption: STARTTLS

### Windows Task Scheduler Setup (Recommended)

1. **Open Task Scheduler**
   - Press `Win + R`, type `taskschd.msc`, press Enter

2. **Create Basic Task**
   - Click "Create Basic Task"
   - Name: "Driving School Appointment Reminders"
   - Description: "Send email reminders 1 day before appointments"

3. **Trigger: Daily**
   - Start date: Today
   - Recur every: 1 day
   - Start time: **8:00 AM**

4. **Action: Start a Program**
   - Program/script:
     ```
     C:\laragon\bin\php\php-8.4.3-Win32-vs16-x64\php.exe
     ```
   - Add arguments:
     ```
     "D:\laragon\www\driving-school-system\send_appointment_reminder.php"
     ```

5. **Finish**
   - Check "Open Properties dialog"
   - In Properties:
     - General tab: Select "Run whether user is logged on or not"
     - Conditions tab: Uncheck "Start the task only if the computer is on AC power"

### Manual Testing

Run this command in PowerShell:
```powershell
cd D:\laragon\www\driving-school-system
php send_appointment_reminder.php
```

**Expected Output:**
```
[2025-10-16 08:00:00] ========================================
[2025-10-16 08:00:00] Starting appointment reminder check...
[2025-10-16 08:00:00] Checking for appointments on: 2025-10-17
[2025-10-16 08:00:00] Found X appointment(s) requiring reminders
[2025-10-16 08:00:00] Processing: John Doe (john@example.com) - TDC on 2025-10-17
[2025-10-16 08:00:00] ✓ Email sent successfully to john@example.com
[2025-10-16 08:00:00] ========================================
[2025-10-16 08:00:00] Reminder check completed!
[2025-10-16 08:00:00] Total: 1 | Success: 1 | Failed: 0
[2025-10-16 08:00:00] ========================================
```

### Check Logs

View email logs at:
```
D:\laragon\www\driving-school-system\logs\appointment_reminders.log
```

---

## 📅 PDC TIME SLOT SYSTEM

### How It Works

1. **Student selects PDC course**
2. **Student picks a date** using date picker
3. **System loads available time slots** for that date via AJAX
4. **Time slots displayed as cards** showing:
   - Time range (e.g., "8:00 AM - 12:00 PM")
   - Assigned instructor
   - Available slots (e.g., "2 slots left")
   - Booking status (e.g., "1/3 booked")
5. **Student clicks a time slot** to select it
6. **System validates** slot availability before booking
7. **Booking count auto-updates** via database triggers

### Database Structure

**Table: `pdc_time_slots`**
```sql
- id                INT PRIMARY KEY
- slot_date         DATE (e.g., '2025-10-20')
- slot_time_start   TIME (e.g., '08:00:00')
- slot_time_end     TIME (e.g., '12:00:00')
- slot_label        VARCHAR(50) (e.g., '8:00 AM - 12:00 PM')
- instructor_id     INT (optional assigned instructor)
- max_bookings      INT (default: 1, can be 3 for multiple students)
- current_bookings  INT (auto-updated by triggers)
- is_available      TINYINT(1) (1 = available, 0 = unavailable)
```

### Sample Time Slots

```
Date: 2025-10-20
├── 8:00 AM - 12:00 PM (3 slots)
├── 2:00 PM - 6:00 PM (3 slots)

Date: 2025-10-21
├── 8:00 AM - 12:00 PM (3 slots)
├── 2:00 PM - 6:00 PM (3 slots)
```

### Add More Time Slots

```sql
INSERT INTO pdc_time_slots 
(slot_date, slot_time_start, slot_time_end, slot_label, max_bookings, is_available) 
VALUES
('2025-11-20', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-20', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1);
```

---

## 📆 TDC CALENDAR DISPLAY

### How It Works

1. **Student selects TDC course**
2. **Dropdown loads** available Friday/Saturday sessions
3. **Student selects a session**
4. **Calendar display appears** showing:
   - 📅 Large calendar icon
   - Full date (e.g., "Friday, October 18, 2025")
   - ⏰ Time range (e.g., "9:00 AM - 5:00 PM")
   - 👥 Available slots (e.g., "5 slots remaining out of 10")

### Visual Design

```
┌─────────────────────────────────────────────┐
│      📅 Your TDC Session Date              │
│                                             │
│     Friday, October 18, 2025                │
│     ⏰ 9:00 AM - 5:00 PM                    │
│     👥 5 slots remaining out of 10          │
└─────────────────────────────────────────────┘
```

---

## 🧪 TESTING GUIDE

### Test 1: PDC Time Slot Booking

1. **Login** as student
2. **Navigate to** Appointments page
3. **Click** "Schedule New Appointment"
4. **Select** "PDC - Practical Driving Course"
5. **Fill PDC form:**
   - Vehicle Type: Motorcycle (₱2,000) or Car (₱4,500)
   - Transmission: Automatic or Manual
   - Duration: 2 Days or 4 Days
   - **Start Date:** Select October 20, 2025
6. **Observe:** Time slots load automatically
7. **Verify display shows:**
   - "8:00 AM - 12:00 PM" card
   - "2:00 PM - 6:00 PM" card
   - Each showing "X slots left" and "Y/Z booked"
8. **Click** a time slot (should highlight green)
9. **Fill payment info**
10. **Submit form**
11. **Check database:**
```sql
SELECT a.*, pts.slot_label 
FROM appointments a
LEFT JOIN pdc_time_slots pts ON a.pdc_time_slot_id = pts.id
WHERE a.student_id = YOUR_STUDENT_ID
ORDER BY a.created_at DESC LIMIT 1;
```
12. **Verify:** `pdc_time_slot_id` is populated and `current_bookings` increased in `pdc_time_slots`

### Test 2: TDC Calendar Display

1. **Login** as student
2. **Navigate to** Appointments page
3. **Click** "Schedule New Appointment"
4. **Select** "TDC - Theoretical Driving Course"
5. **Observe:** Dropdown loads Friday/Saturday sessions
6. **Select** any session (e.g., "Friday, Oct 18, 2025")
7. **Verify calendar displays:**
   - Large calendar icon
   - Full date with day name
   - Time range
   - Available slots count
8. **Fill remaining fields** (instructor, vehicle - optional)
9. **Fill payment info**
10. **Submit form**
11. **Check success message**

### Test 3: Email Reminder System

**Scenario A: Manual Test**
```powershell
# Create test appointment for tomorrow
INSERT INTO appointments 
(student_id, course_selection, appointment_date, start_time, end_time, course_price, status, reminder_sent)
VALUES 
(1, 'TDC', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '17:00:00', 899.00, 'confirmed', 0);

# Run reminder script
cd D:\laragon\www\driving-school-system
php send_appointment_reminder.php
```

**Expected Result:**
- Email sent to student's email address
- `reminder_sent` = 1 in database
- `reminder_sent_at` = current timestamp
- Log file updated

**Scenario B: Check Email Content**

Open email and verify:
- ✅ Subject: "⏰ Reminder: Your Appointment is Tomorrow!"
- ✅ From: Success Driving School (deduyoroy02@gmail.com)
- ✅ Body contains:
  - Student name
  - Course type (TDC or PDC)
  - Formatted date (e.g., "Friday, October 18, 2025")
  - Time
  - Reminders (arrive 15 min early, bring ID)
  - Link to view appointments

---

## 🐛 TROUBLESHOOTING

### Issue: Time slots not loading

**Check:**
1. Database table exists:
   ```sql
   SHOW TABLES LIKE 'pdc_time_slots';
   ```
2. Sample data inserted:
   ```sql
   SELECT COUNT(*) FROM pdc_time_slots;
   ```
3. Browser console for errors (F12)
4. AJAX endpoint working:
   ```
   POST to appointments.php with action=get_pdc_time_slots
   ```

**Fix:**
```sql
-- Re-run migration
SOURCE add_pdc_time_slots.sql;
```

### Issue: Email not sending

**Check:**
1. PHPMailer files exist:
   ```
   phpmailer/PHPMailer.php
   phpmailer/SMTP.php
   phpmailer/Exception.php
   ```
2. Gmail App Password correct
3. Gmail account "Less secure app access" or "2-Step Verification" with App Password
4. Test SMTP connection:
```php
php -r "require 'send_appointment_reminder.php';"
```

**Common Errors:**
- "SMTP connect() failed" → Check firewall blocking port 587
- "Authentication failed" → Verify App Password (remove spaces)
- "Could not instantiate mail function" → PHPMailer not loaded

### Issue: Calendar not displaying (TDC)

**Check:**
1. JavaScript console (F12) for errors
2. `tdcSessionsData` variable populated:
   ```javascript
   console.log(tdcSessionsData);
   ```
3. Session selected:
   ```javascript
   console.log(document.getElementById('tdc_session').value);
   ```

**Fix:**
- Clear browser cache
- Check `showTDCSessionCalendar()` function exists
- Verify `onchange="showTDCSessionCalendar()"` on select element

---

## 📊 DATABASE QUERIES FOR MONITORING

### Check upcoming appointments needing reminders
```sql
SELECT 
    u.full_name,
    u.email,
    a.course_selection,
    a.appointment_date,
    a.start_time,
    a.reminder_sent
FROM appointments a
JOIN users u ON a.student_id = u.id
WHERE DATE(a.appointment_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
AND a.status != 'cancelled'
ORDER BY a.appointment_date, a.start_time;
```

### Check PDC time slot availability
```sql
SELECT 
    slot_date,
    slot_label,
    current_bookings,
    max_bookings,
    (max_bookings - current_bookings) as available_slots
FROM pdc_time_slots
WHERE slot_date >= CURDATE()
AND is_available = 1
ORDER BY slot_date, slot_time_start;
```

### View reminder email history
```sql
SELECT 
    u.full_name,
    u.email,
    a.course_selection,
    a.appointment_date,
    a.reminder_sent_at
FROM appointments a
JOIN users u ON a.student_id = u.id
WHERE a.reminder_sent = 1
ORDER BY a.reminder_sent_at DESC
LIMIT 20;
```

---

## ✅ COMPLETION CHECKLIST

- [ ] Database migration `add_pdc_time_slots.sql` executed successfully
- [ ] `pdc_time_slots` table exists with sample data
- [ ] `reminder_sent` columns added to `appointments` table
- [ ] Email reminder script `send_appointment_reminder.php` created
- [ ] Windows Task Scheduler configured (or cron job on Linux)
- [ ] Manual email test successful
- [ ] PDC time slot selection working on appointments page
- [ ] Time slots load dynamically when date selected
- [ ] Slot availability displays correctly (X/Y booked)
- [ ] TDC calendar display shows after session selection
- [ ] Calendar shows full date, time, and available slots
- [ ] Email logs created in `logs/appointment_reminders.log`
- [ ] Test appointment created for tomorrow
- [ ] Reminder email received successfully

---

## 🎉 SUCCESS CRITERIA

**Your system is working correctly when:**

1. ✅ Students book PDC → See time slots → Unavailable slots show "FULL"
2. ✅ Students book TDC → Select session → See calendar with Friday/Saturday date
3. ✅ Appointments tomorrow → Email sent at 8 AM → Students receive reminder
4. ✅ Email contains correct date, time, course info
5. ✅ Double-booking prevented (slot shows FULL when max reached)
6. ✅ Log file tracks all email sends

---

## 📞 SUPPORT

**If you encounter issues:**

1. Check browser console (F12) for JavaScript errors
2. Check PHP error log: `D:\laragon\www\driving-school-system\logs\`
3. Verify database structure: `SHOW TABLES; DESCRIBE pdc_time_slots;`
4. Test SMTP: Run `send_appointment_reminder.php` manually
5. Check appointment data: `SELECT * FROM appointments WHERE student_id = X;`

---

**System Version:** 2.0
**Last Updated:** October 16, 2025
**Author:** GitHub Copilot
**SMTP Account:** deduyoroy02@gmail.com
