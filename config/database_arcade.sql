USE gaming_community;

-- Add new columns to users for login streak and last login
ALTER TABLE users ADD COLUMN IF NOT EXISTS login_streak INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATE DEFAULT NULL;

-- Achievements Table
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    xp_reward INT DEFAULT 50,
    coin_reward INT DEFAULT 10,
    icon VARCHAR(50) DEFAULT '🏆',
    requirement_type VARCHAR(50) NOT NULL, -- e.g., 'high_score', 'play_count', 'total_score'
    requirement_value INT NOT NULL,
    game_id INT NULL, -- NULL means global achievement
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

-- User Achievements Table
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    achievement_id INT,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id)
);

-- Daily Quests Table
CREATE TABLE IF NOT EXISTS daily_quests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    xp_reward INT DEFAULT 20,
    coin_reward INT DEFAULT 5,
    requirement_type VARCHAR(50) NOT NULL, -- e.g., 'play_game', 'score_target'
    requirement_value INT NOT NULL,
    game_id INT NULL,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

-- User Quests Table
CREATE TABLE IF NOT EXISTS user_quests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    quest_id INT,
    progress INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    assigned_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES daily_quests(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_quest_date (user_id, quest_id, assigned_date)
);

-- Insert new games
INSERT IGNORE INTO games (id, name, slug, description) VALUES 
(2, 'Aim Trainer', 'aim-trainer', 'Improve your aim by shooting blobs!'),
(3, 'Whack-a-Slime', 'whack-a-slime', 'Whack those slimes back to their holes!'),
(4, 'Pixel Jumper', 'pixel-jumper', 'Jump higher and higher!'),
(5, 'Gravity Flip', 'gravity-flip', 'Flip gravity to dodge obstacles!'),
(6, 'Escape Maze', 'escape-maze', 'Find your way out of the procedural maze!');

-- Insert some dummy achievements
INSERT IGNORE INTO achievements (id, name, description, requirement_type, requirement_value, game_id) VALUES
(1, 'First Steps', 'Play your first game in the Arcade', 'play_count', 1, NULL),
(2, 'Sharpshooter', 'Score 1000 in Aim Trainer', 'high_score', 1000, 2),
(3, 'Slime Bane', 'Score 500 in Whack-a-Slime', 'high_score', 500, 3),
(4, 'Sky High', 'Score 2000 in Pixel Jumper', 'high_score', 2000, 4),
(5, 'Mind Bender', 'Score 1500 in Gravity Flip', 'high_score', 1500, 5),
(6, 'Maze Runner', 'Score 1000 in Escape Maze', 'high_score', 1000, 6);
