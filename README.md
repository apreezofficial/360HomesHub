# 360HomesHub 🏠

## Description
360HomesHub is a comprehensive real estate and short-let management ecosystem designed for modern property businesses. This project provides a robust backend infrastructure that handles the entire lifecycle of a property rental—from secure multi-channel user onboarding (Email, Phone, and Google OAuth) and mandatory KYC verification to dynamic pricing calculations and automated payment processing via Paystack and Flutterwave. The system includes a sophisticated administrative dashboard that gives operators full control over user verification, transaction monitoring, and real-time communication.

## Features
- **Multi-Channel Authentication**: Support for Email/Password, SMS-based registration, and Google OAuth.
- **Mandatory KYC System**: A tiered verification process for hosts and guests, including document and selfie uploads.
- **Dynamic Pricing Engine**: Automated calculation of rent, caution fees, taxes, and service charges.
- **Payment Gateway Integration**: Seamless checkout experiences using Paystack and Flutterwave with webhook support.
- **Real-Time Notifications**: Integrated messaging system and automated push/email notifications for booking updates.
- **Administrative Control**: Full-featured panel for property approval, KYC review, and financial reporting.

## Getting Started
### Installation
1. **Clone the Repository**:
   ```bash
   git clone https://github.com/apreezofficial/360HomesHub
   ```
2. **Install Dependencies**:
   ```bash
   composer install
   ```
3. **Database Configuration**:
   - Create a MySQL database named `360homesub`.
   - Import the project schema (if provided) or allow the system to initialize the connection.
4. **Environment Setup**:
   - Edit `config/env.php` to include your specific API keys and database credentials.
5. **Serve the Application**:
   - Move the folder to your `htdocs` or `www` directory.
   - Access the API via `http://localhost/360HomesHub/api/`.

### Environment Variables
The following constants must be defined in `config/env.php`:
- `DB_HOST`: Database host (e.g., localhost)
- `DB_NAME`: Database name (e.g., 360homesub)
- `DB_USER`: Database username
- `DB_PASS`: Database password
- `JWT_SECRET`: Secret key for token signing (Example: `Jfyeiotwyuqndtdghsjyg2diwhj`)
- `TWILIO_ACCOUNT_SID`: For SMS OTP services
- `RESEND_API_KEY`: For email delivery services
- `PAYSTACK_SECRET_KEY`: For Paystack transaction verification
- `FLUTTERWAVE_SECRET_KEY`: For Flutterwave transaction verification

# 360HomesHub API

## Overview
A high-performance RESTful API backend built with PHP 8.1, utilizing JWT for secure authentication and a custom-built utility architecture for database and file management.

## Features
- **Firebase JWT**: Stateless user authentication and authorization
- **PDO Singleton**: Optimized database connection management
- **Custom Upload Manager**: Secure handling of identity documents and property media
- **Geolocation Utility**: Haversine formula implementation for proximity-based property search

## API Documentation
### Base URL
`http://localhost/360HomesHub/api`

### Endpoints

#### POST /auth/register_email
**Request**:
```json
{
  "email": "user@example.com",
  "password": "StrongPassword123"
}
```
**Response**:
```json
{
  "success": true,
  "message": "Registration successful. OTP sent to your email for verification.",
  "data": { "user_id": 1 }
}
```
**Errors**:
- 400: Email and password are required
- 409: Email already registered

#### POST /auth/verify_otp
**Request**:
```json
{
  "user_id": 1,
  "otp_code": "123456"
}
```
**Response**:
```json
{
  "success": true,
  "message": "OTP verified successfully.",
  "data": { 
    "token": "JWT_TOKEN_HERE",
    "onboarding_step": "profile" 
  }
}
```
**Errors**:
- 400: Invalid or expired OTP

#### POST /properties/list
**Request**:
```json
{
  "latitude": 6.5244,
  "longitude": 3.3792,
  "page": 1
}
```
**Response**:
```json
{
  "success": true,
  "data": {
    "properties": [
      {
        "id": 1,
        "name": "Luxury Studio",
        "distance": 2.5,
        "price": 50000.00
      }
    ]
  }
}
```

