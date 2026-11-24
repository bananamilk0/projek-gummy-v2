USE gaming_community;

-- ===== USERS =====
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS bg_image VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS aura VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS nameplate VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS nameplate_style TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS frame VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS frame_style TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS aura_css TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS equipped_character INT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS unlocked_items TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_date DATE DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS login_streak INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS total_login_days INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS voice_minutes INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS chat_streak INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_chat_date DATE DEFAULT NULL;

-- ===== CHAT MESSAGES =====
ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS channel VARCHAR(50) DEFAULT 'ngobrol';
ALTER TABLE chat_messages MODIFY COLUMN message TEXT NULL;
ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS media_url VARCHAR(255) DEFAULT NULL;

-- ===== SHOP ITEMS — rebuild dengan kategori character =====
DROP TABLE IF EXISTS user_inventory;
DROP TABLE IF EXISTS shop_items;

CREATE TABLE shop_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('border','nameplate','character') NOT NULL,
    price INT NOT NULL DEFAULT 100,
    preview_css TEXT,
    aura_css TEXT DEFAULT NULL,
    emoji VARCHAR(20) DEFAULT NULL COMMENT 'Emoji/icon for character items',
    rarity ENUM('common','rare','epic','legendary') DEFAULT 'common',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_item (user_id, item_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES shop_items(id) ON DELETE CASCADE
);

-- ===== SEED: BORDERS =====
INSERT INTO shop_items (id,name,description,category,price,preview_css,aura_css,emoji,rarity) VALUES
(1,'Violet Ring','Border ungu simpel yang elegan dan timeless','border',250,'border:3px solid #8a2be2; box-shadow:0 0 8px rgba(138,43,226,0.6);',NULL,NULL,'common'),
(2,'Cyan Line','Border biru muda bersih dan modern','border',250,'border:3px solid #00e5ff; box-shadow:0 0 8px rgba(0,229,255,0.6);',NULL,NULL,'common'),
(3,'Rose Petal','Border merah muda lembut nan romantis','border',350,'border:3px solid #ff6b9d; box-shadow:0 0 8px rgba(255,107,157,0.55);',NULL,NULL,'common'),
(4,'Gold Trim','Border emas klasik bertekstur mewah','border',500,'border:3px solid #ffd700; box-shadow:0 0 10px rgba(255,215,0,0.5);',NULL,NULL,'rare'),
(5,'Emerald Edge','Border hijau zamrud tajam dan premium','border',600,'border:3px solid #00c875; box-shadow:0 0 10px rgba(0,200,117,0.55);',NULL,NULL,'rare'),
(6,'Galaxy Halo','Border gradasi warna galaksi memukau','border',750,'border:3px solid transparent; background:linear-gradient(#0f111a,#0f111a) padding-box, linear-gradient(135deg,#8a2be2,#00e5ff,#ff007f) border-box;',NULL,NULL,'rare'),
(7,'Nova Aura','Border ungu dengan aura berdenyut memesona','border',1200,'border:3px solid #a040ff;','box-shadow:0 0 0 5px rgba(160,64,255,0.2),0 0 20px rgba(160,64,255,0.6),0 0 40px rgba(160,64,255,0.15); animation:aura-pulse 2s ease-in-out infinite;',NULL,'epic'),
(8,'Ocean Aura','Border cyan dengan aura lautan berkilau','border',1200,'border:3px solid #00e5ff;','box-shadow:0 0 0 5px rgba(0,229,255,0.18),0 0 20px rgba(0,229,255,0.6),0 0 40px rgba(0,229,255,0.12); animation:aura-pulse 2.2s ease-in-out infinite;',NULL,'epic'),
(9,'Sakura Aura','Border pink dengan aura bunga sakura lembut','border',1300,'border:3px solid #ff6b9d;','box-shadow:0 0 0 5px rgba(255,107,157,0.2),0 0 22px rgba(255,107,157,0.6),0 0 45px rgba(255,107,157,0.12); animation:aura-pulse 2.5s ease-in-out infinite;',NULL,'epic'),
(10,'Inferno','Border api legendaris yang membara dahsyat','border',2000,'border:3px solid #ff6b35;','box-shadow:0 0 0 5px rgba(255,107,53,0.3),0 0 28px rgba(255,107,53,0.7),0 0 55px rgba(255,40,0,0.25); animation:aura-pulse 1.4s ease-in-out infinite;',NULL,'legendary'),
(11,'Divine Crown','Border emas ilahi memancarkan cahaya surgawi','border',2800,'border:3px solid #ffd700;','box-shadow:0 0 0 6px rgba(255,215,0,0.35),0 0 32px rgba(255,215,0,0.75),0 0 65px rgba(255,200,0,0.3); animation:aura-pulse 1s ease-in-out infinite;',NULL,'legendary');

