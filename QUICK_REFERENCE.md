# Quick Reference: Activity Logging

## ✅ What Was Done

### 1. **Security Fix: Price Calculation**
- ❌ **Before:** Frontend could send any price to checkout
- ✅ **After:** Backend calculates price from database
- **Impact:** Prevents price manipulation attacks

### 2. **Activity Logging System**
Created comprehensive logging for all important booking actions:

#### Database Table Created
- `activity_logs` table in `360homesub` database
- Tracks: user, action, timestamp, IP, metadata

#### Files Created/Modified
1. **Created:**
   - `db/activity_logs_migration.sql` - Database schema
   - `utils/activity_logger.php` - Logging utility
   - `ACTIVITY_LOGGING_SUMMARY.md` - Full documentation

2. **Modified (Added Logging):**
   - `api/bookings/checkout.php` - Checkout & payment init
   - `api/bookings/create.php` - Booking creation
   - `api/bookings/approve.php` - Booking approval
   - `api/bookings/reject.php` - Booking rejection

## 📊 What Gets Logged

### Checkout Events
- ✅ Checkout initiated
- ✅ Payment gateway initialized (Paystack/Flutterwave)
- ✅ Failed attempts (unauthorized, invalid status, etc.)
- ✅ Errors (database, general)

### Booking Events
- ✅ Booking created
- ✅ Booking approved
- ✅ Booking rejected (with reason)
- ✅ Failed attempts
- ✅ Unauthorized access attempts

### Metadata Captured
- User ID, IP address, User agent
- Booking details (dates, amounts, property)
- Payment details (gateway, reference, amount)
- Error messages
- Rejection reasons

## 🔍 How to View Logs

### In phpMyAdmin
```sql
-- Recent activities
SELECT * FROM activity_logs 
ORDER BY created_at DESC 
LIMIT 50;

-- Specific user's activities
SELECT * FROM activity_logs 
WHERE user_id = 1 
ORDER BY created_at DESC;

-- All checkout activities
SELECT * FROM activity_logs 
WHERE action_type LIKE 'checkout_%' 
ORDER BY created_at DESC;

-- Failed attempts (security monitoring)
SELECT * FROM activity_logs 
WHERE action_type LIKE '%failed%' 
   OR action_type LIKE '%unauthorized%'
ORDER BY created_at DESC;

-- Payment initializations
SELECT * FROM activity_logs 
WHERE action_type LIKE 'payment_%'
ORDER BY created_at DESC;
```

## 🎯 Key Changes in Checkout

### Before
```javascript
// Frontend sends price (INSECURE!)
{
  "booking_id": 1,
  "payment_gateway": "paystack",
  "amount": 50000  // ❌ Can be manipulated
}
```

### After
```javascript
// Frontend only sends booking ID (SECURE!)
{
  "booking_id": 1,
  "payment_gateway": "paystack"
  // ✅ Backend calculates amount from database
}
```

## 📝 Testing the Changes

### 1. Test Checkout (Updated in tester.html)
```json
{
  "booking_id": 1
}
```
- No need to send `payment_gateway` (defaults to paystack)
- No need to send `amount` (calculated by backend)

### 2. Check Activity Logs
After testing, run this SQL:
```sql
SELECT 
  id,
  user_id,
  action_type,
  action_description,
  created_at
FROM activity_logs
ORDER BY created_at DESC
LIMIT 10;
```

## 🚀 What's Next (Optional)

1. **Admin Dashboard**
   - Create a page to view activity logs
   - Add filters (user, date range, action type)

2. **Alerts**
   - Set up email alerts for suspicious activities
   - Monitor failed payment attempts

3. **Analytics**
   - Track conversion rates
   - Identify bottlenecks in booking flow

## 📞 Support

For questions or issues:
1. Check `ACTIVITY_LOGGING_SUMMARY.md` for detailed docs
2. Review the activity logs in database
3. Check error logs in PHP error log

---

**Database:** 360homesub  
**Table:** activity_logs  
**Status:** ✅ Active and logging
