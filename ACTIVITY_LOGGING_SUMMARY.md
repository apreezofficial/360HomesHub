# Activity Logging Implementation Summary

## Overview
This document summarizes the implementation of comprehensive activity logging for the 360HomesHub booking system.

## Changes Made

### 1. Database Schema
**File:** `db/activity_logs_migration.sql`
- Created `activity_logs` table with the following structure:
  - `id`: Primary key
  - `user_id`: Foreign key to users table (nullable for system actions)
  - `action_type`: Type of action (e.g., 'booking_created', 'payment_initiated')
  - `action_description`: Human-readable description
  - `entity_type`: Type of entity affected (e.g., 'booking', 'payment')
  - `entity_id`: ID of the affected entity
  - `ip_address`: Client IP address
  - `user_agent`: Browser user agent
  - `metadata`: JSON field for additional data
  - `created_at`: Timestamp
- Added proper indexes for performance:
  - `idx_user_id`
  - `idx_action_type`
  - `idx_entity` (composite on entity_type and entity_id)
  - `idx_created_at`

### 2. Activity Logger Utility
**File:** `utils/activity_logger.php`
- Created `ActivityLogger` class with static methods for logging
- **Main method:** `log()` - Logs any activity with full metadata
- **Helper methods:**
  - `logBooking()` - Quick log for booking-related actions
  - `logPayment()` - Quick log for payment-related actions
  - `logProperty()` - Quick log for property-related actions
  - `logUser()` - Quick log for user-related actions
- **Features:**
  - Automatic IP address detection (handles proxies and load balancers)
  - User agent capture
  - JSON metadata storage
  - Error handling with fallback logging

### 3. Checkout Endpoint Updates
**File:** `api/bookings/checkout.php`

#### Security Enhancement
- **REMOVED:** Frontend-provided price/amount
- **ADDED:** Backend price calculation from database
- Price is now calculated from `bookings.total_amount` (which was already calculated server-side during booking creation)
- This prevents price manipulation attacks

#### Activity Logging
Logs the following events:
1. **checkout_failed** - Booking not found
2. **checkout_unauthorized** - User not authorized for this booking
3. **checkout_invalid_status** - Booking not in approved state
4. **checkout_initiated** - Successful checkout initiation (with full metadata)
5. **payment_paystack_initialized** - Paystack payment gateway initialized
6. **payment_paystack_init_failed** - Paystack initialization failed
7. **payment_flutterwave_initialized** - Flutterwave payment gateway initialized
8. **payment_flutterwave_init_failed** - Flutterwave initialization failed
9. **checkout_invalid_gateway** - Invalid payment gateway selected
10. **checkout_db_error** - Database error during checkout
11. **checkout_error** - General error during checkout

### 4. Booking Creation Updates
**File:** `api/bookings/create.php`

#### Activity Logging
Logs the following events:
1. **booking_failed** - Property not found
2. **booking_created** - Successful booking creation (with full booking details)
3. **booking_creation_failed** - Failed to execute booking creation query
4. **booking_db_error** - Database error during creation
5. **booking_error** - General error during creation

### 5. Booking Approval Updates
**File:** `api/bookings/approve.php`

#### Activity Logging
Logs the following events:
1. **booking_approve_failed** - Booking not found
2. **booking_approve_unauthorized** - User not authorized to approve
3. **booking_approved** - Successful booking approval (with guest and property details)
4. **booking_approve_failed** - Failed to update booking status
5. **booking_approve_db_error** - Database error during approval
6. **booking_approve_error** - General error during approval

### 6. Booking Rejection Updates
**File:** `api/bookings/reject.php`

#### Activity Logging
Logs the following events:
1. **booking_reject_failed** - Booking not found
2. **booking_reject_unauthorized** - User not authorized to reject
3. **booking_rejected** - Successful booking rejection (includes rejection reason)
4. **booking_reject_failed** - Failed to update booking status
5. **booking_reject_db_error** - Database error during rejection
6. **booking_reject_error** - General error during rejection

## Metadata Captured

### Checkout Events
- `payment_gateway`: Gateway used (paystack/flutterwave)
- `total_amount`: Amount being charged
- `property_id`: Property being booked
- `host_id`: Host of the property
- `reference`: Payment reference (Paystack)
- `tx_ref`: Transaction reference (Flutterwave)
- `email`: User email

### Booking Events
- `property_id`: Property ID
- `host_id`: Host user ID
- `guest_id`: Guest user ID
- `check_in`: Check-in date
- `check_out`: Check-out date
- `nights`: Number of nights
- `adults`: Number of adults
- `children`: Number of children
- `rooms`: Number of rooms
- `total_amount`: Total booking amount
- `rejection_reason`: Reason for rejection (if applicable)

### Error Events
- `error`: Error message
- `reason`: Specific reason code
- `actual_host_id`: Actual host ID (for unauthorized attempts)
- `actual_guest_id`: Actual guest ID (for unauthorized attempts)

## Security Improvements

### Price Calculation
- **Before:** Frontend could send any price to checkout endpoint
- **After:** Backend calculates and validates price from database
- **Impact:** Prevents price manipulation attacks

### Activity Tracking
- All important actions are now logged with:
  - User ID (who did it)
  - IP address (from where)
  - User agent (what browser/device)
  - Timestamp (when)
  - Full context (metadata)

## Database Migration Required

To use this feature, run the migration:

```sql
-- Run this SQL to create the activity_logs table
SOURCE db/activity_logs_migration.sql;
```

Or manually execute the SQL in phpMyAdmin or MySQL client.

## Usage Examples

### Query Recent Activities
```sql
-- Get all activities for a specific user
SELECT * FROM activity_logs 
WHERE user_id = 1 
ORDER BY created_at DESC 
LIMIT 50;

-- Get all booking-related activities
SELECT * FROM activity_logs 
WHERE entity_type = 'booking' 
ORDER BY created_at DESC;

-- Get all payment initializations
SELECT * FROM activity_logs 
WHERE action_type LIKE 'payment_%' 
ORDER BY created_at DESC;

-- Get failed checkout attempts
SELECT * FROM activity_logs 
WHERE action_type LIKE 'checkout_%failed%' 
ORDER BY created_at DESC;
```

### Audit Trail
The activity logs provide a complete audit trail for:
- Compliance requirements
- Security monitoring
- Debugging issues
- User behavior analysis
- Fraud detection

## Next Steps (Recommended)

1. **Create Admin Dashboard View**
   - Display recent activities
   - Filter by user, action type, date range
   - Export functionality

2. **Add More Logging**
   - Payment verification events
   - User login/logout
   - Property creation/updates
   - Admin actions

3. **Monitoring & Alerts**
   - Set up alerts for suspicious activities
   - Monitor failed attempts
   - Track payment failures

4. **Data Retention Policy**
   - Archive old logs
   - Implement cleanup for logs older than X months
   - Backup important logs

## Notes

- All logging is non-blocking (errors are logged but don't stop execution)
- IP detection handles proxies and load balancers
- Metadata is stored as JSON for flexibility
- Indexes ensure fast queries even with millions of records
