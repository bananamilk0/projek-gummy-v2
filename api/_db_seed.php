<?php
// _db_seed.php — jalankan sekali via browser: http://localhost/web-gummy/api/_db_seed.php
require_once __DIR__ . '/../config/database.php';
$ok=0; $err=0; $log=[];

function q($pdo,$sql,&$ok,&$err,&$log){
    $sql=trim($sql); if(strlen($sql)<4) return;
    try { $pdo->exec($sql); $log[]=['ok',substr(preg_replace('/\s+/',' ',$sql),0,90)]; $ok++; }
    catch(PDOException $e){ $log[]=['err',substr(preg_replace('/\s+/',' ',$sql),0,80).' → '.$e->getMessage()]; $err++; }
}

function safeAdd($pdo,$table,$col,$def,&$ok,&$err,&$log){
    try {
        $cols=array_column($pdo->query("SHOW COLUMNS FROM $table")->fetchAll(),'Field');
        if(!in_array($col,$cols)) q($pdo,"ALTER TABLE $table ADD COLUMN $col $def",$ok,$err,$log);
        else { $log[]=['ok',"$table.$col exists"]; }
    } catch(Exception $e){ $log[]=['err',"check $table.$col: ".$e->getMessage()]; }
}

$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

// ── 0. Patch achievements columns ────────────────────────────────────────
foreach(['xp_reward'=>'INT DEFAULT 50','coin_reward'=>'INT DEFAULT 20',
         'icon'=>"VARCHAR(20) DEFAULT '🏆'",'game_id'=>'INT DEFAULT NULL',
         'category'=>"VARCHAR(50) DEFAULT 'global'"] as $c=>$d)
    safeAdd($pdo,'achievements',$c,$d,$ok,$err,$log);

