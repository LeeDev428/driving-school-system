// API ENDPOINTS REFERENCE - appointments.php
// Last Updated: October 9, 2025

// ============================================
// 1. GET CALENDAR EVENTS
// ============================================
/*
 * Endpoint: POST with action=get_calendar_events
 * Description: Fetches all appointments for a specific year/month
 * 
 * Request:
 *   - action: 'get_calendar_events'
 *   - year: YYYY (int)
 *   - month: 1-12 (int)
 * 
 * Response: JSON Array
 *   [
 *     {
 *       id: int,
 *       date: 'YYYY-MM-DD',
 *       time: '9:00 AM',
 *       type: 'TDC' or 'PDC - Motorcycle',
 *       course_selection: 'TDC' or 'PDC',
 *       status: 'pending' | 'confirmed' | 'completed' | 'cancelled',
 *       color: '#9c27b0' (TDC purple) or '#ff9800' (PDC orange)
 *     }
 *   ]
 */

fetch('user/appointments.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_calendar_events&year=2025&month=10'
})
.then(r => r.json())
.then(data => console.log('Appointments:', data));


// ============================================
// 2. GET TDC SESSIONS
// ============================================
/*
 * Endpoint: POST with action=get_tdc_sessions
 * Description: Fetches available TDC sessions (Fridays & Saturdays)
 * 
 * Request:
 *   - action: 'get_tdc_sessions'
 * 
 * Response: JSON Array
 *   [
 *     {
 *       id: int,
 *       date: 'YYYY-MM-DD',
 *       day: 'Friday' or 'Saturday',
 *       start_time: '9:00 AM',
 *       end_time: '5:00 PM',
 *       instructor: 'John Doe' or 'TBA',
 *       current_enrollments: 5,
 *       max_enrollments: 10,
 *       available_slots: 5,
 *       is_full: false
 *     }
 *   ]
 */

fetch('user/appointments.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_tdc_sessions'
})
.then(r => r.json())
.then(data => console.log('TDC Sessions:', data));


// ============================================
// 3. SCHEDULE TDC APPOINTMENT
// ============================================
/*
 * Endpoint: POST with action=schedule_appointment
 * Description: Books a TDC session (Theoretical Driving Course)
 * 
 * Request (FormData or URLSearchParams):
 *   - action: 'schedule_appointment'
 *   - course_selection: 'TDC'
 *   - tdc_session_id: int (from get_tdc_sessions)
 *   - preferred_instructor: int (optional, instructor ID)
 *   - preferred_vehicle: int (optional, vehicle ID)
 *   - notes: string (optional)
 *   - payment_amount: float (should be 179.80 for TDC - 20% of ₱899)
 *   - payment_method: 'online' | 'bank_transfer' | 'card' | 'cash'
 *   - payment_reference: string (required for online/bank/card)
 * 
 * Response: JSON
 *   {
 *     success: true,
 *     message: 'TDC session booked successfully! Awaiting admin confirmation.'
 *   }
 *   OR
 *   {
 *     success: false,
 *     message: 'Session is full. Please select another date.'
 *   }
 */

const formData = new FormData();
formData.append('action', 'schedule_appointment');
formData.append('course_selection', 'TDC');
formData.append('tdc_session_id', '5');
formData.append('preferred_instructor', '2');
formData.append('notes', 'Please confirm my session');
formData.append('payment_amount', '179.80');
formData.append('payment_method', 'online');
formData.append('payment_reference', '1234567890123');

fetch('user/appointments.php', {
    method: 'POST',
    body: formData
})
.then(r => r.json())
.then(data => console.log('TDC Booking:', data));


// ============================================
// 4. SCHEDULE PDC APPOINTMENT
// ============================================
/*
 * Endpoint: POST with action=schedule_appointment
 * Description: Books a PDC session (Practical Driving Course)
 * 
 * Request (FormData or URLSearchParams):
 *   - action: 'schedule_appointment'
 *   - course_selection: 'PDC'
 *   - appointment_date: 'YYYY-MM-DD'
 *   - start_time: 'HH:MM:SS' (e.g., '08:00:00', '11:00:00', '14:00:00')
 *   - duration_days: int (2 or 4)
 *   - vehicle_type: 'motorcycle' or 'car'
 *   - vehicle_transmission: 'automatic' or 'manual'
 *   - notes: string (optional)
 *   - payment_amount: float
 *       Motorcycle: 400.00 (20% of ₱2,000)
 *       Car: 900.00 (20% of ₱4,500)
 *   - payment_method: 'online' | 'bank_transfer' | 'card' | 'cash'
 *   - payment_reference: string (required for online/bank/card)
 * 
 * Response: JSON
 *   {
 *     success: true,
 *     message: 'PDC appointment scheduled successfully! Awaiting admin confirmation.'
 *   }
 */

