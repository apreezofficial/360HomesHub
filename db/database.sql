CREATE TABLE users (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,

  email VARCHAR(255) UNIQUE NULL,
  phone VARCHAR(20) UNIQUE NULL,
  google_id VARCHAR(255) UNIQUE NULL,

  password_hash VARCHAR(255) NOT NULL,

  first_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  bio TEXT NULL,

  address TEXT NULL,
  city VARCHAR(100) NULL,
  state VARCHAR(100) NULL,
  country VARCHAR(100) NULL,

  avatar VARCHAR(255) NULL,

  role ENUM('guest','host','admin') NULL, -- Added 'admin' role

  auth_provider ENUM('email','phone','google') NOT NULL,

  is_verified BOOLEAN DEFAULT 0,

  onboarding_step ENUM(
    'otp',
    'password',
    'profile',
    'location',
    'avatar',
    'role',
    'kyc',
    'completed'
  ) DEFAULT 'otp',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kyc (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,

  country VARCHAR(100) NOT NULL,
  identity_type ENUM('passport','national_id','drivers_license') NOT NULL,

  id_front VARCHAR(255) NOT NULL,
  id_back VARCHAR(255) NOT NULL,
  selfie VARCHAR(255) NOT NULL,

  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  admin_note TEXT NULL,

  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE otps (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  code VARCHAR(6) NOT NULL,
  expires_at DATETIME NOT NULL,
  used BOOLEAN DEFAULT 0,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);