# 360HomeSub API

## Overview
A high-performance real estate and property management backend built with PHP 8.1. It features a robust multi-step onboarding process, JWT-based authentication, and a complete KYC (Know Your Customer) verification pipeline for secure user interactions.

## Features
- **JWT Authentication**: Secure stateless authentication using industry-standard JSON Web Tokens.
- **Multi-Channel OTP**: Dual-factor verification support via Twilio (SMS) and Resend (Email).
- **Social Auth**: Native integration with Google OAuth 2.0 for seamless user registration.
- **Onboarding Workflow**: Managed state transitions for profiles, locations, avatars, and roles.
- **KYC Pipeline**: Complete document management system including ID upload and selfie verification with an administrative review interface.
- **Geospatial Logic**: Real-time distance calculations for property discovery based on user coordinates.
- **Admin Dashboard**: Specialized endpoints for managing user verification and property oversight.

## Getting Started
### Installation
1. Clone the repository to your local server environment.
2. Install dependencies via Composer:
   ```bash
   composer install
   ```
3. Configure your web server (Apache/Nginx) to point to the project root.
4. Import the database schema into your MySQL instance.
5. Create a logs directory and ensure it is writeable:
   ```bash
   mkdir public/logs && chmod 777 public/logs
   ```

### Environment Variables
Configure these constants within `config/env.php`:

| Variable | Example Value | Description |
|----------|---------------|-------------|
| `DB_HOST` | `localhost` | Database host address |
| `DB_NAME` | `360homesub` | Name of the database |
| `DB_USER` | `root` | Database username |
| `DB_PASS` | `password` | Database password |
| `JWT_SECRET` | `your_random_string` | Secret key for token signing |
| `TWILIO_ACCOUNT_SID` | `ACxxx...` | Twilio Account SID |
| `TWILIO_AUTH_TOKEN` | `auth_xxx...` | Twilio Auth Token |
| `RESEND_API_KEY` | `re_xxx...` | Resend API key for emails |
| `GOOGLE_CLIENT_ID` | `xxx.apps.googleusercontent.com` | Google OAuth Client ID |

## API Documentation
### Base URL
`http://yourdomain.com/api`

### Endpoints

#### POST /auth/register_email.php
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
- 400: Invalid email or password format
- 409: Email already registered

#### POST /auth/verify_otp.php
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
    "token": "eyJ0eXAi...",
    "onboarding_step": "password"
  }
}
```
**Errors**:
- 400: Invalid or expired OTP
- 404: User not found

#### POST /auth/google_auth.php
**Request**:
```json
{
  "id_token": "google_id_token_string"
}
```
**Response**:
```json
{
  "success": true,
  "message": "Google authentication successful.",
  "data": {
    "token": "eyJ0eXAi...",
    "onboarding_step": "profile",
    "is_verified": false
  }
}
```

#### POST /onboarding/set_profile.php
**Request**:
(Header: Authorization: Bearer {token})
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "bio": "Real estate enthusiast"
}
```
**Response**:
```json
{
  "success": true,
  "message": "Profile updated successfully.",
  "data": { "token": "new_token", "onboarding_step": "location" }
}
```

#### POST /kyc/upload_documents.php
**Request**:
(Multipart/form-data)
- `country`: "United States"
- `identity_type`: "passport"
- `id_front`: [File]
- `id_back`: [File]

**Response**:
```json
{
  "success": true,
  "message": "Identity documents uploaded successfully.",
  "data": {
    "id_front_url": "/public/uploads/64f1...png",
    "id_back_url": "/public/uploads/64f2...png"
  }
}
```

#### POST /properties/list.php
**Request**:
```json
{
  "latitude": 40.7128,
  "longitude": -74.0060,
  "page": 1
}
```
**Response**:
```json
{
  "success": true,
  "data": {
    "pagination": { "current_page": 1, "total_pages": 5 },
    "properties": [
      {
        "id": 10,
        "name": "Luxury Suite",
        "distance": 1.2,
        "price": 250.00
      }
    ]
  }
}
```

#### GET /admin/kyc_list.php
**Request**:
(Header: Authorization: Bearer {admin_token})
Query Param: `?status=pending`

**Response**:
```json
{
  "success": true,
  "data": {
    "applications": [
      {
        "id": 1,
        "email": "user@example.com",
        "status": "pending",
        "submitted_at": "2023-10-01 12:00:00"
      }
    ]
  }
}
```
**Errors**:
- 403: Access denied. Admin privileges required.

## Usage
The API follows a strict onboarding sequence. After initial registration, users must verify their identity (OTP), set a password (if via email/phone), complete their profile details, and finally undergo KYC verification before being granted full access to host or book properties. All authenticated requests must include the JWT in the `Authorization` header as a `Bearer` token.

## Technologies Used
| Technology | Purpose | Link |
|------------|---------|------|
| PHP 8.1 | Core Language | [php.net](https://www.php.net/) |
| MySQL | Database | [mysql.com](https://www.mysql.com/) |
| Firebase JWT | Authentication | [github.com](https://github.com/firebase/php-jwt) |
| Twilio | SMS OTP | [twilio.com](https://www.twilio.com/) |
| Resend | Email Services | [resend.com](https://resend.com/) |
| Google Client | Social Auth | [cloud.google.com](https://cloud.google.com/) |

## Author Info
**Project Lead**
- GitHub: [github.com/yourusername]
- LinkedIn: [linkedin.com/in/yourusername]
- Website: [yourportfolio.com]

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-black?style=for-the-badge&logo=JSON%20web%20tokens)
![Twilio](https://img.shields.io/badge/Twilio-F22F46?style=for-the-badge&logo=Twilio&logoColor=white)

[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)