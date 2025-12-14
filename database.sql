-- Database: election_system

CREATE DATABASE IF NOT EXISTS `election_system`;
USE `election_system`;

-- Admin Table
CREATE TABLE IF NOT EXISTS `admin` (
    `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Voters Table
CREATE TABLE IF NOT EXISTS `voters` (
    `voter_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(20) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Elections Table
CREATE TABLE IF NOT EXISTS `elections` (
    `election_id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `status` ENUM('active', 'inactive', 'closed') DEFAULT 'inactive',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Candidates Table
CREATE TABLE IF NOT EXISTS `candidates` (
    `candidate_id` INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `details` TEXT,
    `photo_url` VARCHAR(255),
    FOREIGN KEY (`election_id`) REFERENCES `elections`(`election_id`) ON DELETE CASCADE
);

-- Votes Table
CREATE TABLE IF NOT EXISTS `votes` (
    `vote_id` INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT NOT NULL,
    `candidate_id` INT NOT NULL,
    `student_id` VARCHAR(20) NOT NULL,
    `tracking_code` VARCHAR(20) NOT NULL UNIQUE,
    `voted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`election_id`) REFERENCES `elections`(`election_id`) ON DELETE CASCADE,
    FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`candidate_id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_vote` (`election_id`, `student_id`)
);

-- Default Admin (Password: admin123) - Change this in production!
INSERT INTO `admin` (`username`, `password_hash`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