#### POST /bookings/calculate
**Request**:
```json
{
  "property_id": 1,
  "check_in": "2024-12-01",
  "check_out": "2024-12-05",
  "adults": 2,
  "children": 1,
  "rooms": 1
}
```
**Response**:
```json
{
  "success": true,
  "data": {
    "booking_calculation": {
      "rent_amount": 200000.00,
      "caution_fee": 5000.00,
      "service_fee": 20000.00,
      "tax_amount": 15000.00,
      "total_amount": 240000.00
    }
  }
}
```

#### POST /admin/approve_kyc
**Request**:
```json
{
  "kyc_id": 1
}
```
**Response**:
```json
{
  "success": true,
  "message": "KYC application approved and user verified.",
  "data": { "kyc_id": 1, "user_id": 5 }
}
```
**Errors**:
- 403: Admin privileges required

---

## Complete API Reference

### Authentication Endpoints

#### POST /auth/register_email
Register a new user with email and password.
- **Auth**: None
- **Body**: `{ "email": "user@example.com", "password": "Pass123!" }`
- **Success**: `{ "success": true, "message": "Registration successful. OTP sent to your email for verification.", "data": { "user_id": 1 } }`

#### POST /auth/register_phone
Register with phone number.
- **Auth**: None  
- **Body**: `{ "phone": "+2349012345678", "password": "Pass123!" }`

#### POST /auth/verify_otp
Verify OTP sent to email/phone.
- **Auth**: None
- **Body**: `{ "user_id": 1, "otp_code": "123456" }`
- **Success**: Returns JWT token

#### POST /auth/login
Login with email/phone and password.
- **Auth**: None
- **Body**: `{ "email": "user@example.com", "password": "Pass123!" }`
- **Success**: Returns JWT token

---

### Onboarding Endpoints  

#### POST /onboarding/set_profile
Set user profile information.
- **Auth**: JWT required
- **Body**: `{ "first_name": "John", "last_name": "Doe", "bio": "..." }`

#### POST /onboarding/set_location
Set user location.
- **Auth**: JWT required
- **Body**: `{ "address": "...", "city": "Lagos", "state": "Lagos", "country": "Nigeria", "latitude": 6.5244, "longitude": 3.3792 }`

#### POST /onboarding/upload_avatar
Upload user avatar.
- **Auth**: JWT required
- **Body**: Multipart form with `avatar` file

#### POST /onboarding/set_role
Select user role (guest/host).
- **Auth**: JWT required
- **Body**: `{ "role": "host" }`

---

### KYC Endpoints

#### POST /kyc/start_kyc
Initiate KYC verification.
- **Auth**: JWT required
- **Body**: `{ "country": "Nigeria", "identity_type": "passport" }`

#### POST /kyc/upload_documents
Upload ID documents.
- **Auth**: JWT required
- **Body**: Multipart form with `id_front` and `id_back` files

#### POST /kyc/upload_selfie
Upload selfie for verification.
- **Auth**: JWT required
- **Body**: Multipart form with `selfie` file
- **Success**: Marks onboarding as completed

---

### Property Endpoints

#### GET /properties/list
List properties near a location.
- **Auth**: Optional JWT
- **Body**: `{ "latitude": 6.5244, "longitude": 3.3792, "page": 1 }`
- **Success**: Returns paginated list of properties with distance

#### GET /properties/amenities
Get list of all available amenities.
- **Auth**: None
- **Success**: `{ "success": true, "message": "Amenities retrieved.", "data": { "amenities": [{"id": 1, "name": "WiFi"}, ...] } }`

#### GET /properties/rules
Get house rules.
- **Auth**: None
- **Success**: `{ "success": true, "message": "House rules retrieved.", "data": { "house_rules": ["No smoking inside the property.", ...] } }`

---

### Booking Endpoints