// ── 1. Recreate shop tables ───────────────────────────────────────────────
q($pdo,"DROP TABLE IF EXISTS user_inventory",$ok,$err,$log);
q($pdo,"DROP TABLE IF EXISTS shop_items",$ok,$err,$log);
q($pdo,"CREATE TABLE shop_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  category ENUM('border','nameplate','character') NOT NULL,
  price INT NOT NULL DEFAULT 100,
  preview_css TEXT,
  aura_css TEXT DEFAULT NULL,
  emoji VARCHAR(20) DEFAULT NULL,
  rarity ENUM('common','rare','epic','legendary') DEFAULT 'common',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",$ok,$err,$log);
q($pdo,"CREATE TABLE user_inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  item_id INT NOT NULL,
  purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uui (user_id,item_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES shop_items(id) ON DELETE CASCADE
)",$ok,$err,$log);

$ins=$pdo->prepare("INSERT INTO shop_items(id,name,description,category,price,preview_css,aura_css,emoji,rarity) VALUES(?,?,?,?,?,?,?,?,?)");

// ── 2. BORDERS — berbagai bentuk (bulat, kotak, diamond, hexagon, dll) ────
// border-radius trick: 50%=circle, 0=square, 12px=rounded, 50% 0=diamond, 60%40%=shield
$borders=[
// Common — simpel
[1,'Circle Violet','Border lingkaran ungu simpel elegan','border',200,'border:3px solid #8a2be2; border-radius:50%; box-shadow:0 0 8px rgba(138,43,226,0.5);',null,null,'common'],
[2,'Circle Cyan','Border lingkaran biru muda bersih','border',200,'border:3px solid #00e5ff; border-radius:50%; box-shadow:0 0 8px rgba(0,229,255,0.5);',null,null,'common'],
[3,'Square Dark','Border kotak sudut tajam warna gelap','border',220,'border:3px solid #a0a5b5; border-radius:4px; box-shadow:0 0 6px rgba(160,165,181,0.4);',null,null,'common'],
[4,'Square Rose','Border kotak merah muda sudut sedikit tumpul','border',250,'border:3px solid #ff6b9d; border-radius:8px; box-shadow:0 0 7px rgba(255,107,157,0.5);',null,null,'common'],
[5,'Pill White','Border kapsul tipis warna putih bersih','border',280,'border:2px solid rgba(255,255,255,0.7); border-radius:50px; box-shadow:0 0 6px rgba(255,255,255,0.3);',null,null,'common'],
// Rare — bentuk lebih unik
[6,'Gold Square','Border kotak emas bertekstur mewah','border',480,'border:3px solid #ffd700; border-radius:6px; box-shadow:0 0 12px rgba(255,215,0,0.5);',null,null,'rare'],
[7,'Hex Emerald','Border hexagon hijau zamrud premium','border',550,'border:3px solid #00c875; border-radius:14px 0 14px 0; box-shadow:0 0 10px rgba(0,200,117,0.5);',null,null,'rare'],
[8,'Diamond Blue','Border bentuk diamond biru berkilau','border',600,'border:3px solid #00aaff; border-radius:0; transform:rotate(0deg); box-shadow:0 0 12px rgba(0,170,255,0.5); clip-path:polygon(50% 0%,100% 50%,50% 100%,0% 50%);',null,null,'rare'],
[9,'Shield Purple','Border berbentuk perisai ungu gagah','border',650,'border:3px solid #9b59b6; border-radius:50% 50% 30% 30% / 60% 60% 40% 40%; box-shadow:0 0 10px rgba(155,89,182,0.5);',null,null,'rare'],
[10,'Galaxy Halo','Border gradasi galaksi semua sisi','border',750,'border:3px solid transparent; border-radius:50%; background:linear-gradient(#0f111a,#0f111a) padding-box, linear-gradient(135deg,#8a2be2,#00e5ff,#ff007f) border-box;',null,null,'rare'],
// Epic — dengan aura
[11,'Nova Aura','Border lingkaran ungu aura berdenyut','border',1200,'border:3px solid #a040ff; border-radius:50%;','box-shadow:0 0 0 5px rgba(160,64,255,0.2),0 0 20px rgba(160,64,255,0.6),0 0 40px rgba(160,64,255,0.15); animation:aura-pulse 2s ease-in-out infinite;',null,'epic'],
[12,'Ocean Aura','Border lingkaran cyan aura laut','border',1200,'border:3px solid #00e5ff; border-radius:50%;','box-shadow:0 0 0 5px rgba(0,229,255,0.18),0 0 20px rgba(0,229,255,0.6),0 0 40px rgba(0,229,255,0.12); animation:aura-pulse 2.2s ease-in-out infinite;',null,'epic'],
[13,'Square Nova','Border KOTAK ungu dengan aura keren','border',1300,'border:3px solid #a040ff; border-radius:10px;','box-shadow:0 0 0 4px rgba(160,64,255,0.2),0 0 18px rgba(160,64,255,0.55),0 0 35px rgba(160,64,255,0.12); animation:aura-pulse 2s ease-in-out infinite;',null,'epic'],
[14,'Diamond Aura','Border diamond dengan aura cyan memukau','border',1400,'border:3px solid #00e5ff; border-radius:0; clip-path:polygon(50% 0%,100% 50%,50% 100%,0% 50%);','box-shadow:0 0 0 4px rgba(0,229,255,0.2),0 0 18px rgba(0,229,255,0.55),0 0 35px rgba(0,229,255,0.1); animation:aura-pulse 2.2s ease-in-out infinite;',null,'epic'],
[15,'Sakura Aura','Border shield pink aura sakura lembut','border',1350,'border:3px solid #ff6b9d; border-radius:50% 50% 30% 30% / 60% 60% 40% 40%;','box-shadow:0 0 0 5px rgba(255,107,157,0.2),0 0 22px rgba(255,107,157,0.6),0 0 45px rgba(255,107,157,0.12); animation:aura-pulse 2.5s ease-in-out infinite;',null,'epic'],
// Legendary
[16,'Inferno Ring','Border lingkaran api legendaris membara','border',2000,'border:3px solid #ff6b35; border-radius:50%;','box-shadow:0 0 0 5px rgba(255,107,53,0.3),0 0 28px rgba(255,107,53,0.7),0 0 55px rgba(255,40,0,0.25); animation:aura-pulse 1.4s ease-in-out infinite;',null,'legendary'],
[17,'Divine Crown','Border kotak emas cahaya surgawi','border',2800,'border:3px solid #ffd700; border-radius:8px;','box-shadow:0 0 0 6px rgba(255,215,0,0.35),0 0 32px rgba(255,215,0,0.75),0 0 65px rgba(255,200,0,0.3); animation:aura-pulse 1s ease-in-out infinite;',null,'legendary'],
[18,'Void Hex','Border hexagon void dengan aura kosmis','border',3000,'border:3px solid #9b59b6; border-radius:14px 0 14px 0;','box-shadow:0 0 0 6px rgba(138,43,226,0.3),0 0 30px rgba(138,43,226,0.7),0 0 60px rgba(80,0,200,0.3); animation:aura-pulse 1.2s ease-in-out infinite;',null,'legendary'],
];
foreach($borders as $b){try{$ins->execute($b);$ok++;$log[]=['ok',"Border: ".$b[1]];}catch(PDOException $e){$err++;$log[]=['err',"Border ".$b[1].": ".$e->getMessage()];}}

// ── 3. NAMEPLATES — berbagai bentuk (pill, kotak, ribbon, badge, dll) ─────
$nameplates=[
// Common — berbeda bentuk
[20,'Silver Pill','Nameplate kapsul perak minimalis','nameplate',280,'background:rgba(160,165,180,0.15); border:1px solid rgba(160,165,180,0.5); color:#c8ccd8; border-radius:50px; padding:6px 20px;',null,null,'common'],
[21,'Dark Square','Nameplate kotak gelap bertekstur','nameplate',280,'background:rgba(20,20,40,0.6); border:2px solid rgba(138,43,226,0.4); color:#a0a5c8; border-radius:6px; padding:6px 16px;',null,null,'common'],
[22,'Forest Leaf','Nameplate hijau berbentuk daun/oval','nameplate',300,'background:rgba(0,180,100,0.15); border:1px solid rgba(0,200,110,0.45); color:#60e090; border-radius:50% 10% 50% 10% / 30% 50% 30% 50%; padding:6px 18px;',null,null,'common'],
[23,'Rose Badge','Nameplate lencana merah muda','nameplate',320,'background:rgba(255,107,157,0.15); border:1px solid rgba(255,107,157,0.45); color:#ff9dc0; border-radius:8px 8px 12px 12px; padding:6px 16px;',null,null,'common'],
[24,'Ice Flat','Nameplate datar biru es bersih','nameplate',300,'background:rgba(0,180,255,0.1); border:1px solid rgba(0,200,255,0.35); color:#80ddff; border-radius:4px; padding:6px 18px; letter-spacing:1px;',null,null,'common'],
// Rare
[25,'Neon Cyan','Nameplate neon biru memancarkan energi','nameplate',550,'background:linear-gradient(135deg,rgba(0,229,255,0.18),rgba(0,160,200,0.1)); border:1px solid rgba(0,229,255,0.55); color:#00e5ff; border-radius:20px;',null,null,'rare'],
[26,'Crimson Tab','Nameplate merah tab game keren','nameplate',600,'background:linear-gradient(135deg,rgba(220,50,0,0.22),rgba(180,30,0,0.12)); border:1px solid rgba(255,80,40,0.55); color:#ff6b3d; border-radius:4px 4px 16px 16px;',null,null,'rare'],
[27,'Gold Hexagon','Nameplate hexagonal emas premium','nameplate',700,'background:linear-gradient(135deg,rgba(255,215,0,0.15),rgba(200,160,0,0.1)); border:1px solid rgba(255,215,0,0.5); color:#ffd700; border-radius:10px 0 10px 0;',null,null,'rare'],
[28,'Galaxy Ribbon','Nameplate ribbon gradasi galaksi','nameplate',750,'background:linear-gradient(135deg,rgba(138,43,226,0.25),rgba(0,229,255,0.18)); border:1px solid rgba(138,43,226,0.5); color:#d0a8ff; border-radius:50px 4px 50px 4px;',null,null,'rare'],
[29,'Ember Wing','Nameplate sayap asimetris merah','nameplate',800,'background:linear-gradient(135deg,rgba(255,100,0,0.2),rgba(200,40,0,0.12)); border:1px solid rgba(255,100,0,0.5); color:#ff8040; border-radius:4px 50px 4px 50px;',null,null,'rare'],
// Epic — dengan aura
[30,'Starlight Aura','Nameplate bintang aura cahaya gemilang','nameplate',1400,'background:linear-gradient(135deg,rgba(138,43,226,0.3),rgba(0,229,255,0.2)); border:1.5px solid rgba(200,150,255,0.6); color:#fff; border-radius:20px;','box-shadow:0 0 12px rgba(138,43,226,0.6),0 0 25px rgba(0,229,255,0.35); animation:aura-pulse 2s ease-in-out infinite; text-shadow:0 0 8px rgba(255,255,255,0.8);',null,'epic'],
[31,'Phoenix Aura','Nameplate oranye aura feniks berkobar','nameplate',1500,'background:linear-gradient(135deg,rgba(255,140,0,0.28),rgba(220,60,0,0.18)); border:1.5px solid rgba(255,140,0,0.6); color:#ffb84d; border-radius:50px 4px 50px 4px;','box-shadow:0 0 14px rgba(255,130,0,0.65),0 0 28px rgba(255,80,0,0.35); animation:aura-pulse 1.8s ease-in-out infinite; text-shadow:0 0 8px rgba(255,150,50,0.85);',null,'epic'],
[32,'Ice Aura','Nameplate kotak es aura biru membeku','nameplate',1400,'background:linear-gradient(135deg,rgba(0,200,255,0.2),rgba(0,150,220,0.12)); border:2px solid rgba(0,220,255,0.6); color:#80eeff; border-radius:6px;','box-shadow:0 0 12px rgba(0,220,255,0.55),0 0 24px rgba(0,180,255,0.3); animation:aura-pulse 2.5s ease-in-out infinite; text-shadow:0 0 8px rgba(0,220,255,0.7);',null,'epic'],
// Legendary
[33,'Lunar Crown','Nameplate bulan purnama legendaris','nameplate',2100,'background:linear-gradient(135deg,rgba(255,215,0,0.25),rgba(200,160,0,0.15)); border:2px solid rgba(255,215,0,0.7); color:#ffd700; border-radius:50px;','box-shadow:0 0 16px rgba(255,215,0,0.6),0 0 35px rgba(255,200,0,0.28); animation:aura-pulse 1.5s ease-in-out infinite; text-shadow:0 0 10px rgba(255,215,0,0.85);',null,'legendary'],
[34,'Celestial','Nameplate surgawi hanya jiwa terpilih','nameplate',3000,'background:linear-gradient(135deg,rgba(200,180,255,0.2),rgba(80,120,255,0.25),rgba(0,229,255,0.18)); border:2px solid rgba(180,160,255,0.65); color:#e8e0ff; border-radius:10px 0 10px 0;','box-shadow:0 0 20px rgba(180,160,255,0.6),0 0 45px rgba(80,120,255,0.3); animation:aura-pulse 1.2s ease-in-out infinite; text-shadow:0 0 10px rgba(220,210,255,0.95);',null,'legendary'],
[35,'Void Hex','Nameplate void hexagonal kosmis','nameplate',3500,'background:linear-gradient(135deg,rgba(80,0,180,0.35),rgba(0,0,80,0.2)); border:2px solid rgba(138,43,226,0.8); color:#c090ff; border-radius:10px 0 10px 0;','box-shadow:0 0 18px rgba(138,43,226,0.7),0 0 40px rgba(80,0,200,0.35); animation:aura-pulse 1s ease-in-out infinite; text-shadow:0 0 10px rgba(180,100,255,0.9);',null,'legendary'],
];
foreach($nameplates as $b){try{$ins->execute($b);$ok++;$log[]=['ok',"Nameplate: ".$b[1]];}catch(PDOException $e){$err++;$log[]=['err',"Nameplate ".$b[1].": ".$e->getMessage()];}}

// ── 4. CHARACTERS ────────────────────────────────────────────────────────
$ahalloween='filter:drop-shadow(0 0 6px rgba(255,80,0,0.8)) drop-shadow(0 0 14px rgba(200,30,0,0.4)); animation:aura-pulse 1.8s ease-in-out infinite;';
$acupcake ='filter:drop-shadow(0 0 6px rgba(255,180,220,0.8)) drop-shadow(0 0 14px rgba(255,100,180,0.4)); animation:aura-pulse 2s ease-in-out infinite;';
$acyber   ='filter:drop-shadow(0 0 8px rgba(0,229,255,0.9)) drop-shadow(0 0 18px rgba(0,100,200,0.5)); animation:aura-pulse 1.6s ease-in-out infinite;';
$amonster ='filter:drop-shadow(0 0 8px rgba(100,0,200,0.9)) drop-shadow(0 0 18px rgba(50,0,100,0.5)); animation:aura-pulse 2s ease-in-out infinite;';
$abasic   =null;
$aanimal  ='filter:drop-shadow(0 0 5px rgba(0,255,136,0.7)) drop-shadow(0 0 12px rgba(0,180,80,0.4)); animation:aura-pulse 2.5s ease-in-out infinite;';

$characters=[
// === BASIC (5) — default style berbeda bentuk ===
[40,'Nova','Karakter default minimalis berbentuk bintang terang','character',0,null,$abasic,'⚫','common'],
[41,'Spark','Karakter default kilatan biru bersih','character',0,null,$abasic,'🔵','common'],
[42,'Orb','Karakter default bola cahaya putih','character',0,null,$abasic,'⚪','common'],
[43,'Pixel','Karakter default gaya pixel retro','character',0,null,$abasic,'🟣','common'],
[44,'Zap','Karakter default listrik kuning ceria','character',0,null,$abasic,'🟡','common'],
// === HALLOWEEN (5) ===
[45,'Pumpkin','Labu halloween berwajah seram','character',500,null,$ahalloween,'🎃','rare'],
[46,'Ghost','Hantu putih menyeramkan','character',550,null,$ahalloween,'👻','rare'],
[47,'Witch','Penyihir bertopi misterius','character',600,null,$ahalloween,'🧙','rare'],
[48,'Skull','Tengkorak horor bergaya','character',650,null,$ahalloween,'💀','epic'],
[49,'Bat','Kelelawar malam halloween','character',500,null,$ahalloween,'🦇','rare'],
// === CUPCAKE (5) ===
[50,'Sweet','Cupcake manis krim putih','character',450,null,$acupcake,'🧁','rare'],
[51,'Choco','Cupcake coklat lezat','character',480,null,$acupcake,'🍫','rare'],
[52,'Strawberry','Cupcake stroberi ceria','character',480,null,$acupcake,'🍓','rare'],
[53,'Rainbow','Cupcake pelangi glitter','character',600,null,$acupcake,'🌈','epic'],
[54,'Candy','Permen lolipop warna-warni','character',450,null,$acupcake,'🍬','rare'],
// === CYBER (5) ===
[55,'Cyber','Robot cyber futuristik','character',700,null,$acyber,'🤖','rare'],
[56,'Neon Fox','Rubah neon hologram keren','character',800,null,$acyber,'🦊','epic'],
[57,'Drone','Drone cyber terbang','character',750,null,$acyber,'🛸','rare'],
[58,'Laser','Laser cyber senjata energi','character',850,null,$acyber,'⚡','epic'],
[59,'Matrix','Makhluk matrix kode digital','character',1000,null,$acyber,'💻','epic'],
// === MONSTER (5) ===
[60,'Slime','Monster lendir hijau menggemaskan','character',550,null,$amonster,'🟢','rare'],
[61,'Dragon','Naga kecil penyembur api','character',800,null,$amonster,'🐉','epic'],
[62,'Demon','Iblis kecil bersayap','character',900,null,$amonster,'😈','epic'],
[63,'Alien','Alien aneh dari luar angkasa','character',700,null,$amonster,'👾','rare'],
[64,'Kraken','Monster laut raksasa mini','character',1000,null,$amonster,'🐙','epic'],
// === ANIMAL (5) — ganti hewan lama ===
[65,'Tiger','Harimau kecil gagah dan berani','character',500,null,$aanimal,'🐯','rare'],
[66,'Wolf','Serigala malam penuh misteri','character',550,null,$aanimal,'🐺','rare'],
[67,'Fox','Rubah licik berwarna oranye','character',500,null,$aanimal,'🦊','rare'],
[68,'Panda','Panda imut hitam putih','character',480,null,$aanimal,'🐼','rare'],
[69,'Dragon Cat','Kucing naga legendaris','character',900,null,$aanimal,'🐱','epic'],
];
foreach($characters as $b){try{$ins->execute($b);$ok++;$log[]=['ok',"Char: ".$b[1]];}catch(PDOException $e){$err++;$log[]=['err',"Char ".$b[1].": ".$e->getMessage()];}}

// ── 5. user_achievements table ────────────────────────────────────────────
q($pdo,"CREATE TABLE IF NOT EXISTS user_achievements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  achievement_id INT NOT NULL,
  unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uua (user_id,achievement_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
)",$ok,$err,$log);

// ── 6. Seed achievements ──────────────────────────────────────────────────
$ai=$pdo->prepare("INSERT IGNORE INTO achievements(name,description,icon,xp_reward,coin_reward,category) VALUES(?,?,?,?,?,?)");
$achs=[
  ['Paling Rajin','Kirim pesan di chat selama 7 hari berturut-turut (1x per hari)','💬',150,75,'social'],
  ['Paling Betah','Habiskan total 100 jam di voice channel','🎙️',500,250,'social'],
  ['Ghost Mode','Login pada pukul 03:00 – 03:59 dini hari','👻',200,100,'special'],
  ['Lunarian 𝟏','Login secara berturut-turut selama 7 hari','🌙',100,50,'streak'],
  ['Lunarian 𝟐','Login secara berturut-turut selama 30 hari','🌕',300,150,'streak'],
  ['Lunarian 𝟑','Login secara berturut-turut selama 100 hari','🌟',1000,500,'streak'],
];
foreach($achs as $a){try{$ai->execute($a);$ok++;$log[]=['ok',"Achievement: ".$a[0]];}catch(PDOException $e){$err++;$log[]=['err',$e->getMessage()];}}

// ── 7. Safe ALTER users & chat_messages ──────────────────────────────────
$userAdd=['login_streak'=>'INT DEFAULT 0','chat_streak'=>'INT DEFAULT 0',
  'last_login_date'=>'DATE DEFAULT NULL','last_chat_date'=>'DATE DEFAULT NULL',
  'voice_minutes'=>'INT DEFAULT 0','frame'=>'VARCHAR(100) DEFAULT NULL',
  'frame_style'=>'TEXT DEFAULT NULL','nameplate'=>'VARCHAR(100) DEFAULT NULL',
  'nameplate_style'=>'TEXT DEFAULT NULL','aura'=>'VARCHAR(100) DEFAULT NULL',
  'bg_image'=>'VARCHAR(255) DEFAULT NULL','bio'=>'TEXT DEFAULT NULL'];
foreach($userAdd as $c=>$d) safeAdd($pdo,'users',$c,$d,$ok,$err,$log);

$chatAdd=['channel'=>"VARCHAR(50) DEFAULT 'ngobrol'",'media_url'=>'VARCHAR(255) DEFAULT NULL'];
foreach($chatAdd as $c=>$d) safeAdd($pdo,'chat_messages',$c,$d,$ok,$err,$log);

// ── Also alter chat_messages.message to allow NULL ─────────────────────
try { $pdo->exec("ALTER TABLE chat_messages MODIFY COLUMN message TEXT NULL"); $ok++; }
catch(Exception $e){ $log[]=['err','modify message: '.$e->getMessage()]; }

$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

// ── Output ────────────────────────────────────────────────────────────────
$errors   = array_filter($log, fn($l)=>$l[0]==='err');
$successes= array_filter($log, fn($l)=>$l[0]==='ok');
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>DB Seed</title>
<style>body{background:#0f111a;color:#e0e0e0;font-family:monospace;padding:24px;} h1{color:#8a2be2;} .ok{color:#00ff88;} .err{color:#ff6060;} pre{background:#13151f;padding:16px;border-radius:10px;font-size:0.82rem;line-height:1.7;max-height:500px;overflow-y:auto;}</style>
</head><body>
<h1>🛠️ DB Seed — Gummy</h1>
<p><strong style="color:#00ff88;"><?= $ok ?> OK</strong> &nbsp; <strong style="color:<?= count($errors)>0?'#ff6060':'#00ff88'; ?>"><?= count($errors) ?> Errors</strong></p>
<pre>
<?php foreach($log as $l): ?>
<span class="<?= $l[0] ?>"><?= $l[0]==='ok'?'✅':'❌' ?> <?= htmlspecialchars($l[1]) ?></span>
<?php endforeach; ?>
</pre>
<?php if(count($errors)===0): ?>
<p style="color:#00ff88;font-weight:bold;">✅ Semua berhasil! Buka <a href="/?page=perks" style="color:#00e5ff;">Cosmetics Shop</a> atau <a href="/?page=achievements" style="color:#8a2be2;">Achievements</a></p>
<?php else: ?>
<p style="color:#ff6060;font-weight:bold;">⚠️ Ada <?= count($errors) ?> error. Cek log di atas.</p>
<?php endif; ?>
<p style="margin-top:20px;color:#ff8080;font-size:0.85rem;">⚠️ <strong>Hapus file ini setelah dijalankan!</strong></p>
</body></html>
