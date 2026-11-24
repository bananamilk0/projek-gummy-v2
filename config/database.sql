-- Database Schema for Gaming Community
CREATE DATABASE IF NOT EXISTS gaming_community;
USE gaming_community;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('guest', 'user', 'moderator', 'admin') DEFAULT 'user',
    xp INT DEFAULT 0,
    level INT DEFAULT 1,
    coins INT DEFAULT 0,
    avatar VARCHAR(255) DEFAULT 'default.png',
    frame VARCHAR(255) DEFAULT 'default_frame.png',
    name_color VARCHAR(50) DEFAULT '#ffffff',
    unlocked_colors TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    content TEXT NOT NULL,
    media_url VARCHAR(255) DEFAULT NULL,
    likes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    game_id INT,
    score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

-- Insert dummy game
INSERT IGNORE INTO games (name, slug, description) VALUES ('Neon Runner', 'neon-runner', 'Endless running game in a synthwave city.');

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
);

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES 
('welcome_title', 'Welcome to Web Gummy'),
('welcome_message', 'The ultimate hub for gamers, creators, and everyone in between.'),
('announcement', 'Check out our new real-time chat and leveling system!');