const formData = new FormData();
formData.append('action', 'schedule_appointment');
formData.append('course_selection', 'PDC');
formData.append('appointment_date', '2025-10-15');
formData.append('start_time', '08:00:00');
formData.append('duration_days', '4');
formData.append('vehicle_type', 'car');
formData.append('vehicle_transmission', 'automatic');
formData.append('notes', 'Prefer morning sessions');
formData.append('payment_amount', '900.00');
formData.append('payment_method', 'online');
formData.append('payment_reference', '1234567890123');

fetch('user/appointments.php', {
    method: 'POST',
    body: formData
})
.then(r => r.json())
.then(data => console.log('PDC Booking:', data));


// ============================================
// PRICING REFERENCE
// ============================================
/*
 * TDC (Theoretical Driving Course):
 *   - Full Price: ₱899
 *   - 20% Down Payment: ₱179.80
 *   - Schedule: Every Friday & Saturday
 *   - Capacity: 10 students per session
 *   - Includes: Instructor selection, Vehicle selection
 * 
 * PDC (Practical Driving Course):
 *   Motorcycle:
 *     - Full Price: ₱2,000
 *     - 20% Down Payment: ₱400
 *   
 *   Car:
 *     - Full Price: ₱4,500
 *     - 20% Down Payment: ₱900
 *   
 *   - Duration Options: 2 or 4 days
 *   - Transmission: Automatic or Manual
 *   - Time Slots: 8-11 AM, 11 AM-2 PM, 2-5 PM
 *   - NO instructor selection (auto-assigned)
 */


// ============================================
// VALIDATION RULES
// ============================================
/*
 * TDC Booking:
 *   ✅ REQUIRED: tdc_session_id
 *   ✅ REQUIRED: payment_amount (must be 179.80)
 *   ✅ REQUIRED: payment_method
 *   ✅ REQUIRED: payment_reference (if method is online/bank/card)
 *   ⚠️ Session must not be full (current_enrollments < 10)
 *   ⚠️ Session must be active
 * 
 * PDC Booking:
 *   ✅ REQUIRED: appointment_date
 *   ✅ REQUIRED: start_time
 *   ✅ REQUIRED: duration_days (2 or 4)
 *   ✅ REQUIRED: vehicle_type (motorcycle or car)
 *   ✅ REQUIRED: vehicle_transmission (automatic or manual)
 *   ✅ REQUIRED: payment_amount
 *       - ₱400 if motorcycle
 *       - ₱900 if car
 *   ✅ REQUIRED: payment_method
 *   ✅ REQUIRED: payment_reference (if method is online/bank/card)
 */


// ============================================
// DATABASE STRUCTURE REFERENCE
// ============================================
/*
 * tdc_sessions table:
 *   - id: INT PRIMARY KEY
 *   - session_date: DATE
 *   - session_day: ENUM('Friday', 'Saturday')
 *   - start_time: TIME
 *   - end_time: TIME
 *   - max_enrollments: INT (default 10)
 *   - current_enrollments: INT (auto-updated by triggers)
 *   - instructor_id: INT (nullable)
 *   - status: ENUM('active', 'full', 'cancelled')
 * 
 * appointments table (new fields):
 *   - course_selection: ENUM('TDC', 'PDC')
 *   - tdc_session_id: INT (nullable, for TDC only)
 *   - duration_days: INT (nullable, for PDC only)
 *   - vehicle_type: ENUM('motorcycle', 'car') (nullable, for PDC only)
 *   - vehicle_transmission: ENUM('automatic', 'manual') (nullable, for PDC only)
 *   - course_price: DECIMAL(10,2) (stores actual price: 899, 2000, or 4500)
 */


// ============================================
// ERROR HANDLING
// ============================================
/*
 * Common Error Responses:
 * 
 * 1. Session Full:
 *    { success: false, message: 'Session is full. Please select another date.' }
 * 
 * 2. Session Not Available:
 *    { success: false, message: 'Session not available.' }
 * 
 * 3. Database Error:
 *    { success: false, message: 'Database error: [MySQL error]' }
 * 
 * 4. Invalid Action:
 *    { success: false, message: 'Invalid action.' }
 */
