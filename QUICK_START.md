# 🚀 QUICK START GUIDE - Email & Time Slot System# 🎯 QUICK START GUIDE - Forgot Password System



## ⚡ 3-Step Setup## ⚡ 3-Step Installation



### STEP 1: Database Setup (1 minute)### Step 1: Install Database Table

```bashOpen **HeidiSQL** or **phpMyAdmin** and run this SQL:

1. Open phpMyAdmin: http://localhost/phpmyadmin

2. Select database: driving_school```sql

3. Click "Import" tabCREATE TABLE IF NOT EXISTS `password_resets` (

4. Choose file: add_pdc_time_slots.sql  `id` int NOT NULL AUTO_INCREMENT,

5. Click "Go"  `email` varchar(100) NOT NULL,

6. ✅ Done! (You should see "Import has been successfully finished")  `token` varchar(255) NOT NULL,

```  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  `expires_at` timestamp NOT NULL,

### STEP 2: Email Scheduler Setup (2 minutes)  `used` tinyint(1) NOT NULL DEFAULT '0',

  PRIMARY KEY (`id`),

**Windows:**  KEY `email` (`email`),

```bash  KEY `token` (`token`),

1. Press Win + R  KEY `expires_at` (`expires_at`)

2. Type: taskschd.msc) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

3. Press Enter```

4. Click "Create Basic Task"

5. Name: "Appointment Reminders"**Or** import the file: `add_password_resets_table.sql`

6. Trigger: Daily at 8:00 AM

7. Action: Start a program---

   - Program: C:\laragon\bin\php\php-8.4.3-Win32-vs16-x64\php.exe

   - Arguments: "D:\laragon\www\driving-school-system\send_appointment_reminder.php"### Step 2: Install PHPMailer

8. Finish!

```#### Option A: Run PowerShell Script (Easiest)

1. Right-click on `install_phpmailer.ps1`

### STEP 3: Test Everything (2 minutes)2. Select "Run with PowerShell"

3. Wait for it to complete

**Test PDC Time Slots:**

```#### Option B: Manual Download

1. Login as student1. Download: https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip

2. Go to Appointments2. Extract the ZIP

3. Click "Schedule New Appointment"3. Create folder: `D:\laragon\www\driving-school-system\phpmailer`

4. Select "PDC"4. Copy these 3 files from `PHPMailer-master\src\` to your `phpmailer` folder:

5. Choose vehicle type (Motorcycle or Car)   - `PHPMailer.php`

6. Select date: October 20, 2025   - `SMTP.php`

7. ✅ Time slots should appear: "8:00 AM - 12:00 PM", "2:00 PM - 6:00 PM"   - `Exception.php`

8. Click a time slot

9. Fill payment info---

10. Submit

```### Step 3: Test the System

1. Open browser: `http://localhost/driving-school-system/test_email_config.php`

**Test TDC Calendar:**2. Check if all components are installed

```3. Send a test email to yourself

1. Login as student4. Verify the email arrives

2. Go to Appointments

3. Click "Schedule New Appointment"---

4. Select "TDC"

5. Select any Friday or Saturday session## ✅ What You Get

6. ✅ Calendar should appear showing:

   - 📅 Full date### New Pages Created:

   - ⏰ Time- **forgot_password.php** - Request password reset

   - 👥 Available slots- **reset_password.php** - Enter new password

7. Fill payment info- **send_reset_email.php** - Email sending function

8. Submit

```### Updated Pages:

- **login.php** - Now has working "Forgot your password?" link

**Test Email Reminders:**

```powershell### Gmail Configuration (Already Set Up):

# Double-click this file:- **Email:** deduyoroy02@gmail.com

test_email_reminders.bat- **App Password:** ntue ydcf abel nqnm

- **SMTP:** smtp.gmail.com:587

# OR run this command:

cd D:\laragon\www\driving-school-system---

php send_appointment_reminder.php

```## 🎮 How Users Will Use It



---1. **User forgets password** → Clicks "Forgot your password?" on login page

2. **Enters email** → System sends reset link to their email

## ✅ VERIFICATION3. **Clicks link in email** → Opens reset password page

4. **Enters new password** → Password is updated

### Check Database5. **Logs in** → Can now use new password

```sql

-- PDC time slots created?---

SELECT COUNT(*) FROM pdc_time_slots; 

-- Should show: 40## 🔒 Security Features



-- Reminder columns added?✓ Reset links expire after 1 hour

SHOW COLUMNS FROM appointments LIKE 'reminder%';✓ Links can only be used once

-- Should show: reminder_sent, reminder_sent_at✓ 64-character random tokens

```✓ Passwords are hashed with bcrypt

