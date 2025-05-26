CREATE DATABASE hkid_appointment;
USE hkid_appointment;

-- Users table with role-based access and TOTP secret for MFA
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('junior', 'approving', 'admin') NOT NULL,
    totp_secret VARCHAR(32) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- create the appointments table with new fields
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    english_name VARCHAR(100) NOT NULL,
    chinese_name VARCHAR(100) NOT NULL,
    gender ENUM('M', 'F', 'Other') NOT NULL,
    date_of_birth DATE NOT NULL,
    address TEXT NOT NULL,
    place_of_birth VARCHAR(100) NOT NULL,
    occupation VARCHAR(100) NOT NULL,
    hkid VARCHAR(10) NOT NULL,
    purpose ENUM('application', 'replacement') NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    venue_id INT NOT NULL,
    email VARCHAR(255) NOT NULL, -- Added email column
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id) -- Added foreign key for venue_id
);

-- Create a venues table for available venues
CREATE TABLE venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL
);

-- Insert sample venues (you can add more as needed)
INSERT INTO venues (name) VALUES 
('Immigration Tower (Wan Chai)'),
('Kwun Tong Immigration Office'),
('Sha Tin Immigration Office'),
('Fo Tan Immigration Office'),
('Tuen Mun Immigration Office');