#### POST /bookings/calculate
Calculate booking costs.
- **Auth**: JWT required
- **Body**: `{ "property_id": 1, "check_in": "2024-12-01", "check_out": "2024-12-05", "adults": 2, "children": 1, "rooms": 1 }`
- **Success**: Returns breakdown of rent, fees, taxes, and total amount

#### POST /bookings/create
Create a new booking request.
- **Auth**: JWT required (guest)
- **Body**: Same as `/bookings/calculate`
- **Success**: Creates booking with status "pending"

#### POST /bookings/approve
Approve a booking (host only).
- **Auth**: JWT required (host)
- **Body**: `{ "booking_id": 5 }`
- **Success**: Updates booking status to "approved"

#### POST /bookings/reject
Reject a booking (host only).
- **Auth**: JWT required (host)
- **Body**: `{ "booking_id": 5, "rejection_reason": "Property under maintenance" }`
- **Success**: Updates booking status to "rejected"

#### POST /bookings/checkout
Initiate payment for approved booking.
- **Auth**: JWT required (guest)
- **Body**: `{ "booking_id": 5 }`
- **Success**: Returns payment gateway checkout URL

#### POST /bookings/status
Get booking status.
- **Auth**: JWT required (guest or host)
- **Body**: `{ "booking_id": 5 }`
- **Success**: Returns booking details and current status

---

### Admin Endpoints

#### POST /admin/login
Admin login.
- **Auth**: None
- **Body**: `{ "email": "admin@360homeshub.com", "password": "AdminPass123!" }`
- **Success**: Returns JWT token (admin role required)

#### POST /admin/create_user
Create a new user (admin only).
- **Auth**: JWT required (admin)
- **Body**: `{ "email": "user@example.com", "password": "Pass123!", "first_name": "John", "last_name": "Doe", "role": "guest" }`

#### POST /admin/create_property
Create a property (admin only).
- **Auth**: JWT required (admin)
- **Body**: Property details including name, type, price, location, amenities

#### GET /admin/users
List all users (admin only).
- **Auth**: JWT required (admin)

#### GET /admin/properties
List all properties (admin only).
- **Auth**: JWT required (admin)

#### POST /admin/approve_kyc
Approve KYC application.
- **Auth**: JWT required (admin)
- **Body**: `{ "kyc_id": 1 }`

#### POST /admin/reject_kyc
Reject KYC application.
- **Auth**: JWT required (admin)
- **Body**: `{ "kyc_id": 1, "reason": "Documents unclear" }`

---

### Payment Webhook

#### POST /payments/webhook
Payment gateway webhook (Paystack/Flutterwave).
- **Auth**: Gateway signature verification
- **Body**: Payment event data from gateway
- **Success**: Updates booking status to "paid" and credits host wallet


## Technologies Used
| Technology | Purpose | Link |
| :--- | :--- | :--- |
| **PHP 8.1+** | Backend Logic | [php.net](https://www.php.net/) |
| **MySQL** | Database Management | [mysql.com](https://www.mysql.com/) |
| **Firebase JWT** | Security & Auth | [github.com/firebase/php-jwt](https://github.com/firebase/php-jwt) |
| **Paystack** | Payment Processing | [paystack.com](https://paystack.com/) |
| **Flutterwave** | Global Payments | [flutterwave.com](https://flutterwave.com/) |
| **Twilio** | SMS OTP Services | [twilio.com](https://www.twilio.com/) |

## Contributing
- 🚀 Fork the project repository.
- 🌿 Create a new feature branch: `git checkout -b feature/YourFeatureName`.
- 💾 Commit your changes: `git commit -m 'Add some feature'`.
- 📤 Push to the branch: `git push origin feature/YourFeatureName`.
- 🔍 Open a pull request for review.

## Author Info
**[Your Name]**
- GitHub: [apreezofficial](https://github.com/apreezofficial)
- LinkedIn: [Your Username]
- Twitter: [Your Username]

[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)](#)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-blue)](#)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](#)

[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)