✓ Generic error messages (doesn't reveal if email exists)

### Check Files Exist✓ Secure SMTP with TLS encryption

```

✓ send_appointment_reminder.php---

✓ add_pdc_time_slots.sql  

✓ test_email_reminders.bat## 🧪 Testing Checklist

✓ EMAIL_TIMESLOT_GUIDE.md (this file)

✓ phpmailer/PHPMailer.php- [ ] Run SQL to create password_resets table

✓ phpmailer/SMTP.php- [ ] Install PHPMailer files

✓ phpmailer/Exception.php- [ ] Visit test_email_config.php to verify setup

```- [ ] Send test email to yourself

- [ ] Check if email arrives (check spam too!)

---- [ ] Click the reset link in email

- [ ] Create new password

## 🎯 What You Get- [ ] Login with new password



### For Students:---



**PDC Booking:**## 📁 Files You Need

- ✅ Select date → See available time slots

- ✅ Shows "2 slots left" / "FULL" status### Core Files (Already Created):

- ✅ Can't double-book (automatic prevention)```

- ✅ Email reminder 1 day before✓ forgot_password.php

✓ reset_password.php

**TDC Booking:**✓ send_reset_email.php

- ✅ Only Friday/Saturday sessions✓ login.php (updated)

- ✅ 10 students max per session```

- ✅ Visual calendar shows selected date

- ✅ Email reminder 1 day before### Database:

```

### For Admin:✓ add_password_resets_table.sql

```

**Time Slot Management:**

- ✅ Create new time slots in database### Installation Tools:

- ✅ Set max bookings per slot```

- ✅ Auto-updates when students book✓ install_phpmailer.ps1

- ✅ View booking counts✓ test_email_config.php

```

**Email System:**

- ✅ Runs automatically every day at 8 AM### Documentation:

- ✅ Sends reminder 1 day before appointments```

- ✅ Logs all emails sent✓ FORGOT_PASSWORD_INSTALLATION.md

- ✅ Tracks which reminders sent✓ QUICK_START.md (this file)

```

---

### YOU NEED TO ADD:

## 📧 Email Details```

⚠ phpmailer/PHPMailer.php

**When:** Daily at 8:00 AM (checks for tomorrow's appointments)⚠ phpmailer/SMTP.php

⚠ phpmailer/Exception.php

**Content:**```

- Student name

- Course type (TDC or PDC)---

- Date (formatted: "Friday, October 18, 2025")

- Time## 🆘 Quick Troubleshooting

- Reminders (arrive 15 min early, bring ID)

### Email not sending?

**SMTP Settings (Already Configured):**1. Check app password: `ntue ydcf abel nqnm` (with spaces!)

- Host: smtp.gmail.com2. Make sure PHPMailer files are in `phpmailer/` folder

- Port: 5873. Check if 2-Step Verification is enabled on Gmail

- From: deduyoroy02@gmail.com4. Run `test_email_config.php` to diagnose

- Password: ntue ydcf abel nqnm

### Reset link not working?

---1. Check if password_resets table exists

2. Make sure token hasn't expired (1 hour limit)

## 🔧 Common Issues & Fixes3. Verify the link hasn't been used already



### "Time slots not loading"### PHPMailer class not found?

```sql1. Run `install_phpmailer.ps1` again

-- Re-run the migration2. Or manually download and copy the 3 files

SOURCE add_pdc_time_slots.sql;3. Check folder structure: `phpmailer/PHPMailer.php` should exist

```

---

### "Email not sending"

```bash## 🎨 Customization

# Test manually

cd D:\laragon\www\driving-school-systemWant to change something?

php send_appointment_reminder.php

### Change email expiry time:

# Check logEdit `forgot_password.php` line 33:

type logs\appointment_reminders.log```php

```$expires_at = date('Y-m-d H:i:s', strtotime('+2 hours')); // 2 hours instead of 1

```

### "Calendar not showing (TDC)"

```### Change password minimum length:

1. Clear browser cache (Ctrl + Shift + Del)Edit `reset_password.php` line 65:

2. Refresh page (F5)```php

3. Check browser console (F12) for errors} elseif (strlen($new_password) < 8) { // 8 characters instead of 6

``````



---### Change email design:

Edit `send_reset_email.php` line 31-140 (the HTML email template)

## 📊 Quick SQL Queries

---

**See tomorrow's appointments:**

```sql## 📧 Need Help?

SELECT u.full_name, u.email, a.course_selection, a.appointment_date

FROM appointments a1. Check `FORGOT_PASSWORD_INSTALLATION.md` for detailed guide

JOIN users u ON a.student_id = u.id2. Run `test_email_config.php` to diagnose issues

WHERE DATE(a.appointment_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)3. Check PHP error logs in Laragon

AND a.status != 'cancelled';4. Verify Gmail credentials are correct

```

---

**Check time slot availability:**

```sql## ✨ You're All Set!

SELECT slot_date, slot_label, 

       current_bookings, max_bookings,After completing the 3 steps above, your forgot password system is ready to use!

       (max_bookings - current_bookings) as available

FROM pdc_time_slots**Test URL:** http://localhost/driving-school-system/login.php

WHERE slot_date >= CURDATE()

ORDER BY slot_date, slot_time_start;Click "Forgot your password?" to try it out! 🚀

```

**View recent reminders sent:**
```sql
SELECT u.full_name, a.appointment_date, a.reminder_sent_at
FROM appointments a
JOIN users u ON a.student_id = u.id
WHERE a.reminder_sent = 1
ORDER BY a.reminder_sent_at DESC
LIMIT 10;
```

---

## 🎉 You're Done!

**System is ready when you see:**
- ✅ 40 time slots in database
- ✅ Time slots appear when booking PDC
- ✅ Calendar appears when booking TDC
- ✅ Task Scheduler has daily task
- ✅ Test email received successfully

**Need Help?**
- Check: EMAIL_TIMESLOT_GUIDE.md (full documentation)
- Run: test_email_reminders.bat (test emails)
- View: logs/appointment_reminders.log (email history)

---

**Setup Time:** ~5 minutes
**Difficulty:** Easy ⭐
**Status:** ✅ Production Ready
