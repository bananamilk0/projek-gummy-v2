<?php
require_once __DIR__ . '/../config/database.php';
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

// Ensure news table has correct structure (no FK issue on published_by)
$pdo->exec("CREATE TABLE IF NOT EXISTS news (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  image_url VARCHAR(255) DEFAULT NULL,
  published_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Update Love Pink → heart shape using clip-path
$pdo->exec("UPDATE shop_items SET
  name='Love Heart',
  description='Border berbentuk hati berwarna pink yang imut dan menggemaskan',
  preview_css='border:3px solid #ff6b9d; background:rgba(255,107,157,0.15); clip-path:path(\"M34 13 C34 6 28 0 21 0 C14 0 8 6 8 13 C2 13 0 20 0 27 C0 40 17 52 34 64 C51 52 68 40 68 27 C68 20 66 13 60 13 C53 13 47 6 40 13 Z\"); box-shadow:0 0 12px rgba(255,107,157,0.6);',
  price=500, rarity='rare'
WHERE name='Love Pink' OR name='Love Border'");

// Diamond Aura — thicker/stronger effect
$pdo->exec("UPDATE shop_items SET
  aura_css='box-shadow:0 0 0 6px rgba(0,229,255,0.35),0 0 22px rgba(0,229,255,0.75),0 0 45px rgba(0,229,255,0.3),inset 0 0 15px rgba(0,229,255,0.15); animation:aura-pulse 2.2s ease-in-out infinite;',
  preview_css='border:4px solid #00e5ff; border-radius:0; clip-path:polygon(50% 0%,100% 50%,50% 100%,0% 50%);'
WHERE name='Diamond Aura'");

// Inferno Orbit — smaller orbit radius (20px instead of 28px)
$pdo->exec("UPDATE shop_items SET
  aura_css='box-shadow:0 -20px 0 3px #ff4400, 0 0 22px rgba(255,68,0,0.65), 0 0 40px rgba(200,30,0,0.25); animation:inferno-orbit 1.4s linear infinite;'
WHERE name='Inferno Orbit'");

// Rainbow Halo — add stronger rainbow glow
$pdo->exec("UPDATE shop_items SET
  aura_css='filter:drop-shadow(0 0 6px rgba(255,0,100,0.7)) drop-shadow(0 0 10px rgba(0,200,255,0.6)) drop-shadow(0 0 14px rgba(100,255,0,0.5)); animation:aura-pulse 2s ease-in-out infinite;'
WHERE name='Rainbow Halo'");

// Ensure achievements: add level milestones
$cols = array_column($pdo->query("SHOW COLUMNS FROM achievements")->fetchAll(),'Field');
if (!in_array('category',$cols)) $pdo->exec("ALTER TABLE achievements ADD COLUMN category VARCHAR(50) DEFAULT 'global'");
if (!in_array('xp_reward',$cols)) $pdo->exec("ALTER TABLE achievements ADD COLUMN xp_reward INT DEFAULT 50");
if (!in_array('coin_reward',$cols)) $pdo->exec("ALTER TABLE achievements ADD COLUMN coin_reward INT DEFAULT 20");
if (!in_array('icon',$cols)) $pdo->exec("ALTER TABLE achievements ADD COLUMN icon VARCHAR(20) DEFAULT '🏆'");

$pdo->exec("INSERT IGNORE INTO achievements(name,description,icon,xp_reward,coin_reward,category) VALUES
  ('🌱 Novice I','Mencapai Level 50','🌱',200,100,'level'),
  ('⚔️ Veteran II','Mencapai Level 250','⚔️',500,250,'level'),
  ('👑 Legend III','Mencapai Level 1000','👑',2000,1000,'level')");

// Daily Login quest (type login, once per day, value=1)
$pdo->exec("INSERT IGNORE INTO daily_quests(id,title,description,xp_reward,coin_reward,requirement_type,requirement_value) VALUES
  (9,'Daily Login','Login hari ini untuk mempertahankan Lunarian Streak',50,20,'login',1)");

$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
echo json_encode(['ok'=>true,'msg'=>'Patch applied successfully']);
