## API Documentation

### Authentication
All authenticated endpoints require a JSON Web Token (JWT) to be passed in the `Authorization` header.

**Format**: `Authorization: Bearer <your_jwt_token>`

> **IMPORTANT**: The new Dashboard and Properties endpoints are location-aware. The frontend **MUST** provide the user's current `latitude` and `longitude` in the request body for these endpoints. The backend uses this to calculate distances and sort results in real-time. It does not use the user's saved profile location.

---

### Auth

#### `POST /api/auth/register_email.php`
Registers a new user with their email and password. Sends an OTP to the provided email for verification.

**Request Payload:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```
- `email` (string, required): The user's email address.
- `password` (string, required): The user's password (min 8 characters).

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Registration successful. OTP sent to your email for verification.",
  "data": {
    "user_id": 123
  }
}
```

#### `POST /api/auth/register_phone.php`
Registers a new user with their phone number and password. Sends an OTP to the provided phone number for verification.

**Request Payload:**
```json
{
  "phone": "+1234567890",
  "password": "password123"
}
```
- `phone` (string, required): The user's phone number in E.164 format.
- `password` (string, required): The user's password (min 8 characters).

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Registration successful. OTP sent to your phone for verification.",
  "data": {
    "user_id": 123
  }
}
```

#### `POST /api/auth/verify_otp.php`
Verifies the OTP sent to the user's email or phone. Returns a JWT to be used for subsequent authenticated requests.

**Request Payload:**
```json
{
  "user_id": 123,
  "otp_code": "123456"
}
```
- `user_id` (integer, required): The ID of the user being verified.
- `otp_code` (string, required): The OTP code sent to the user.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "OTP verified successfully.",
  "data": {
    "token": "ey...",
    "onboarding_step": "profile"
  }
}
```

#### `POST /api/auth/login.php`
Logs in a user with their email/phone and password. Returns a JWT.

**Request Payload:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```
or
```json
{
  "phone": "+1234567890",
  "password": "password123"
}
```
- `email` or `phone` (string, required): The user's email or phone.
- `password` (string, required): The user's password.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Login successful.",
  "data": {
    "token": "ey...",
    "onboarding_step": "completed",
    "is_verified": true,
    "role": "guest"
  }
}
```

#### `POST /api/auth/google_auth.php`
Authenticates a user with a Google ID token. Creates a new user if one doesn't exist. Returns a JWT.

**Request Payload:**
```json
{
  "id_token": "ey..."
}
```
- `id_token` (string, required): The Google ID token obtained from the frontend.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Google authentication successful.",
  "data": {
    "token": "ey...",
    "onboarding_step": "password",
    "is_verified": false,
    "role": null
  }
}
```

#### `POST /api/auth/set_password.php`
Allows a user to set or change their password. This is required for users who registered via Google.

**Request Payload:**
```json
{
  "password": "new_password123"
}
```
- `password` (string, required): The new password (min 8 characters).

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Password set successfully. Please complete your profile.",
  "data": {
    "token": "ey...",
    "onboarding_step": "profile"
  }
}
```

---

### Onboarding

The onboarding process is a multi-step workflow. Each step, when completed successfully, returns a new JWT that includes the next `onboarding_step` in its payload.

#### `POST /api/onboarding/set_profile.php`
Sets the user's first name, last name, and bio.

**Request Payload:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "bio": "Software developer from New York."
}
```
- `first_name`, `last_name` (string, required): The user's name.
- `bio` (string, optional): A short biography.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Profile updated successfully. Please set your location.",
  "data": {
    "token": "ey...",
    "onboarding_step": "location"
  }
}
```

#### `POST /api/onboarding/set_location.php`
Sets the user's geographical location.

**Request Payload:**
```json
{
  "address": "123 Main St",
  "city": "New York",
  "state": "NY",
  "country": "USA"
}
```
- `address`, `city`, `state`, `country` (string, required): The user's location details.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Location updated successfully. Please upload your avatar.",
  "data": {
    "token": "ey...",
    "onboarding_step": "avatar"
  }
}
```

#### `POST /api/onboarding/upload_avatar.php`
Uploads a user's avatar. The request must be `multipart/form-data`.

**Request Payload:**
- `avatar`: The avatar image file.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Avatar uploaded successfully. Please select your role.",
  "data": {
    "token": "ey...",
    "onboarding_step": "role",
    "avatar_url": "/public/uploads/..."
  }
}
```

