-- ========================================
-- Envindo Database Setup Script
-- ========================================
-- 
-- Cara menggunakan:
-- 1. Buka phpMyAdmin: http://localhost/phpmyadmin
-- 2. Klik tab "SQL" di bagian atas
-- 3. Copy-paste script ini
-- 4. Klik "Go" untuk execute
--
-- Atau via command line:
-- mysql -u root -p < database-setup.sql
-- ========================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `envipoin_db` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_general_ci;

-- Use the database
USE `envipoin_db`;

-- Display success message
SELECT 'Database envipoin_db created successfully!' AS Status;

-- ========================================
-- Optional: Create dedicated database user
-- ========================================
-- Uncomment lines below if you want to create a dedicated user
-- (Recommended for production, optional for development)

-- CREATE USER IF NOT EXISTS 'envipoin_user'@'localhost' IDENTIFIED BY 'envipoin_password_123';
-- GRANT ALL PRIVILEGES ON envipoin_db.* TO 'envipoin_user'@'localhost';
-- FLUSH PRIVILEGES;
-- SELECT 'Database user created successfully!' AS Status;

-- ========================================
-- Note: Tables will be created by CodeIgniter migrations
-- Run: php spark migrate
-- ========================================
