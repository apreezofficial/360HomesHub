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
4. Import the database schema (`db/database.sql`) and seed data (`db/seed.sql`) into your MySQL instance.
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
| `DB_USER` | `root` | Database password |
| `DB_PASS` | `password` | Database password |
| `JWT_SECRET` | `your_random_string` | Secret key for token signing |
| `TWILIO_ACCOUNT_SID` | `ACxxx...` | Twilio Account SID |
| `TWILIO_AUTH_TOKEN` | `auth_xxx...` | Twilio Auth Token |
| `RESEND_API_KEY` | `re_xxx...` | Resend API key for emails |
| `GOOGLE_CLIENT_ID` | `xxx.apps.googleusercontent.com` | Google OAuth Client ID |

---

## API Documentation

### Authentication
All authenticated endpoints require a JSON Web Token (JWT) to be passed in the `Authorization` header.

**Format**: `Authorization: Bearer <your_jwt_token>`

> **IMPORTANT**: The new Dashboard and Properties endpoints are location-aware. The frontend **MUST** provide the user's current `latitude` and `longitude` in the request body for these endpoints. The backend uses this to calculate distances and sort results in real-time. It does not use the user's saved profile location.

---

### Dashboard & Counts

#### `GET /api/dashboard/home.php`
Retrieves all necessary data for the main dashboard view, including a welcome message, unread counts, and a list of nearby properties.

**Request Payload:**
```json
{
  "latitude": 40.7128,
  "longitude": -74.0060
}
```
- `latitude` (float, required): The user's current latitude.
- `longitude` (float, required): The user's current longitude.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "welcome_message": "Welcome back, John!",
    "unread_notifications": 5,
    "unread_messages": 3,
    "property_categories": {
      "apartment": 50,
      "house": 30,
      "studio": 20,
      "duplex": 10,
      "hotel": 5
    },
    "nearby_properties": [
      {
        "id": 15,
        "name": "Cozy Studio Near Park",
        "image": "http://example.com/uploads/studio_main.jpg",
        "distance": 0.5,
        "price": 120.00,
        "price_type": "night",
        "city": "New York",
        "state": "NY"
      }
    ]
  }
}
```

#### `GET /api/messages/unread_count.php`
Returns the count of unread messages for the authenticated user.

**Request Payload:** None.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "unread_count": 3
  }
}
```

#### `GET /api/notifications/unread_count.php`
Returns the count of unread notifications for the authenticated user.

**Request Payload:** None.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "unread_count": 5
  }
}
```

---

### Properties

#### `POST /api/properties/list.php`
Fetches a paginated list of all available properties, sorted by the nearest distance to the user's current location.

**Request Payload:**
```json
{
  "latitude": 40.7128,
  "longitude": -74.0060,
  "page": 1
}
```
- `latitude` (float, required): The user's current latitude.
- `longitude` (float, required): The user's current longitude.
- `page` (integer, optional, default: 1): The page number for pagination.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "pagination": {
      "current_page": 1,
      "total_pages": 10,
      "total_results": 100
    },
    "properties": [
      {
        "id": 15,
        "name": "Cozy Studio Near Park",
        "image": "http://example.com/uploads/studio_main.jpg",
        "distance": 0.5,
        "price": 120.00,
        "price_type": "night",
        "city": "New York",
        "state": "NY"
      }
    ]
  }
}
```

#### `POST /api/properties/view.php`
Retrieves full details for a single property, including all its images, host information, and its distance from the user.

**Request Payload:**
```json
{
  "property_id": 12,
  "latitude": 40.7128,
  "longitude": -74.0060
}
```
- `property_id` (integer, required): The ID of the property to view.
- `latitude` (float, required): The user's current latitude.
- `longitude` (float, required): The user's current longitude.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "property": {
      "id": 12,
      "name": "Luxury Downtown Apartment",
      "description": "A beautiful apartment in the heart of the city.",
      "type": "apartment",
      "price": 250.00,
      "price_type": "night",
      "bedrooms": 2,
      "bathrooms": 2,
      "area": 1200,
      "booking_type": "instant",
      "free_cancellation": true,
      "amenities": ["wifi", "pool", "gym"],
      "city": "New York",
      "state": "NY",
      "distance": 0.1,
      "images": [
        "http://example.com/uploads/image1.jpg",
        "http://example.com/uploads/image2.jpg"
      ],
      "host": {
        "id": 5,
        "first_name": "John",
        "last_name": "Doe",
        "avatar": "http://example.com/avatars/johndoe.jpg"
      }
    }
  }
}
```

#### `POST /api/properties/search.php`
Performs a powerful search for properties based on a combination of filters. All results are sorted by distance.

**Request Payload:**
> All filter fields are optional.

```json
{
  "latitude": 40.7128,
  "longitude": -74.0060,
  "keyword": "beachfront",
  "type": "apartment",
  "price_min": 100,
  "price_max": 500,
  "bedrooms": 2,
  "bathrooms": 1,
  "booking_type": "instant",
  "free_cancellation": true
}
```
- `latitude`, `longitude` (float, required): User's current location.
- `keyword` (string): Searches `name`, `city`, and `state` fields.
- `type` (string): One of `apartment`, `house`, `studio`, `duplex`, `hotel`.
- `price_min`, `price_max` (integer): Price range for filtering.
- `bedrooms`, `bathrooms` (integer): Minimum number of bedrooms/bathrooms.
- `booking_type` (string): `instant` or `request`.
- `free_cancellation` (boolean): `true` to only show properties with free cancellation.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "results_count": 1,
    "applied_filters": {
      "keyword": "beachfront",
      "type": "apartment",
      "price_min": 100,
      "price_max": 500
    },
    "properties": [
      {
        "id": 25,
        "name": "Modern Beachfront Apartment",
        "image": "http://example.com/uploads/beach_apt.jpg",
        "distance": 15.3,
        "price": 450.00,
        "price_type": "night",
        "city": "Miami",
        "state": "FL"
      }
    ]
  }
}
```

---
### Common Error Response

If a request fails due to invalid input, authentication issues, or server errors, the API will return a standardized error response.

**Example Error (400 Bad Request):**
```json
{
  "status": "error",
  "message": "Missing required fields: latitude, longitude."
}
```

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