-- ===== SEED: NAMEPLATES =====
INSERT INTO shop_items (id,name,description,category,price,preview_css,aura_css,emoji,rarity) VALUES
(12,'Silver Tag','Nameplate perak minimalis dan bersih','nameplate',300,'background:rgba(160,165,180,0.15); border:1px solid rgba(160,165,180,0.5); color:#c8ccd8; border-radius:20px;',NULL,NULL,'common'),
(13,'Indigo Box','Nameplate biru gelap penuh aura misterius','nameplate',300,'background:rgba(80,60,200,0.18); border:1px solid rgba(100,80,220,0.5); color:#a0a8ff; border-radius:8px;',NULL,NULL,'common'),
(14,'Forest Tag','Nameplate hijau hutan segar dan natural','nameplate',350,'background:rgba(0,180,100,0.15); border:1px solid rgba(0,200,110,0.45); color:#60e090; border-radius:20px;',NULL,NULL,'common'),
(15,'Neon Cyan','Nameplate neon biru memancarkan energi','nameplate',550,'background:linear-gradient(135deg,rgba(0,229,255,0.18),rgba(0,160,200,0.1)); border:1px solid rgba(0,229,255,0.55); color:#00e5ff; border-radius:20px;',NULL,NULL,'rare'),
(16,'Crimson Flame','Nameplate merah membara penuh semangat juang','nameplate',600,'background:linear-gradient(135deg,rgba(220,50,0,0.2),rgba(180,30,0,0.12)); border:1px solid rgba(255,80,40,0.55); color:#ff6b3d; border-radius:20px;',NULL,NULL,'rare'),
(17,'Galaxy Frame','Nameplate bergradasi galaksi yang memukau','nameplate',700,'background:linear-gradient(135deg,rgba(138,43,226,0.25),rgba(0,229,255,0.18)); border:1px solid rgba(138,43,226,0.5); color:#d0a8ff; border-radius:12px;',NULL,NULL,'rare'),
(18,'Starlight Aura','Nameplate bintang dengan aura cahaya gemilang','nameplate',1400,'background:linear-gradient(135deg,rgba(138,43,226,0.3),rgba(0,229,255,0.2)); border:1.5px solid rgba(200,150,255,0.6); color:#fff; border-radius:20px;','box-shadow:0 0 12px rgba(138,43,226,0.6),0 0 25px rgba(0,229,255,0.35); animation:aura-pulse 2s ease-in-out infinite; text-shadow:0 0 8px rgba(255,255,255,0.8);',NULL,'epic'),
(19,'Phoenix Aura','Nameplate oranye berkobar bak nyala feniks','nameplate',1500,'background:linear-gradient(135deg,rgba(255,140,0,0.28),rgba(220,60,0,0.18)); border:1.5px solid rgba(255,140,0,0.6); color:#ffb84d; border-radius:20px;','box-shadow:0 0 14px rgba(255,130,0,0.65),0 0 28px rgba(255,80,0,0.35); animation:aura-pulse 1.8s ease-in-out infinite; text-shadow:0 0 8px rgba(255,150,50,0.85);',NULL,'epic'),
(20,'Lunar Crown','Nameplate bulan purnama legendaris bercahaya','nameplate',2100,'background:linear-gradient(135deg,rgba(255,215,0,0.25),rgba(200,160,0,0.15)); border:2px solid rgba(255,215,0,0.7); color:#ffd700; border-radius:20px;','box-shadow:0 0 16px rgba(255,215,0,0.6),0 0 35px rgba(255,200,0,0.28); animation:aura-pulse 1.5s ease-in-out infinite; text-shadow:0 0 10px rgba(255,215,0,0.85);',NULL,'legendary'),
(21,'Celestial','Nameplate surgawi hanya untuk jiwa terpilih','nameplate',3000,'background:linear-gradient(135deg,rgba(200,180,255,0.2),rgba(80,120,255,0.25),rgba(0,229,255,0.18)); border:2px solid rgba(180,160,255,0.65); color:#e8e0ff; border-radius:20px;','box-shadow:0 0 20px rgba(180,160,255,0.6),0 0 45px rgba(80,120,255,0.3); animation:aura-pulse 1.2s ease-in-out infinite; text-shadow:0 0 10px rgba(220,210,255,0.95);',NULL,'legendary');

