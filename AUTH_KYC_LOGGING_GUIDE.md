# Activity Logging for Auth & KYC - Implementation Guide

## Files to Update

Due to the number of files, I'll provide the key changes needed for each file:

### 1. api/auth/register_email.php

Add after line 6:
```php
require_once __DIR__ . '/../../utils/activity_logger.php';
```

Replace the success block (around line 48):
```php
$pdo->commit();

// Log successful registration
ActivityLogger::logUser(
    $userId,
    'registered_email',
    [
        'email' => $email,
        'auth_provider' => 'email'
    ]
);

send_success('Registration successful. OTP sent to your email for verification.', ['user_id' => $userId]);
```

Add to catch block (around line 52):
```php
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Email registration error: " . $e->getMessage());
    
    ActivityLogger::log(
        null,
        'registration_failed',
        "Email registration failed",
        'user',
        null,
        ['email' => $email, 'error' => $e->getMessage()]
    );
    
    send_error('Registration failed: ' . $e->getMessage(), [], 500);
}
```

### 2. api/auth/login.php

Add after line 2:
```php
require_once __DIR__ . '/../../utils/activity_logger.php';
```

After successful password verification (around line 33):
```php
if (!$user || !password_verify($password, $user['password_hash'])) {
    // Log failed login attempt
    ActivityLogger::log(
        null,
        'login_failed',
        "Failed login attempt",
        'user',
        null,
        ['email' => $email, 'phone' => $phone, 'reason' => 'invalid_credentials']
    );
    send_error('Invalid credentials.', [], 401);
}
```

Before send_success (around line 63):
```php
// Log successful login
ActivityLogger::logUser(
    $user['id'],
    'logged_in',
    [
        'email' => $user['email'],
        'phone' => $user['phone'],
        'auth_provider' => $user['auth_provider']
    ]
);

send_success('Login successful.', [
    'token' => $token, 
    'onboarding_step' => $user['onboarding_step'], 
    'status' => $user['status'],
    'message_disabled' => (bool)$user['message_disabled'],
    'booking_disabled' => (bool)$user['booking_disabled'],
    'role' => $user['role']
]);
```

### 3. api/auth/verify_otp.php

Add activity logger require and log successful OTP verification:
```php
// After successful OTP verification
ActivityLogger::logUser(
    $userId,
    'otp_verified',
    ['verification_method' => 'otp']
);
```

### 4. api/kyc/start_kyc.php

Add after line 7:
```php
require_once __DIR__ . '/../../utils/activity_logger.php';
```

Before send_success (around line 60):
```php
// Log KYC initiation
ActivityLogger::log(
    $userId,
    'kyc_initiated',
    "KYC process initiated",
    'kyc',
    null,
    ['country' => $country, 'identity_type' => $identityType]
);

send_success('KYC initiated. Please proceed to upload your identity documents.', [
    'country' => $country,
    'identity_type' => $identityType
]);
```

### 5. api/kyc/upload_documents.php

Add logging for document uploads:
```php
// After successful upload
ActivityLogger::log(
    $userId,
    'kyc_documents_uploaded',
    "KYC identity documents uploaded",
    'kyc',
    $kycId,
    ['document_type' => $identityType, 'country' => $country]
);
```

### 6. api/kyc/upload_selfie.php

Add logging for selfie upload:
```php
// After successful upload
ActivityLogger::log(
    $userId,
    'kyc_selfie_uploaded',
    "KYC selfie uploaded",
    'kyc',
    $kycId
);
```

### 7. api/admin/approve_kyc.php

Add logging for KYC approval:
```php
// After approval
ActivityLogger::log(
    $adminId,
    'kyc_approved',
    "KYC approved by admin",
    'kyc',
    $kycId,
    ['user_id' => $kycUserId]
);
```

### 8. api/admin/reject_kyc.php

Add logging for KYC rejection:
```php
// After rejection
ActivityLogger::log(
    $adminId,
    'kyc_rejected',
    "KYC rejected by admin",
    'kyc',
    $kycId,
    ['user_id' => $kycUserId, 'reason' => $adminNote]
);
```

## Summary of Activity Types Being Logged

### Authentication
- `user_registered_email` - Email registration
- `user_registered_phone` - Phone registration
- `user_logged_in` - Successful login
- `login_failed` - Failed login attempt
- `user_otp_verified` - OTP verification
- `registration_failed` - Registration failure

### KYC
- `kyc_initiated` - KYC process started
- `kyc_documents_uploaded` - Identity documents uploaded
- `kyc_selfie_uploaded` - Selfie uploaded
- `kyc_approved` - KYC approved by admin
- `kyc_rejected` - KYC rejected by admin

## Quick Implementation Script

To implement all these changes quickly, you can:

1. Add `require_once __DIR__ . '/../../utils/activity_logger.php';` to all auth and KYC files
2. Add the appropriate `ActivityLogger::log()` or `ActivityLogger::logUser()` calls at key points
3. Test each endpoint to ensure logging works

## Testing

After implementation, test with:
```sql
-- View all auth activities
SELECT * FROM activity_logs 
WHERE action_type LIKE '%login%' 
   OR action_type LIKE '%register%' 
   OR action_type LIKE '%otp%'
ORDER BY created_at DESC;

-- View all KYC activities
SELECT * FROM activity_logs 
WHERE action_type LIKE '%kyc%'
ORDER BY created_at DESC;
```
