-- Create the database
CREATE DATABASE IF NOT EXISTS fast_event_management;
USE fast_event_management;

-- Roles table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    student_id VARCHAR(50) NULL,
    phone VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Event Categories table
CREATE TABLE event_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL
);

-- Events table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category_id INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    registration_deadline DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    max_participants INT NULL,
    organizer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_published BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (category_id) REFERENCES event_categories(id),
    FOREIGN KEY (organizer_id) REFERENCES users(id)
);

-- Event Registration table
CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'attended') DEFAULT 'pending',
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_registration (event_id, user_id)
);

-- Certificates table
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    certificate_url VARCHAR(255) NULL,
    issued_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES event_registrations(id)
);

-- Add Food Stall related tables

-- Table for food stalls
CREATE TABLE IF NOT EXISTS `food_stalls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `location` varchar(255) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `availability_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `food_stalls_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for stall bookings
CREATE TABLE IF NOT EXISTS `stall_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stall_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stall_id` (`stall_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `stall_bookings_ibfk_1` FOREIGN KEY (`stall_id`) REFERENCES `food_stalls` (`id`),
  CONSTRAINT `stall_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles
INSERT INTO roles (name) VALUES 
('Admin'),
('Organizer'),
('Student'),
('Outsider');

-- Insert default event categories
INSERT INTO event_categories (name, description) VALUES 
('Hackathon', 'Coding competitions and technology challenges'),
('Concert', 'Music and entertainment events'),
('Sports', 'Athletic competitions and sporting events'),
('Workshop', 'Educational and training sessions'),
('Seminar', 'Lectures and knowledge-sharing events');

-- Insert admin user (password: admin123)
INSERT INTO users (email, password, first_name, last_name, role_id) 
VALUES ('admin@fast.edu.pk', '$2y$10$jhEkHziC5ibaEu3HNZc0ReJwNO6yNW8aJ0zDNs.Qof7tudjOdqQHy', 'Admin', 'User', 1); 

-- Insert organizer user (password: organizer123)
INSERT INTO users (email, password, first_name, last_name, role_id) 
VALUES ('organizer@fast.edu.pk', '$2y$10$jhEkHziC5ibaEu3HNZc0ReJwNO6yNW8aJ0zDNs.Qof7tudjOdqQHy', 'Organizer', 'User', 2); 

-- Insert student user (password: student123)
INSERT INTO users (email, password, first_name, last_name, role_id) 
VALUES ('student@fast.edu.pk', '$2y$10$jhEkHziC5ibaEu3HNZc0ReJwNO6yNW8aJ0zDNs.Qof7tudjOdqQHy', 'Student', 'User', 3); 

-- Insert outsider user (password: outsider123)
INSERT INTO users (email, password, first_name, last_name, role_id) 
VALUES ('outsider@fast.edu.pk', '$2y$10$jhEkHziC5ibaEu3HNZc0ReJwNO6yNW8aJ0zDNs.Qof7tudjOdqQHy', 'Outsider', 'User', 4); 