-- ===== SEED: CHARACTERS =====
INSERT INTO shop_items (id,name,description,category,price,preview_css,aura_css,emoji,rarity) VALUES
(22,'Gummy Bear','Karakter beruang gummy lucu dan menggemaskan','character',400,NULL,NULL,'🐻','common'),
(23,'Star Bunny','Kelinci bintang yang menggemaskan dan ceria','character',400,NULL,NULL,'🐰','common'),
(24,'Penguin Cool','Penguin keren berjaket yang stylish abis','character',500,NULL,NULL,'🐧','common'),
(25,'Ghost','Karakter hantu yang jahil dan menggemaskan','character',600,NULL,NULL,'👻','rare'),
(26,'Pumpkin Jack','Jack-o-lantern halloween yang menyeramkan','character',650,NULL,NULL,'🎃','rare'),
(27,'Witch','Penyihir perempuan bertopi hitam misterius','character',700,NULL,NULL,'🧙','rare'),
(28,'Cupcake','Cupcake manis berlapis krim dengan glitter','character',600,NULL,NULL,'🧁','rare'),
(29,'Robot','Robot futuristik berteknologi tinggi','character',750,NULL,NULL,'🤖','rare'),
(30,'Ninja','Ninja gelap yang bergerak cepat bak bayangan','character',800,NULL,NULL,'🥷','rare'),
(31,'Shadow Reaper','Karakter seram dengan aura kegelapan mematikan','character',1200,NULL,'filter:drop-shadow(0 0 8px rgba(100,0,200,0.8)) drop-shadow(0 0 16px rgba(50,0,100,0.5)); animation:aura-pulse 2s ease-in-out infinite;','💀','epic'),
(32,'Cyber Fox','Rubah cyber neon dengan efek hologram keren','character',1300,NULL,'filter:drop-shadow(0 0 8px rgba(0,229,255,0.8)) drop-shadow(0 0 16px rgba(0,100,200,0.5)); animation:aura-pulse 1.8s ease-in-out infinite;','🦊','epic'),
(33,'Flame Demon','Iblis api dengan sayap yang berkobar membara','character',1500,NULL,'filter:drop-shadow(0 0 10px rgba(255,80,0,0.9)) drop-shadow(0 0 20px rgba(200,30,0,0.5)); animation:aura-pulse 1.5s ease-in-out infinite;','😈','epic'),
(34,'Angel','Malaikat suci memancarkan cahaya surgawi murni','character',2000,NULL,'filter:drop-shadow(0 0 12px rgba(255,215,0,0.9)) drop-shadow(0 0 24px rgba(255,200,0,0.5)); animation:aura-pulse 1.3s ease-in-out infinite;','⭐','legendary'),
(35,'Void Walker','Makhluk dari dimensi void dengan aura kosmis','character',2500,NULL,'filter:drop-shadow(0 0 14px rgba(138,43,226,0.95)) drop-shadow(0 0 28px rgba(80,0,200,0.6)); animation:aura-pulse 1s ease-in-out infinite;','🌀','legendary');