#### `POST /api/onboarding/set_role.php`
Sets the user's role as either a `guest` or a `host`.

**Request Payload:**
```json
{
  "role": "guest"
}
```
- `role` (string, required): Must be `guest` or `host`.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Role selected successfully. Please proceed to KYC verification.",
  "data": {
    "token": "ey...",
    "onboarding_step": "kyc",
    "role": "guest"
  }
}
```

---

### KYC (Know Your Customer)

The KYC process is the final step of user verification.

#### `GET /api/kyc/kyc_status.php`
Retrieves the current status of the user's KYC application.

**Request Payload:** None.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "KYC application status retrieved successfully.",
  "data": {
    "kyc": {
      "id": 1,
      "country": "USA",
      "identity_type": "passport",
      "status": "pending",
      "admin_note": null,
      "submitted_at": "2026-01-18 12:00:00",
      "id_front_url": "/public/uploads/...",
      "id_back_url": "/public/uploads/...",
      "selfie_url": "/public/uploads/..."
    }
  }
}
```

#### `POST /api/kyc/start_kyc.php`
Initiates the KYC process by specifying the country and identity document type.

**Request Payload:**
```json
{
  "country": "USA",
  "identity_type": "passport"
}
```
- `country` (string, required): The user's country.
- `identity_type` (string, required): One of `passport`, `national_id`, `drivers_license`.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "KYC initiated. Please proceed to upload your identity documents.",
  "data": {
    "country": "USA",
    "identity_type": "passport"
  }
}
```

#### `POST /api/kyc/upload_documents.php`
Uploads the front and back of the user's identity document. The request must be `multipart/form-data`.

**Request Payload:**
- `id_front`: The image file of the front of the ID.
- `id_back`: The image file of the back of the ID.
- `country` (string, required): The user's country.
- `identity_type` (string, required): The type of ID.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Identity documents uploaded successfully. Please proceed to upload your selfie.",
  "data": {
    "id_front_url": "/public/uploads/...",
    "id_back_url": "/public/uploads/..."
  }
}
```

#### `POST /api/kyc/upload_selfie.php`
Uploads a selfie of the user for verification and completes the user's onboarding. The request must be `multipart/form-data`.

**Request Payload:**
- `selfie`: The selfie image file.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Selfie uploaded and KYC application submitted for review. You will be notified of the status.",
  "data": {
    "token": "ey...",
    "onboarding_step": "completed",
    "selfie_url": "/public/uploads/..."
  }
}
```

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
### Admin

Admin endpoints require an admin-level JWT.

#### `POST /api/admin/login.php`
Logs in an admin user.

**Request Payload:**
```json
{
  "email": "admin@example.com",
  "password": "adminpassword"
}
```
- `email` (string, required): The admin's email.
- `password` (string, required): The admin's password.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Admin login successful.",
  "data": {
    "token": "ey...",
    "role": "admin"
  }
}
```

#### `GET /api/admin/kyc_list.php`
Retrieves a list of all KYC applications. Can be filtered by status.

**Query Parameters:**
- `status` (string, optional): Filter by `pending`, `approved`, or `rejected`.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "KYC applications retrieved successfully.",
  "data": {
    "applications": [
      {
        "id": 1,
        "user_id": 123,
        "email": "user@example.com",
        "status": "pending",
        ...
      }
    ]
  }
}
```

#### `POST /api/admin/approve_kyc.php`
Approves a user's KYC application.

**Request Payload:**
```json
{
  "kyc_id": 1
}
```
- `kyc_id` (integer, required): The ID of the KYC application to approve.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "KYC application approved and user verified.",
  "data": {
    "kyc_id": 1,
    "user_id": 123
  }
}
```

#### `POST /api/admin/reject_kyc.php`
Rejects a user's KYC application.

**Request Payload:**
```json
{
  "kyc_id": 1,
  "admin_note": "ID photo is blurry."
}
```
- `kyc_id` (integer, required): The ID of the KYC application to reject.
- `admin_note` (string, optional): A reason for the rejection.

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "KYC application rejected.",
  "data": {
    "kyc_id": 1,
    "admin_note": "ID photo is blurry."
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