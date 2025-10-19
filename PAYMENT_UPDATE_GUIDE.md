# 🚀 PAYMENT SYSTEM UPDATE - GCASH ONLY

## ✅ What Changed

**BEFORE:**
- Multiple payment methods (GCash, Bank Transfer, Card, Cash)
- Reference number input field
- GCash QR code shown

**AFTER:**
- ✅ Only GCash payment method
- ✅ Upload screenshot of successful transaction (not reference number)
- ✅ Only QR code name and QR code image displayed
- ✅ Image preview before submission

---

## 📋 INSTALLATION STEPS

### Step 1: Run SQL Scripts in HeidiSQL (3 minutes)

**Run these TWO scripts in order:**

#### A. Update Payment System
```
File: update_payment_system.sql
```
1. Open HeidiSQL
2. Connect to your database
3. Click "File" → "Load SQL file"
4. Select: `update_payment_system.sql`
5. Click "Execute" (F9)
6. ✅ Should see: "Payment system update completed!"

#### B. Add Time Slots & Email System
```
File: add_pdc_time_slots.sql
```
1. Click "File" → "Load SQL file"
2. Select: `add_pdc_time_slots.sql`
3. Click "Execute" (F9)
4. ✅ Should see: "Migration completed successfully!"

**OR run them separately line by line if you get errors.**

---

### Step 2: Create Uploads Directory

**Option A: Using File Explorer**
```
1. Go to: D:\laragon\www\driving-school-system
2. Create new folder named: uploads
3. Inside uploads, create folder: payment_proofs
4. Right-click payment_proofs → Properties → Security
5. Make sure "Users" has "Write" permission
```

**Option B: Using PowerShell**
```powershell
cd D:\laragon\www\driving-school-system
mkdir -p uploads/payment_proofs
icacls uploads /grant Users:F
```

---

### Step 3: Test the System (2 minutes)

1. **Login as student**
2. **Go to Appointments page**
3. **Click "Schedule New Appointment"**
4. **Select TDC or PDC course**
5. **Fill course details**
6. **Scroll to Payment Section**
7. **Verify you see:**
   ```
   ┌─────────────────────────────────────┐
   │ Pay via GCash                       │
   │                                     │
   │ GCash: Success Driving School       │
   │ [QR CODE IMAGE]                     │
   │                                     │
   │ 20% Down Payment: ₱XXX.XX          │
   │                                     │
   │ Upload Payment Proof:               │
   │ [Choose File] No file chosen        │
   │                                     │
   │ (Image preview will appear here)    │
   └─────────────────────────────────────┘
   ```
8. **Click "Choose File"**
9. **Select a screenshot image**
10. **Verify:** Image preview appears below
11. **Submit form**
12. **Check:** File saved in `uploads/payment_proofs/`

---

## 🎯 What the System Does Now

### Payment Flow:
```
Student books appointment
    ↓
Sees GCash QR code
    ↓
Pays via GCash app (scans QR)
    ↓
Takes screenshot of successful transaction
    ↓
Uploads screenshot in form
    ↓
Image preview shows
    ↓
Submits form
    ↓
File saved: uploads/payment_proofs/12345_proof_202510161234.jpg
    ↓
Admin can view screenshot to verify payment
```

### File Naming:
```
Format: {appointment_id}_proof_{timestamp}.{extension}
Example: 42_proof_20251016123045.jpg

Where:
- 42 = appointment ID
- 20251016123045 = 2025-10-16 12:30:45
- jpg = original file extension
```

---

## 🗂️ Database Changes

**New Column Added:**
```sql
appointments table:
├─ payment_proof VARCHAR(255) NULL
   - Stores filename of uploaded screenshot
   - Example: "42_proof_20251016123045.jpg"
```

**No columns removed** - payment_reference still exists but not used in form

---

## 📊 Admin View (Future Enhancement)

Admin can check payment proofs:
```sql
SELECT 
    a.id,
    u.full_name,
    a.payment_amount,
    a.payment_method,
    a.payment_proof,
    CONCAT('uploads/payment_proofs/', a.payment_proof) as file_path
FROM appointments a
JOIN users u ON a.student_id = u.id
WHERE a.payment_proof IS NOT NULL
ORDER BY a.created_at DESC;
```

---

## ✅ Verification Checklist

### Database:
- [ ] `update_payment_system.sql` executed successfully
- [ ] `add_pdc_time_slots.sql` executed successfully
- [ ] `payment_proof` column exists in `appointments` table
- [ ] `pdc_time_slots` table created (40 rows)
- [ ] Triggers created successfully

### Files:
- [ ] `uploads/` directory exists
- [ ] `uploads/payment_proofs/` directory exists
- [ ] Directory has write permissions

### Form:
- [ ] Payment section shows "Pay via GCash" heading
- [ ] GCash name displays: "Success Driving School"
- [ ] QR code image displays
- [ ] File upload button shows
- [ ] No dropdown for payment methods
- [ ] No reference number input field

### Functionality:
- [ ] Can select image file (jpg, jpeg, png, gif)
- [ ] Image preview appears after selection
- [ ] Form submits successfully
- [ ] File saved in `uploads/payment_proofs/`
- [ ] Filename stored in database

---

## 🐛 Troubleshooting

### Error: "Column already exists"
```
This is OK! It means the column was already added.
The script checks before adding.
```

### Error: "Cannot create directory"
```powershell
# Run as Administrator:
cd D:\laragon\www\driving-school-system
mkdir uploads\payment_proofs
icacls uploads /grant Everyone:F
```

### Error: "Failed to upload file"
```
1. Check uploads directory exists
2. Check write permissions
3. Check file size (max 5MB in code)
4. Check file type (jpg, jpeg, png, gif only)
```

### Image not displaying
```
1. Check file uploaded successfully
2. Check path: ../uploads/payment_proofs/{filename}
3. Check browser console (F12) for errors
```

---

## 🎉 Success Criteria

**System is working when:**

✅ Payment section shows ONLY GCash option
✅ QR code name "Success Driving School" displays
✅ QR code image displays
✅ File upload button present
✅ Image preview works
✅ File uploads to `uploads/payment_proofs/`
✅ Filename saves in database
✅ Form submission successful

---

## 📁 Updated Files

1. ✅ `user/appointments.php` - Updated payment form (GCash only + file upload)
2. ✅ `update_payment_system.sql` - Adds payment_proof column
3. ✅ `add_pdc_time_slots.sql` - Fixed SQL syntax for HeidiSQL
4. ✅ `PAYMENT_UPDATE_GUIDE.md` - This guide

---

## 🔄 Next Steps

1. Run both SQL files in HeidiSQL
2. Create uploads directory
3. Test booking with image upload
4. Verify file saved correctly
5. (Optional) Create admin page to view payment proofs

---

**Status:** ✅ READY TO INSTALL
**Estimated Time:** 5 minutes
**Difficulty:** Easy ⭐
