# 360HomeHub API

## Overview
A high-performance backend infrastructure built with PHP 8.1+ providing secure user management, multi-step onboarding, and a rigorous KYC verification pipeline. This system leverages JWT for stateless authentication and integrates third-party services like Twilio, Resend, and Google OAuth for a seamless user experience.

## Features
- **Multi-Channel Authentication**: Support for Email, Phone, and Google OAuth 2.0.
- **Security**: JWT-based session management and Bcrypt password hashing.
- **Two-Factor Verification**: OTP delivery via Twilio (SMS) and Resend (Email).
- **Onboarding Workflow**: Sequential data collection including profile, location, and role selection.
- **KYC Engine**: Secure document upload handling (Identity cards and selfies) with an admin review interface.
- **Admin Management**: Dedicated administrative endpoints to monitor, approve, or reject user verifications.

## Getting Started
### Installation
1. **Clone the Repository**
   ```bash
   git clone https://github.com/apreezofficial/360HomesHub
   cd 360HomesHub
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Database Configuration**
   - Create a MySQL database named `360homesub`.
   - Import your schema (tables for `users`, `otps`, and `kyc`).
   - Configure the connection in `config/env.php`.

4. **Directory Permissions**
   Ensure the upload directory is writable:
   ```bash
   chmod -R 775 public/uploads
   ```

### Environment Variables
Configure these constants within `config/env.php`:

| Variable | Example Value | Description |
|----------|---------------|-------------|
| `DB_HOST` | `localhost` | Database host |
| `DB_NAME` | `360homesub` | Database name |
| `DB_USER` | `root` | Database username |
| `DB_PASS` | `your_password` | Database password |
| `JWT_SECRET` | `shhh_secret_key` | Secret key for token signing |
| `TWILIO_ACCOUNT_SID` | `ACxxx...` | Twilio Account SID |
| `TWILIO_AUTH_TOKEN` | `auth_xxx...` | Twilio Auth Token |
| `RESEND_API_KEY` | `re_xxx...` | Resend API Key |
| `GOOGLE_CLIENT_ID` | `google_id...` | Google OAuth Client ID |

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
  "message": "Registration successful. OTP sent to your email.",
  "data": { "user_id": 1 }
}
```
**Errors**:
- 400: Email and password required
- 409: Email already registered

#### POST /auth/register_phone.php
**Request**:
```json
{
  "phone": "+1234567890",
  "password": "StrongPassword123"
}
```
**Response**:
```json
{
  "success": true,
  "message": "Registration successful. OTP sent to phone.",
  "data": { "user_id": 2 }
}
```
**Errors**:
- 400: Invalid phone format
- 409: Phone already registered

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
  "data": { "token": "jwt_token_string", "onboarding_step": "password" }
}
```
**Errors**:
- 400: Invalid or expired OTP

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
  "data": { "token": "jwt_token", "onboarding_step": "profile" }
}
```
**Errors**:
- 401: Invalid Google ID token

#### POST /auth/login.php
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
  "message": "Login successful.",
  "data": { "token": "jwt_token", "is_verified": false }
}
```
**Errors**:
- 401: Invalid credentials
- 403: OTP verification required

#### POST /auth/set_password.php
**Request** (Header: `Authorization: Bearer <token>`):
```json
{
  "password": "NewSecurePassword123"
}
```
**Response**:
```json
{
  "success": true,
  "message": "Password set successfully.",
  "data": { "onboarding_step": "profile" }
}
```

#### POST /onboarding/set_profile.php
**Request** (Header: `Authorization: Bearer <token>`):
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "bio": "Software Engineer"
}
```
**Response**:
```json
{
  "success": true,
  "message": "Profile updated successfully.",
  "data": { "onboarding_step": "location" }
}
```

#### POST /onboarding/set_location.php
**Request**:
```json
{
  "address": "123 Main St",
  "city": "Lagos",
  "state": "Lagos",
  "country": "Nigeria"
}
```
**Response**:
```json
{
  "success": true,
  "message": "Location updated.",
  "data": { "onboarding_step": "avatar" }
}
```

#### POST /onboarding/upload_avatar.php
**Request** (Multipart/Form-Data):
- `avatar`: [File Binary]

**Response**:
```json
{
  "success": true,
  "message": "Avatar uploaded.",
  "data": { "avatar_url": "/public/uploads/unique_id.png" }
}
```

#### POST /onboarding/set_role.php
**Request**:
```json
{
  "role": "host"
}
```
**Response**:
```json
{
  "success": true,
  "message": "Role selected.",
  "data": { "onboarding_step": "kyc" }
}
```

#### GET /kyc/kyc_status.php
**Response**:
```json
{
  "success": true,
  "data": { "kyc": { "status": "pending", "submitted_at": "..." } }
}
```

#### POST /kyc/start_kyc.php
**Request**:
```json
{
  "country": "Nigeria",
  "identity_type": "national_id"
}
```
**Response**:
```json
{
  "success": true,
  "message": "KYC initiated."
}
```

#### POST /kyc/upload_documents.php
**Request** (Multipart/Form-Data):
- `id_front`: [File]
- `id_back`: [File]
- `country`: "Nigeria"
- `identity_type`: "passport"

**Response**:
```json
{
  "success": true,
  "message": "Documents uploaded."
}
```

#### POST /kyc/upload_selfie.php
**Request** (Multipart/Form-Data):
- `selfie`: [File]

**Response**:
```json
{
  "success": true,
  "message": "Selfie uploaded and KYC submitted."
}
```

#### POST /admin/login.php
**Request**:
```json
{
  "email": "admin@360home.com",
  "password": "AdminPassword"
}
```
**Response**:
```json
{
  "success": true,
  "data": { "token": "admin_jwt_token", "role": "admin" }
}
```

#### GET /admin/kyc_list.php
**Query Params**: `status` (optional: pending, approved, rejected)
**Response**:
```json
{
  "success": true,
  "data": { "applications": [...] }
}
```

#### POST /admin/approve_kyc.php
**Request**:
```json
{
  "kyc_id": 10
}
```
**Response**:
```json
{
  "success": true,
  "message": "KYC application approved."
}
```

#### POST /admin/reject_kyc.php
**Request**:
```json
{
  "kyc_id": 10,
  "admin_note": "ID card is blurred"
}
```
**Response**:
```json
{
  "success": true,
  "message": "KYC application rejected."
}
```

## Technologies Used
| Technology | Purpose |
|------------|---------|
| [PHP 8.1+](https://php.net) | Core Logic |
| [Firebase JWT](https://github.com/firebase/php-jwt) | Authentication |
| [Twilio SDK](https://www.twilio.com) | SMS OTP |
| [Resend](https://resend.com) | Email OTP |
| [Google API Client](https://github.com/googleapis/google-api-php-client) | Social Auth |
| [MySQL](https://mysql.com) | Database Management |

## Author
**[Your Name]**
- GitHub: [apreezofficial](https://github.com/apreezofficial)
- LinkedIn: [Your Profile]
- Twitter: [Your Handle]

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-black?style=for-the-badge&logo=JSON%20web%20tokens)

[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)