CREATE TABLE amenities (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE property_amenities (
  property_id BIGINT,
  amenity_id BIGINT
);

CREATE TABLE bookings (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT NOT NULL,
  guest_id BIGINT NOT NULL,
  host_id BIGINT NOT NULL,

  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  nights INT NOT NULL,

  adults INT DEFAULT 1,
  children INT DEFAULT 0,
  rooms INT DEFAULT 1,

  rent_amount DECIMAL(10,2),
  caution_fee DECIMAL(10,2),
  service_fee DECIMAL(10,2),
  tax_amount DECIMAL(10,2),
  total_amount DECIMAL(10,2),

  status ENUM('pending','approved','rejected','paid') DEFAULT 'pending',
  rejection_reason TEXT NULL,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT,
  title VARCHAR(255),
  message TEXT,
  level ENUM('important','normal','low'),
  is_read BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE properties ADD COLUMN house_rules TEXT, ADD COLUMN important_information TEXT, ADD COLUMN cancellation_policy BOOLEAN;