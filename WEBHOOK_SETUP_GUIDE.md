# 🔗 Webhook Setup Guide

Webhooks ensure that payments are confirmed even if the user closes their browser before being redirected back to your site.

## 1. Paystack Setup

1.  Log in to your [Paystack Dashboard](https://dashboard.paystack.com/).
2.  Go to **Settings** > **API Keys & Webhooks**.
3.  Scroll down to the **Webhooks** section.
4.  **Webhook URL:** Enter your live or test URL.
    *   **Local Development:** You must use a tunneling service like [Ngrok](https://ngrok.com/) to expose your local server.
        *   Example: `https://your-ngrok-url.ngrok-free.app/360HomesHub/api/payments/webhook.php`
    *   **Production:** `https://yourdomain.com/api/payments/webhook.php`
5.  **Save Changes**.

## 2. Flutterwave Setup

1.  Log in to your [Flutterwave Dashboard](https://dashboard.flutterwave.com/).
2.  Go to **Settings** > **Webhooks**.
3.  **URL:** Enter your webhook URL (same as above).
4.  **Secret Hash:**
    *   Create a secret string (e.g., `my_secret_hash_value_123`).
    *   **Crucial:** You MUST add this secret hash to your `config/env.php` file as `FLUTTERWAVE_SECRET_HASH`.
    *   Flutterwave sends this hash in the header `verif-hash`, and we verify it against your config.
5.  **Save**.

## 3. Configuration (config/env.php)

Ensure your `config/env.php` has the following constants defined:

```php
// Paystack
define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxx');

// Flutterwave
define('FLUTTERWAVE_SECRET_KEY', 'FLWSECK_TEST-xxxxxxxxxxxxxxxxxxxxx-X');
define('FLUTTERWAVE_SECRET_HASH', 'my_secret_hash_value_123'); // MUST MATCH DASHBOARD
```

## 4. Testing Webhooks Locally

Since webhooks require a public URL, you cannot test `http://localhost/...` directly from Paystack/Flutterwave.

### Option A: Use Ngrok (Recommended)
1.  Download and install Ngrok.
2.  Run: `ngrok http 80` (or whatever port XAMPP uses).
3.  Copy the `https` URL provided by Ngrok.
4.  Use that URL in your Paystack/FW dashboard settings.

### Option B: Manual POST Test
You can use Postman to simulate a webhook call to `http://localhost/360HomesHub/api/payments/webhook.php`.

**Paystack Simulation (Header: `x-paystack-signature` required):**
You'll need to generate a HMAC SHA512 signature of the body using your secret key and add it as a header. This is tricky to do manually.

**Flutterwave Simulation (Header: `verif-hash` required):**
1.  Set Header `verif-hash` to your `FLUTTERWAVE_SECRET_HASH`.
2.  Body (JSON):
    ```json
    {
      "event": "charge.completed",
      "data": {
        "id": 12345,
        "tx_ref": "360HB-1700000000-5",
        "flw_ref": "FLW-MOCK-12345",
        "amount": 50000,
        "currency": "NGN",
        "status": "successful",
        "customer": {
          "email": "user@example.com"
        },
        "meta": {
           "booking_id": 5
        }
      }
    }
    ```

## 5. Troubleshooting

Check the **Activity Logs**!
We now log all webhook attempts, failures, and successes.

```sql
SELECT * FROM activity_logs WHERE action_type LIKE 'webhook_%' ORDER BY created_at DESC;
```
