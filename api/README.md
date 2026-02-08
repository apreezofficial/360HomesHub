# 360HomesHub API Documentation

Welcome to the 360HomesHub API documentation. This API powers the 360HomesHub platform, providing endpoints for property listings, bookings, user authentication, payments, and more.

## Base URL
All API requests are made to:
`https://[YOUR_DOMAIN]/api/`

## Authentication
Most endpoints require authentication using JSON Web Tokens (JWT). 
1. Obtain a token by calling the `/auth/login.php` or `/auth/verify_otp.php` endpoint.
2. Include the token in the `Authorization` header for subsequent requests:
   `Authorization: Bearer <your_jwt_token>`

## Global Response Format
The API returns JSON responses with the following structure:

### Success Response
```json
{
  "status": "success",
  "message": "Operation successful.",
  "data": { ... }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error description.",
  "errors": { ... }
}
```

---

## API Endpoints

### 1. Authentication (`/api/auth/`)

| Endpoint | Method | Description | Parameters (JSON) |
| :--- | :--- | :--- | :--- |
| `register_email.php` | POST | Register with email/password. | `email`, `password` |
| `register_phone.php` | POST | Register with phone/password. | `phone`, `password` |
| `login.php` | POST | Authenticate and get a token. | `email` OR `phone`, `password` |
| `verify_otp.php` | POST | Verify registration OTP. | `user_id`, `otp` |
| `set_password.php` | POST | Set or reset password. | `user_id`, `password` |
| `google_auth.php` | POST | Authenticate via Google. | `google_token` |

### 2. Onboarding (`/api/onboarding/`)
*Requires Authentication*

| Endpoint | Method | Description | Parameters (JSON) |
| :--- | :--- | :--- | :--- |
| `set_role.php` | POST | Set user role (guest/host). | `role` |
| `set_profile.php` | POST | Set profile details. | `full_name`, `email` (if phone reg) |
| `set_location.php` | POST | Set user location. | `latitude`, `longitude`, `address` |
| `upload_avatar.php` | POST | Upload profile picture. | `avatar` (file) |

### 3. Properties (`/api/properties/`)

| Endpoint | Method | Auth | Description | Parameters (JSON) |
| :--- | :--- | :--- | :--- | :--- |
| `list.php` | POST | Yes | List properties sorted by distance. | `latitude`, `longitude`, `page` |
| `details.php` | GET | No | Get full details of a property. | `id` (query param) |
| `amenities.php` | GET | No | List available amenities. | - |
| `calculate_price.php` | POST | No | Calculate total price for dates. | `property_id`, `check_in`, `check_out` |

#### Host Property Onboarding (`/api/properties/onboarding/`)
*Requires 'host' role*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `init_listing.php` | POST | Initialize a new property listing. |
| `update_listing.php` | POST | Update property details during onboarding. |
| `pricing_preview.php` | POST | Preview host earnings based on price. |

### 4. Bookings (`/api/bookings/`)
*Requires Authentication*

| Endpoint | Method | Description | Parameters (JSON) |
| :--- | :--- | :--- | :--- |
| `create.php` | POST | Create a new booking request. | `property_id`, `check_in`, `check_out`, `adults`, `children`, `rooms` |
| `calculate.php` | POST | Calculate booking costs including fees. | `property_id`, `check_in`, `check_out` |
| `status.php` | GET | Get status of a booking. | `id` (query param) |
| `approve.php` | POST | Approve a booking (Host only). | `booking_id` |
| `reject.php` | POST | Reject a booking (Host only). | `booking_id` |
| `checkout.php` | POST | Initialize payment for booking. | `booking_id` |

### 5. Messages & Notifications (`/api/messages/`, `/api/notifications/`)
*Requires Authentication*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `messages/list.php` | GET | List chat conversations. |
| `messages/view.php` | GET | View messages for a specific chat. |
| `messages/unread_count.php`| GET | Get total unread messages count. |
| `notifications/unread_count.php`| GET | Get total unread notifications. |
| `notifications/notify.php`| POST | (Internal) Send a notification. |

### 6. Payments (`/api/payments/`)
*Requires Authentication*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `checkout.php` | POST | Create a payment session/link. |
| `verify.php` | POST | Verify a payment status. |
| `webhook.php` | POST | (Admin/External) Payment provider webhook listener. |

### 7. KYC Verification (`/api/kyc/`)
*Requires Authentication*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `start_kyc.php` | POST | Initiate KYC process. |
| `upload_documents.php` | POST | Upload ID documents. |
| `upload_selfie.php` | POST | Upload a selfie for verification. |
| `kyc_status.php` | GET | Check current KYC status. |

### 8. Admin Management (`/api/admin/`)
*Requires 'admin' role*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `login.php` | POST | Admin-specific login. |
| `stats.php` | GET | Platform-wide statistics. |
| `users.php` | GET | List and manage users. |
| `properties.php` | GET | List all properties. |
| `kyc_list.php` | GET | List pending KYC verifications. |
| `approve_kyc.php` | POST | Approve a user's KYC. |
| `reject_kyc.php` | POST | Reject a user's KYC. |
| `transactions.php` | GET | List all payment transactions. |

---

## Error Codes
- `400 Bad Request`: Missing parameters or validation failed.
- `401 Unauthorized`: Invalid or missing JWT token.
- `403 Forbidden`: Insufficient permissions (e.g., guest trying host actions).
- `404 Not Found`: Resource does not exist.
- `405 Method Not Allowed`: Wrong HTTP method used.
- `409 Conflict`: Resource already exists (e.g., email already registered).
- `500 Internal Server Error`: Unexpected server error.
