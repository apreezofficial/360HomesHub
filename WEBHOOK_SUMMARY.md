# Webhook & Payments Implementation

## ✅ Webhooks Implemented
**File:** `api/payments/webhook.php`

This endpoint handles real-time payment updates from Paystack and Flutterwave.
- **Paystack:** Verifies `x-paystack-signature` header.
- **Flutterwave:** Verifies `verif-hash` header against `FLUTTERWAVE_SECRET_HASH`.
- **Database:** Transactionally updates `bookings` status to 'paid' and logs to `transactions`.
- **Notifications:** Emails guest/host/admin and sends in-app notifications.
- **Logging:** All activity is logged via `ActivityLogger`.

## ✅ Payment Result Page
**File:** `payment_result.php`

- Beautiful, clean UI for payment success/failure.
- Auto-closes after 10 seconds on success.
- Hides raw JSON data from users.

## ✅ Redirects Updated
**File:** `api/payments/verify.php`

- Now redirects users to `payment_result.php` instead of showing raw JSON.
- Handles errors gracefully with user-friendly messages.

## ✅ Setup Instructions

### 1. Configure Keys
Add these to `config/env.php`:

```php
// Flutterwave Webhook Secret (Create this yourself)
define('FLUTTERWAVE_SECRET_HASH', 'your_secret_hash_here'); 

// App Base URL (For redirects)
define('APP_URL', 'http://localhost/360HomesHub');
```

### 2. Set Webhook URL in Dashboards
- **Paystack:** `https://your-domain.com/api/payments/webhook.php`
- **Flutterwave:** `https://your-domain.com/api/payments/webhook.php`

*(For local testing use Ngrok: `https://your-ngrok.ngrok-free.app/360HomesHub/api/payments/webhook.php`)*

### 3. Verify Setup
Run `http://localhost/360HomesHub/check_env.php` to verify your keys are set correctly.

## 🚀 Testing

1.  **Standard Checkout:** Complete a payment. You should be redirected to the beautiful success page.
2.  **Webhook Simulation:** Use Postman to POST to `api/payments/webhook.php` (see `WEBHOOK_SETUP_GUIDE.md` for payloads).
3.  **Logs:** Check `activity_logs` table for `webhook_payment_verified` events.
