-- FoodShare Database Schema (Fixed)
-- Run this script to create the required database and tables

-- Create database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS foodshare;
USE foodshare;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('donor', 'receiver', 'admin') NOT NULL,
    organization_name VARCHAR(100),
    contact_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Food posts table
CREATE TABLE IF NOT EXISTS food_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    quantity INT NOT NULL,
    unit VARCHAR(20) NOT NULL,
    food_type VARCHAR(50) NOT NULL,
    expiration_datetime DATETIME NOT NULL,
    pickup_location TEXT NOT NULL,
    nutritional_info TEXT,
    image_path VARCHAR(255),
    status ENUM('available', 'claimed', 'completed', 'expired', 'cancelled') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Pickups table (for tracking food claims)
CREATE TABLE IF NOT EXISTS pickups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    receiver_id INT NOT NULL,
    scheduled_at DATETIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES food_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_post_id INT,
    receiver_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (food_post_id) REFERENCES food_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for better performance (only if they don't exist)
CREATE INDEX IF NOT EXISTS idx_food_posts_status ON food_posts(status);
CREATE INDEX IF NOT EXISTS idx_food_posts_user_id ON food_posts(user_id);
CREATE INDEX IF NOT EXISTS idx_food_posts_food_type ON food_posts(food_type);
CREATE INDEX IF NOT EXISTS idx_pickups_post_id ON pickups(post_id);
CREATE INDEX IF NOT EXISTS idx_pickups_receiver_id ON pickups(receiver_id);
CREATE INDEX IF NOT EXISTS idx_notifications_receiver_id ON notifications(receiver_id);
CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- Sample data will be inserted by the setup script to avoid conflicts
-- Note: Sample users (donor1, receiver1, admin) with password 'password' will be created during setup
