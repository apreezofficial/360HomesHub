# 360-homeshub API 🏠

## Overview
A custom-built MVC PHP backend architecture designed for streamlined real estate operations and secure user management. This project implements a robust multi-step onboarding process, integrating JWT authentication and Google OAuth 2.0 to ensure a modern and secure user experience.

## Features
- **Stateless Authentication**: Implementation of JSON Web Tokens (JWT) for secure, scalable session management.
- **Social Integration**: Seamless Google OAuth 2.0 login for improved user conversion.
- **Multi-Step Onboarding**: A temporary user persistence layer that handles complex registration flows including personal details, address verification, and profile image uploads.
- **Security First**: Prepared PDO statements to prevent SQL injection and password hashing using BCRYPT.
- **OTP Verification**: Time-sensitive One-Time Password system for email and phone validation.

## Getting Started
### Installation
1. Clone the repository to your local server.
2. Run `composer install` to pull dependencies.
3. Import the database schema into your MySQL instance.
4. Configure the virtual host to point to the project root.

### Environment Variables
Configure these constants in `config/config.php`:
- `DB_HOST`: Database host (e.g., localhost)
- `DB_USER`: Database username
- `DB_PASS`: Database password
- `DB_NAME`: Database name
- `URLROOT`: Base URL (e.g., http://localhost/360-homeshub)
- `JWT_SECRET`: A secure random string for signing tokens
- `GOOGLE_CLIENT_ID`: Your Google Developer Console Client ID
- `GOOGLE_CLIENT_SECRET`: Your Google Developer Console Client Secret
- `GOOGLE_REDIRECT_URI`: OAuth callback URL
- `RESEND_API_KEY`: Your Resend API Key

## API Documentation
### Base URL
`http://localhost/360-homeshub/api`

### Endpoints

#### GET /test
A simple test endpoint to check API connectivity.
**Response**:
```json
{
  "message": "API GET request is working!"
}
```
**Errors**:
- 404: API endpoint not found

#### POST /test
A simple test endpoint for POST requests.
**Request**:
```json
{
  "key": "value"
}
```
**Response**:
```json
{
  "message": "API POST request is working!",
  "data": {"key": "value"}
}
```
**Errors**:
- 404: API endpoint not found

#### POST /auth/register
Initiates the registration process by validating the email/phone and sending an OTP via Resend.
**Request**:
```json
{
  "email": "user@example.com",
  "phone": "08012345678"
}
```
**Response**:
```json
{
  "message": "OTP sent successfully"
}
```
**Errors**:
- 400: Email or phone already taken
- 405: Invalid request method
- 500: Failed to send OTP email

#### POST /auth/verifyOtp
Validates the 6-digit code sent to the user.
**Request**:
```json
{
  "email": "user@example.com",
  "otp": "123456"
}
```
**Response**:
```json
{
  "message": "OTP verified successfully"
}
```
**Errors**:
- 400: Invalid or expired OTP

#### POST /auth/createPassword
Sets the user password and creates a temporary record.
**Request**:
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123"
}
```
**Response**:
```json
{
  "message": "Password created successfully"
}
```
**Errors**:
- 400: Password length requirements not met

#### POST /auth/collectPersonalDetails
Updates the temporary profile with name and bio.
**Request**:
```json
{
  "email": "user@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "bio": "Real estate enthusiast"
}
```
**Response**:
```json
{
  "message": "Personal details saved successfully"
}
```

#### POST /auth/collectAddress
Saves user location data to the temporary record.
**Request**:
```json
{
  "email": "user@example.com",
  "address": "123 Street Ave",
  "city": "Lagos",
  "state": "Lagos",
  "country": "Nigeria"
}
```
**Response**:
```json
{
  "message": "Address saved successfully"
}
```

#### POST /auth/uploadProfilePhoto
Handles multipart/form-data for profile image uploads.
**Request**:
- Key: `photo` (File)
- Key: `email` (String)

**Response**:
```json
{
  "message": "Profile photo uploaded successfully"
}
```

#### POST /auth/selectRole
Finalizes onboarding by selecting a role and moving data to the main users table.
**Request**:
```json
{
  "email": "user@example.com",
  "role": "agent"
}
```
**Response**:
```json
{
  "message": "User registered successfully"
}
```

#### GET /auth/googleLogin
Redirects the client to the Google OAuth consent screen.
**Response**:
- 302 Redirect to Google accounts.

## Usage
The API follows a strict sequential onboarding flow. Clients should first call the register endpoint, verify the OTP, and then proceed through the collection endpoints (Details -> Address -> Photo -> Role) to complete the account creation. Authentication is handled via Bearer tokens provided after successful Google OAuth or final registration.

## Technologies Used
| Technology | Purpose | Link |
| :--- | :--- | :--- |
| PHP | Core Language | [php.net](https://www.php.net/) |
| MySQL | Database Engine | [mysql.com](https://www.mysql.com/) |
| Firebase JWT | Token Authentication | [github.com/firebase/php-jwt](https://github.com/firebase/php-jwt) |
| Google API Client | OAuth 2.0 Services | [github.com/google/google-api-php-client](https://github.com/google/google-api-php-client) |
| Resend | Email API | [resend.com](https://resend.com/) |
| Composer | Dependency Management | [getcomposer.org](https://getcomposer.org/) |

## Author Info
Developed with a focus on scalable architecture and clean code.

- **Developer**: [Your Name]
- **LinkedIn**: [Your LinkedIn Profile]
- **Twitter**: [Your Twitter Profile]
- **Portfolio**: [Your Portfolio Link]

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-black?style=for-the-badge&logo=JSON%20web%20tokens)
![Google Cloud](https://img.shields.io/badge/Google_Cloud-4285F4?style=for-the-badge&logo=google-cloud&logoColor=white)

[![Readme was generated by Dokugen](https://img.shields.io/badge/Readme%20was%20generated%20by-Dokugen-brightgreen)](https://www.npmjs.com/package/dokugen)