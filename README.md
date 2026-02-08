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

# 360HomesHub API Reference

The 360HomesHub API is a RESTful interface for managing real estate listings, bookings, user identity, and payments.

## Base URL
`http://localhost/360HomesHub/api`

## Authentication
Most endpoints require a JWT token in the `Authorization` header.
`Authorization: Bearer <your_token>`

---

## 1. Authentication Endpoints (`/api/auth/`)

| Endpoint | Method | Auth | Description | Parameters |
| :--- | :--- | :--- | :--- | :--- |
| `/register_email` | POST | None | Register with email. | `email`, `password` |
| `/register_phone` | POST | None | Register with phone number. | `phone`, `password` |
| `/verify_otp` | POST | None | Verify registration OTP. | `user_id`, `otp_code` |
| `/login` | POST | None | Login with credentials. | `email`/`phone`, `password` |
| `/set_password` | POST | None | Set/Reset user password. | `user_id`, `password` |
| `/google_auth` | POST | None | Google OAuth login. | `google_token` |

---

## 2. Onboarding Endpoints (`/api/onboarding/`)
*Requires JWT*

| Endpoint | Method | Description | Parameters |
| :--- | :--- | :--- | :--- |
| `/set_profile` | POST | Set basic profile info. | `first_name`, `last_name`, `bio` |
| `/set_location` | POST | Set user home location. | `address`, `city`, `state`, `latitude`, `longitude` |
| `/set_role` | POST | Define user as 'guest' or 'host'. | `role` |
| `/upload_avatar` | POST | Upload profile picture. | `avatar` (File) |

---

## 3. Property Endpoints (`/api/properties/`)

| Endpoint | Method | Auth | Description | Parameters |
| :--- | :--- | :--- | :--- | :--- |
| `/list` | POST | Opt | List properties by distance. | `latitude`, `longitude`, `page` |
| `/details` | GET | None | Full property details. | `id` (Query) |
| `/amenities` | GET | None | Get available amenities list. | - |
| `/calculate_price`| POST | None | Pricing for specific dates. | `property_id`, `check_in`, `check_out` |

### Host Property Onboarding (`/api/properties/onboarding/`)
*Requires JWT & 'host' role*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/init_listing` | POST | Start a new property listing. |
| `/update_listing` | POST | Update details (step-by-step). |
| `/pricing_preview`| POST | Calculate expected earnings. |

---

## 4. Booking Endpoints (`/api/bookings/`)
*Requires JWT*

| Endpoint | Method | Description | Parameters |
| :--- | :--- | :--- | :--- |
| `/create` | POST | Create a new booking request. | `property_id`, `check_in`, `check_out`, `adults`, `children`, `rooms` |
| `/calculate` | POST | Breakdown of costs & fees. | `property_id`, `check_in`, `check_out` |
| `/status` | GET | Current booking status. | `id` (Query) |
| `/approve` | POST | Approve (Host only). | `booking_id` |
| `/reject` | POST | Reject (Host only). | `booking_id`, `rejection_reason` |
| `/checkout` | POST | Get payment link. | `booking_id` |

---

## 5. KYC Endpoints (`/api/kyc/`)
*Requires JWT*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/start_kyc` | POST | Initiate verification. |
| `/upload_documents`| POST | Upload ID front/back. |
| `/upload_selfie` | POST | Final selfie verification. |
| `/kyc_status` | GET | Check verification progress.|

---

## 6. Communication & Notifications (`/api/messages/`, `/api/notifications/`)
*Requires JWT*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/messages/list` | GET | Get user conversations. |
| `/messages/view` | GET | View chat history. |
| `/messages/unread_count`| GET | Global unread chat count. |
| `/notifications/unread_count`| GET | Unread alerts count. |

---

## 7. Payment Endpoints (`/api/payments/`)

| Endpoint | Method | Auth | Description |
| :--- | :--- | :--- | :--- |
| `/checkout` | POST | JWT | Create transaction session. |
| `/verify` | POST | JWT | Manually verify transaction. |
| `/webhook` | POST | None | Gateway callback listener. |

---

## 8. Admin Endpoints (`/api/admin/`)
*Requires JWT & 'admin' role*

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/login` | POST | Admin login. |
| `/stats` | GET | Platform overview stats. |
| `/users` | GET | View all registered users. |
| `/properties` | GET | View all property listings. |
| `/kyc_list` | GET | List pending verifications. |
| `/approve_kyc` | POST | Verify a user account. |
| `/reject_kyc` | POST | Deny verification. |
| `/transactions` | GET | Platform-wide payments. |
| `/send_message` | POST | Official platform message. |
| `/send_notification`| POST | Push system alert. |
| `/update_property` | POST | Modify any listing. |
| `/property_details` | GET | Full admin view of property.|


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