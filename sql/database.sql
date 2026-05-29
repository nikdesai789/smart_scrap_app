-- Create Database
CREATE DATABASE IF NOT EXISTS smart_scrap_db;
USE smart_scrap_db;

-- Users (Vendors) Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    rating DECIMAL(3,2) DEFAULT 0,
    total_requests INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stakeholders Table
CREATE TABLE IF NOT EXISTS stakeholders(
    id INT PRIMARY KEY AUTO_INCREMENT,
    business_name VARCHAR(100) NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    description TEXT,
    rating DECIMAL(3,2) DEFAULT 0,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);





-- Scrap Requests Table
CREATE TABLE IF NOT EXISTS scrap_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    stakeholder_id INT NOT NULL,
    category_id INT NOT NULL,
    quantity_range VARCHAR(50) NOT NULL,
    estimated_quantity DECIMAL(10,2),
    description TEXT,
    images TEXT,
    pickup_address TEXT,
    pickup_date DATE NOT NULL,
    pickup_time TIME NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    agent_name VARCHAR(100),
    agent_phone VARCHAR(15),
    agent_vehicle_no VARCHAR(50),
    otp VARCHAR(10),
    actual_weight DECIMAL(10,2),
    final_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id),
    FOREIGN KEY (stakeholder_id) REFERENCES stakeholders(id),
    FOREIGN KEY (category_id) REFERENCES scrap_categories(id)
);





-- Insert Sample Data
INSERT INTO stakeholders (business_name, owner_name, email, password, phone, address, latitude, longitude, description, verified) VALUES
('Green Recyclers', 'Rajesh Kumar', 'rajesh@greenrecyclers.com', MD5('password123'), '9876543210', 'MG Road, Delhi', 28.6139, 77.2090, 'Professional scrap dealer since 2010', TRUE),
('Eco Scrap Solutions', 'Priya Singh', 'priya@ecoscrap.com', MD5('password123'), '9876543211', 'Connaught Place, Delhi', 28.6315, 77.2167, 'Best prices for all scrap types', TRUE);