-- ===== NEWS =====
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    published_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ===== VOICE SESSIONS =====
CREATE TABLE IF NOT EXISTS voice_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    channel VARCHAR(50) NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_voice (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===== ACHIEVEMENTS =====
ALTER TABLE achievements ADD COLUMN IF NOT EXISTS xp_reward INT DEFAULT 50;
ALTER TABLE achievements ADD COLUMN IF NOT EXISTS coin_reward INT DEFAULT 20;
ALTER TABLE achievements ADD COLUMN IF NOT EXISTS icon VARCHAR(20) DEFAULT '🏆';
ALTER TABLE achievements ADD COLUMN IF NOT EXISTS game_id INT DEFAULT NULL;
ALTER TABLE achievements ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'global';

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_ach (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

-- Bersihkan achievement lama yang mungkin salah nama
DELETE FROM achievements WHERE name IN ('Pencerita Harian','Marathon Suara');

-- Seed achievement baru (INSERT IGNORE = tidak duplicate)
INSERT IGNORE INTO achievements (name, description, icon, xp_reward, coin_reward, category) VALUES
('Paling Rajin','Kirim pesan di chat selama 7 hari berturut-turut (1x per hari, tidak ada XP dari chat)','💬',150,75,'social'),
('Paling Betah','Habiskan total 100 jam di voice channel','🎙️',500,250,'social'),
('Ghost Mode','Login pada pukul 03:00 dini hari','👻',200,100,'special'),
('Lunarian 𝟏','Login secara berturut-turut selama 7 hari','🌙',100,50,'streak'),
('Lunarian 𝟐','Login secara berturut-turut selama 30 hari','🌕',300,150,'streak'),
('Lunarian 𝟑','Login secara berturut-turut selama 100 hari','🌟',1000,500,'streak');

-- ===== DAILY QUESTS =====
CREATE TABLE IF NOT EXISTS daily_quests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    xp_reward INT DEFAULT 50,
    coin_reward INT DEFAULT 25,
    requirement_type ENUM('chat_messages','game_score','login','voice_minutes') DEFAULT 'chat_messages',
    requirement_value INT DEFAULT 5
);

CREATE TABLE IF NOT EXISTS user_quests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quest_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    progress INT DEFAULT 0,
    completed TINYINT(1) DEFAULT 0,
    UNIQUE KEY uq_user_quest_date (user_id, quest_id, assigned_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES daily_quests(id) ON DELETE CASCADE
);

INSERT IGNORE INTO daily_quests (id,title,description,xp_reward,coin_reward,requirement_type,requirement_value) VALUES
(1,'Pagi yang Aktif','Kirim 5 pesan di chat hari ini',50,25,'chat_messages',5),
(2,'Komentator Tangguh','Kirim 15 pesan di chat hari ini',100,50,'chat_messages',15),
(3,'Obrolan Marathon','Kirim 30 pesan di chat',200,100,'chat_messages',30),
(4,'Pemain Aktif','Mainkan satu game hari ini (skor > 0)',75,35,'game_score',1),
(5,'Gamer Sejati','Raih total skor 1000 di game',150,75,'game_score',1000),
(6,'Selalu Hadir','Login hari ini',30,15,'login',1),
(7,'Voice Lover','Habiskan 10 menit di voice channel',80,40,'voice_minutes',10),
(8,'Suara Komunitas','Habiskan 30 menit di voice channel',160,80,'voice_minutes',30);

INSERT IGNORE INTO site_settings (setting_key,setting_value) VALUES ('news_enabled','1');
