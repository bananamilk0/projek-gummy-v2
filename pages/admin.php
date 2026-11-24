<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ?page=admin-login"); exit;
}
global $pdo;
$success_msg = '';
$error_msg   = '';

// Ensure tables & columns exist
try { 
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_images (id INT AUTO_INCREMENT PRIMARY KEY,image_url VARCHAR(255) NOT NULL,caption VARCHAR(160) DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); 
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_notifications (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, message TEXT NOT NULL, type VARCHAR(50) DEFAULT 'info', is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_read_notifications (user_id INT NOT NULL, notification_id INT NOT NULL, read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (user_id, notification_id))");
    $colCheck = $pdo->query("SHOW COLUMNS FROM news LIKE 'link_url'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE news ADD COLUMN link_url VARCHAR(500) NULL");
    }
} catch (Exception $e) {}

// State Persistence Active Tab
$active_tab = $_POST['active_tab'] ?? $_GET['tab'] ?? $_SESSION['admin_active_tab'] ?? 'settings';
$_SESSION['admin_active_tab'] = $active_tab;

// ── POST Handlers ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Site Settings
    if (isset($_POST['save_settings'])) {
        $stmt = $pdo->prepare("UPDATE site_settings SET setting_value=? WHERE setting_key=?");
        $stmt->execute([trim($_POST['welcome_title']   ?? ''), 'welcome_title']);
        $stmt->execute([trim($_POST['welcome_message'] ?? ''), 'welcome_message']);
        $stmt->execute([trim($_POST['announcement']    ?? ''), 'announcement']);
        $success_msg = "Pengaturan situs berhasil disimpan!";
        $active_tab = 'settings';
    }

    // 2. Post News with URL Link Validation
    elseif (isset($_POST['post_news'])) {
        $ntitle   = trim($_POST['news_title']   ?? '');
        $ncontent = trim($_POST['news_content'] ?? '');
        $nlink    = trim($_POST['news_link']    ?? '');
        $nimg     = '';

        if (!empty($nlink) && !preg_match("~^(?:f|ht)tps?://~i", $nlink)) {
            $nlink = "https://" . $nlink;
        }

        if (!empty($nlink) && !filter_var($nlink, FILTER_VALIDATE_URL)) {
            $error_msg = "Format Link/URL tidak valid. Pastikan diawali dengan http:// atau https://";
        } elseif (!empty($ntitle) && !empty($ncontent)) {
            if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['news_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $dir = __DIR__ . '/../uploads/news/';
                    if (!is_dir($dir)) mkdir($dir, 0777, true);
                    $fn = md5(time().'news').'.'.$ext;
                    if (move_uploaded_file($_FILES['news_image']['tmp_name'], $dir.$fn)) {
                        $nimg = 'uploads/news/'.$fn;
                    }
                }
            }
            try {
                $pdo->prepare("INSERT INTO news (title,content,image_url,link_url,published_by) VALUES (?,?,?,?,?)")->execute([$ntitle, $ncontent, $nimg ?: null, $nlink ?: null, $_SESSION['user_id']]);
                $success_msg = "Berita berhasil diposting!";
            } catch (Exception $e) { $error_msg = "Gagal posting berita: " . $e->getMessage(); }
        } else {
            $error_msg = "Judul dan isi berita wajib diisi.";
        }
        $active_tab = 'news';
    }

    // 3. Upload Gallery
    elseif (isset($_POST['upload_gallery'])) {
        $caption = trim($_POST['gallery_caption'] ?? ''); $file = $_FILES['gallery_image'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) $error_msg = 'Pilih foto gallery terlebih dahulu.';
        else { $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            if (!in_array($ext,['jpg','jpeg','png','gif','webp'])) $error_msg='Format foto tidak didukung.';
            else { $dir=__DIR__.'/../uploads/gallery/'; if(!is_dir($dir)) mkdir($dir,0777,true); $name=bin2hex(random_bytes(12)).'.'.$ext;
                if(move_uploaded_file($file['tmp_name'],$dir.$name)) { $pdo->prepare("INSERT INTO gallery_images (image_url,caption) VALUES (?,?)")->execute(['uploads/gallery/'.$name,$caption ?: null]); $success_msg='Foto gallery ditambahkan.'; }
                else $error_msg='Foto gallery gagal disimpan.';
            }
        }
        $active_tab = 'gallery';
    }
    elseif (isset($_POST['delete_gallery'])) {
        $q=$pdo->prepare('SELECT image_url FROM gallery_images WHERE id=?');$q->execute([(int)$_POST['delete_gallery']]);$row=$q->fetch();
        if($row && is_file(__DIR__.'/../'.$row['image_url'])) @unlink(__DIR__.'/../'.$row['image_url']);
        $pdo->prepare('DELETE FROM gallery_images WHERE id=?')->execute([(int)$_POST['delete_gallery']]); $success_msg='Foto gallery dihapus.';
        $active_tab = 'gallery';
    }

    // 4. Delete News
    elseif (isset($_POST['delete_news'])) {
        $nid = (int)$_POST['delete_news'];
        try {
            $row = $pdo->prepare("SELECT image_url FROM news WHERE id=?");
            $row->execute([$nid]);
            $nr = $row->fetch();
            if ($nr && $nr['image_url'] && file_exists(__DIR__.'/../'.$nr['image_url'])) {
                @unlink(__DIR__.'/../'.$nr['image_url']);
            }
        } catch (Exception $e) {}
        $pdo->prepare("DELETE FROM news WHERE id=?")->execute([$nid]);
        $success_msg = "Berita berhasil dihapus.";
        $active_tab = 'news';
    }

    // 5. Delete Chat Message
    elseif (isset($_POST['delete_chat'])) {
        $pdo->prepare("DELETE FROM chat_messages WHERE id=?")->execute([(int)$_POST['delete_chat']]);
        $success_msg = "Pesan chat berhasil dihapus.";
        $active_tab = 'chat';
    }

    // 6. Delete User
    elseif (isset($_POST['delete_user'])) {
        $del_id = (int)$_POST['delete_user'];
        if ($del_id === (int)$_SESSION['user_id']) {
            $error_msg = "Tidak bisa menghapus akun sendiri!";
        } else {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$del_id]);
            $success_msg = "User berhasil dihapus!";
        }
        $active_tab = 'users';
    }

    // 7. Update User
    elseif (isset($_POST['update_user'])) {
        $uid   = (int)$_POST['usr_id'];
        $role  = $_POST['role'] ?? 'user';
        $coins = min(999999999, max(0, (int)($_POST['coins'] ?? 0)));
        $xp    = min(999999999, max(0, (int)($_POST['xp']    ?? 0)));
        require_once __DIR__ . '/../includes/level_system.php';
        $lvl = calculate_level($xp);
        $pdo->prepare("UPDATE users SET role=?,coins=?,xp=?,level=? WHERE id=?")->execute([$role,$coins,$xp,$lvl,$uid]);

        if ($uid === (int)$_SESSION['user_id']) {
            $_SESSION['xp']    = $xp;
            $_SESSION['level'] = $lvl;
            $_SESSION['coins'] = $coins;
        }
        $success_msg = "Data user diperbarui!";
        $active_tab = 'users';
    }

    // 8. Add Notification
    elseif (isset($_POST['create_notification'])) {
        $ntitle = trim($_POST['notif_title'] ?? '');
        $nmsg   = trim($_POST['notif_message'] ?? '');
        $ntype  = $_POST['notif_type'] ?? 'info';
        if (!empty($ntitle) && !empty($nmsg)) {
            $pdo->prepare("INSERT INTO site_notifications (title, message, type, is_active) VALUES (?, ?, ?, 1)")->execute([$ntitle, $nmsg, $ntype]);
            $success_msg = "Notifikasi baru berhasil dibuat!";
        } else {
            $error_msg = "Judul dan pesan notifikasi wajib diisi.";
        }
        $active_tab = 'notifications';
    }

    // 9. Toggle Notification Status
    elseif (isset($_POST['toggle_notification'])) {
        $nid = (int)$_POST['toggle_notification'];
        $pdo->prepare("UPDATE site_notifications SET is_active = NOT is_active WHERE id = ?")->execute([$nid]);
        $success_msg = "Status notifikasi diperbarui!";
        $active_tab = 'notifications';
    }

    // 10. Delete Notification
    elseif (isset($_POST['delete_notification'])) {
        $nid = (int)$_POST['delete_notification'];
        $pdo->prepare("DELETE FROM site_notifications WHERE id = ?")->execute([$nid]);
        $pdo->prepare("DELETE FROM user_read_notifications WHERE notification_id = ?")->execute([$nid]);
        $success_msg = "Notifikasi berhasil dihapus!";
        $active_tab = 'notifications';
    }

    // 11. Save News Timeframe Setting
    elseif (isset($_POST['save_news_timeframe'])) {
        $tf = (int)($_POST['news_timeframe_hours'] ?? 0);
        $st = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('news_timeframe_hours', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $st->execute([(string)$tf]);
        $success_msg = "Pengaturan rentang waktu berita berhasil disimpan!";
        $active_tab = 'news';
    }
}

$_SESSION['admin_active_tab'] = $active_tab;

// ── Fetch Data ─────────────────────────────────────────────────────────────
$settings = [];
try {
    foreach ($pdo->query("SELECT setting_key,setting_value FROM site_settings")->fetchAll() as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
} catch (Exception $e) {}

$users           = [];
$newsList        = [];
$galleryImages   = [];
$notifications   = [];
$userChatHistory = [];

try { $users          = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(); } catch(Exception $e){}
try { $newsList       = $pdo->query("SELECT n.*,u.username as author FROM news n LEFT JOIN users u ON n.published_by=u.id ORDER BY n.created_at DESC LIMIT 30")->fetchAll(); } catch(Exception $e){}
try { $galleryImages  = $pdo->query("SELECT * FROM gallery_images ORDER BY id DESC")->fetchAll(); } catch(Exception $e){}
try { $notifications  = $pdo->query("SELECT * FROM site_notifications ORDER BY id DESC")->fetchAll(); } catch(Exception $e){}

try {
    $allMsgs = $pdo->query("SELECT c.*, u.username, u.avatar FROM chat_messages c JOIN users u ON c.user_id = u.id ORDER BY c.id ASC")->fetchAll();
    foreach ($allMsgs as $m) {
        $userChatHistory[$m['user_id']][] = $m;
    }
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';

$s  = 'width:100%;padding:11px 13px;background:rgba(0,0,0,0.35);border:1px solid var(--glass-border);border-radius:9px;color:#fff;outline:none;font-family:var(--font-body);font-size:0.87rem;';
$lbl= 'display:block;margin-bottom:7px;font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;';
?>

<div style="max-width:1000px;margin:0 auto;padding:0 0 60px;">

<div style="margin-bottom:32px;">
    <h1 style="font-size:2rem;font-weight:800;color:#fff;margin-bottom:4px;">⚙️ Admin Dashboard</h1>
    <p style="color:var(--text-muted);font-size:0.88rem;margin:0;">Kelola website, berita, pesan, notifikasi, dan pengguna komunitas Gummy.</p>
</div>

<?php if($success_msg): ?>
<div class="admin-alert" style="background:rgba(0,255,136,0.1);border:1px solid rgba(0,255,136,0.3);color:#00ff88;padding:13px 16px;border-radius:10px;margin-bottom:20px;font-size:0.88rem;transition:opacity 0.4s ease, transform 0.4s ease;">
    ✅ <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>
<?php if($error_msg): ?>
<div class="admin-alert" style="background:rgba(255,59,48,0.1);border:1px solid rgba(255,59,48,0.3);color:#ff8080;padding:13px 16px;border-radius:10px;margin-bottom:20px;font-size:0.88rem;transition:opacity 0.4s ease, transform 0.4s ease;">
    ⚠️ <?= htmlspecialchars($error_msg) ?>
</div>
<?php endif; ?>

<!-- Tab Nav -->
<div style="display:flex;gap:8px;margin-bottom:28px;flex-wrap:wrap;" id="admin-tabs">
    <?php
    $tabs = [
        ['settings',      '⚙️ Pengaturan'],
        ['users',         '👥 Members'],
        ['gallery',       '🖼️ Gallery'],
        ['chat',          '💬 Moderasi Chat'],
        ['news',          '📰 Berita'],
        ['notifications', '🔔 Pengaturan Notifikasi'],
    ];
    foreach ($tabs as [$tid, $tlabel]):
        $isAct = ($active_tab === $tid);
    ?>
    <button onclick="showTab('<?= $tid ?>')" id="tab-btn-<?= $tid ?>"
        style="padding:9px 18px;border-radius:10px;border:1px solid var(--glass-border);background:<?= $isAct ? 'rgba(138,43,226,0.25)' : 'rgba(255,255,255,0.05)' ?>;color:<?= $isAct ? '#fff' : 'var(--text-muted)' ?>;cursor:pointer;font-size:0.85rem;font-weight:600;transition:all 0.2s;">
        <?= $tlabel ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- ═══ TAB: SETTINGS ═══ -->
<div id="tab-settings" class="admin-tab" style="<?= $active_tab==='settings'?'':'display:none;' ?>">
    <div class="glass-panel" style="padding:28px;border:1px solid var(--glass-border);">
        <h3 style="margin:0 0 22px;color:#fff;font-size:1.1rem;font-weight:700;">⚙️ Pengaturan Situs</h3>
        <form method="POST" style="display:flex;flex-direction:column;gap:16px;">
            <input type="hidden" name="active_tab" class="active-tab-input" value="settings">
            <div>
                <label style="<?= $lbl ?>">Welcome Title</label>
                <input type="text" name="welcome_title" value="<?= htmlspecialchars($settings['welcome_title'] ?? '') ?>" style="<?= $s ?>">
            </div>
            <div>
                <label style="<?= $lbl ?>">Welcome Message</label>
                <textarea name="welcome_message" rows="3" style="<?= $s ?>resize:vertical;"><?= htmlspecialchars($settings['welcome_message'] ?? '') ?></textarea>
            </div>
            <div>
                <label style="<?= $lbl ?>">Announcement Banner (Topbar)</label>
                <input type="text" name="announcement" value="<?= htmlspecialchars($settings['announcement'] ?? '') ?>" style="<?= $s ?>">
            </div>
            <button type="submit" name="save_settings" class="btn-primary" style="padding:12px 28px;font-size:0.9rem;align-self:flex-start;">
                Simpan Perubahan
            </button>
        </form>
    </div>
</div>

<!-- ═══ TAB: USER MANAGEMENT (MEMBERS) ═══ -->
<div id="tab-users" class="admin-tab" style="<?= $active_tab==='users'?'':'display:none;' ?>">
    <div class="glass-panel" style="padding:28px;border:1px solid var(--glass-border);">
        <h3 style="margin:0 0 20px;color:#fff;font-size:1.1rem;font-weight:700;">👥 Manajemen Members (<?= count($users) ?>)</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;text-align:left;font-size:0.84rem;">
                <thead>
                    <tr style="border-bottom:2px solid var(--glass-border);color:var(--text-muted);">
                        <th style="padding:10px 8px;">User</th>
                        <th style="padding:10px 8px;">Role</th>
                        <th style="padding:10px 8px;">XP</th>
                        <th style="padding:10px 8px;">Koin</th>
                        <th style="padding:10px 8px;text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $usr): ?>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                        <td style="padding:10px 8px;">
                            <div style="display:flex;align-items:center;gap:9px;">
                                <img src="uploads/avatars/<?= htmlspecialchars($usr['avatar'] ?? 'default.png') ?>" style="width:30px;height:30px;border-radius:50%;object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($usr['username']) ?>&size=30'">
                                <span style="color:#fff;font-weight:600;"><?= htmlspecialchars($usr['username']) ?></span>
                            </div>
                        </td>
                        <form method="POST">
                            <input type="hidden" name="active_tab" class="active-tab-input" value="users">
                            <input type="hidden" name="usr_id" value="<?= $usr['id'] ?>">
                            <td style="padding:10px 8px;">
                                <select name="role" style="background:rgba(0,0,0,0.5);color:#fff;border:1px solid var(--glass-border);border-radius:6px;padding:5px 8px;font-size:0.8rem;">
                                    <option value="user"      <?= $usr['role']==='user'      ?'selected':'' ?>>User</option>
                                    <option value="moderator" <?= $usr['role']==='moderator' ?'selected':'' ?>>Moderator</option>
                                    <option value="admin"     <?= $usr['role']==='admin'     ?'selected':'' ?>>Admin</option>
                                </select>
                            </td>
                            <td style="padding:10px 8px;"><input type="number" name="xp" min="0" max="999999999" value="<?= (int)$usr['xp'] ?>" style="width:95px;<?= $s ?>padding:5px 8px;"></td>
                            <td style="padding:10px 8px;"><input type="number" name="coins" min="0" max="999999999" value="<?= (int)$usr['coins'] ?>" style="width:95px;<?= $s ?>padding:5px 8px;"></td>
                            <td style="padding:10px 8px;text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                                    <button type="submit" name="update_user" style="background:rgba(138,43,226,0.2);border:1px solid rgba(138,43,226,0.4);color:#c080ff;padding:6px 12px;border-radius:7px;cursor:pointer;font-size:0.78rem;">Simpan</button>
                        </form>
                                    <?php if ($usr['id'] !== (int)$_SESSION['user_id']): ?>
                                    <form method="POST" onsubmit="return confirm('Hapus user ini?')">
                                        <input type="hidden" name="active_tab" class="active-tab-input" value="users">
                                        <input type="hidden" name="delete_user" value="<?= $usr['id'] ?>">
                                        <button type="submit" style="background:rgba(255,59,48,0.15);border:1px solid rgba(255,59,48,0.3);color:#ff8080;padding:6px 10px;border-radius:7px;cursor:pointer;font-size:0.78rem;">Hapus</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══ TAB: GALLERY ═══ -->
<div id="tab-gallery" class="admin-tab" style="<?= $active_tab==='gallery'?'':'display:none;' ?>">
    <div class="glass-panel" style="padding:28px;border:1px solid var(--glass-border);margin-bottom:20px;">
        <h3 style="margin:0 0 18px;color:#fff;">🖼️ Gallery Home</h3>
        <form method="POST" enctype="multipart/form-data" style="display:flex;gap:12px;flex-wrap:wrap;">
            <input type="hidden" name="active_tab" class="active-tab-input" value="gallery">
            <input required type="file" name="gallery_image" accept="image/*" style="<?= $s ?>flex:1;min-width:200px;">
            <input type="text" name="gallery_caption" placeholder="Caption (opsional)" style="<?= $s ?>flex:1;min-width:180px;">
            <button class="btn-primary" name="upload_gallery" type="submit">Tambah Foto</button>
        </form>
    </div>

    <!-- Gallery Flex Wrap Container -->
    <div id="admin-gallery-container" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
        <?php foreach($galleryImages as $index => $image): 
            $isHidden = ($index >= 12);
        ?>
        <div class="glass-panel admin-gallery-card <?= $isHidden ? 'admin-gallery-hidden' : '' ?>" style="padding:12px; flex:0 0 auto; max-width:240px; <?= $isHidden ? 'display:none;' : '' ?>">
            <div style="background:#0a0d12; border-radius:8px; overflow:hidden; display:flex; align-items:center; justify-content:center; padding:4px;">
                <img src="<?= htmlspecialchars($image['image_url']) ?>" 
                     onclick="openGalleryLightbox('<?= htmlspecialchars($image['image_url']) ?>', '<?= htmlspecialchars(addslashes($image['caption'] ?? '')) ?>')"
                     title="Klik untuk memperbesar"
                     style="max-width:210px; max-height:220px; width:auto; height:auto; display:block; border-radius:6px; cursor:pointer; transition:transform 0.2s;"
                     onmouseenter="this.style.transform='scale(1.03)'" onmouseleave="this.style.transform='scale(1)'">
            </div>
            <p style="margin:10px 0 6px; color:#ddd; font-size:.82rem; word-break:break-word;"><?= htmlspecialchars($image['caption'] ?: 'Tanpa caption') ?></p>
            <form method="POST" onsubmit="return confirm('Hapus foto gallery ini?')">
                <input type="hidden" name="active_tab" class="active-tab-input" value="gallery">
                <button name="delete_gallery" value="<?= $image['id'] ?>" style="color:#ff8080; background:rgba(255,59,48,0.1); border:1px solid rgba(255,59,48,0.25); padding:4px 10px; border-radius:6px; cursor:pointer; font-size:0.75rem;">Hapus</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($galleryImages) > 12): ?>
    <div style="text-align:center; margin-top:20px;">
        <button id="btn-gallery-more" onclick="toggleGalleryMore()" class="btn-secondary" style="padding:10px 24px; font-size:0.85rem;">
            Lihat Lainnya (<?= count($galleryImages) - 12 ?> foto lagi)
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- ═══ TAB: CHAT MODERATION ACCORDION PER USER ═══ -->
<div id="tab-chat" class="admin-tab" style="<?= $active_tab==='chat'?'':'display:none;' ?>">
    <div class="glass-panel" style="padding:28px;border:1px solid var(--glass-border);">
        <h3 style="margin:0 0 8px;color:#fff;font-size:1.1rem;font-weight:700;">💬 Moderasi Chat (Accordion Per User)</h3>
        <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:20px;">Klik nama user untuk melihat dan memoderasi seluruh riwayat chat yang dikirim oleh user tersebut.</p>

        <?php if (empty($users)): ?>
            <p style="color:var(--text-muted);text-align:center;padding:30px 0;margin:0;">Belum ada user terdaftar.</p>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($users as $usr): 
                    $uMsgs = $userChatHistory[$usr['id']] ?? [];
                ?>
                <div class="glass-panel" style="padding:0; overflow:hidden; border:1px solid var(--glass-border); border-radius:12px; background:rgba(20,25,35,0.4);">
                    <div onclick="toggleUserAccordion(<?= $usr['id'] ?>)" style="padding:14px 18px; background:rgba(255,255,255,0.03); cursor:pointer; display:flex; align-items:center; justify-content:space-between; user-select:none; transition:background 0.2s;" onmouseenter="this.style.background='rgba(255,255,255,0.06)'" onmouseleave="this.style.background='rgba(255,255,255,0.03)'">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="uploads/avatars/<?= htmlspecialchars($usr['avatar'] ?? 'default.png') ?>" style="width:34px; height:34px; border-radius:50%; object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($usr['username']) ?>&size=34'">
                            <div>
                                <strong style="color:#fff; font-size:0.9rem;"><?= htmlspecialchars($usr['username']) ?></strong>
                                <span style="color:var(--text-muted); font-size:0.75rem; margin-left:8px; text-transform:uppercase;">[<?= htmlspecialchars($usr['role']) ?>]</span>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span style="background:rgba(93,219,133,0.15); color:var(--primary); font-size:0.75rem; padding:4px 10px; border-radius:12px; font-weight:600;">
                                <?= count($uMsgs) ?> Pesan
                            </span>
                            <i data-lucide="chevron-down" id="acc-icon-<?= $usr['id'] ?>" style="width:18px; height:18px; color:var(--text-muted); transition:transform 0.3s;"></i>
                        </div>
                    </div>
                    
                    <div id="acc-body-<?= $usr['id'] ?>" style="display:none; padding:16px; border-top:1px solid rgba(255,255,255,0.05); background:rgba(0,0,0,0.25);">
                        <?php if (empty($uMsgs)): ?>
                            <p style="color:var(--text-muted); text-align:center; padding:15px 0; margin:0; font-size:0.85rem;">Belum ada pesan yang tercatat dari user ini.</p>
                        <?php else: ?>
                            <div style="max-height:320px; overflow-y:auto; padding-right:6px; display:flex; flex-direction:column; gap:10px;">
                                <?php foreach ($uMsgs as $cm): ?>
                                    <div style="display:flex; align-items:flex-start; gap:12px; padding:10px 12px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-radius:8px;">
                                        <div style="flex-grow:1; min-width:0;">
                                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                                                <span style="background:rgba(138,43,226,0.2); color:var(--primary); font-size:0.68rem; padding:2px 7px; border-radius:8px;">#<?= htmlspecialchars($cm['channel']) ?></span>
                                                <span style="color:var(--text-muted); font-size:0.74rem;"><?= date('d M Y H:i', strtotime($cm['created_at'])) ?></span>
                                            </div>
                                            <?php if ($cm['message']): ?>
                                                <p style="margin:0; color:#ccc; font-size:0.85rem; line-height:1.4; word-break:break-word;"><?= htmlspecialchars($cm['message']) ?></p>
                                            <?php endif; ?>
                                            <?php if ($cm['media_url']): ?>
                                                <img src="<?= htmlspecialchars($cm['media_url']) ?>" style="max-height:70px; border-radius:6px; margin-top:6px;" onerror="this.style.display='none'">
                                            <?php endif; ?>
                                        </div>
                                        <form method="POST" onsubmit="return confirm('Hapus pesan ini?')">
                                            <input type="hidden" name="active_tab" class="active-tab-input" value="chat">
                                            <input type="hidden" name="delete_chat" value="<?= $cm['id'] ?>">
                                            <button type="submit" style="background:rgba(255,59,48,0.12); border:1px solid rgba(255,59,48,0.25); color:#ff8080; padding:4px 8px; border-radius:6px; cursor:pointer; font-size:0.75rem;">
                                                <i data-lucide="trash-2" style="width:12px;height:12px;"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ TAB: NEWS ═══ -->
<div id="tab-news" class="admin-tab" style="<?= $active_tab==='news'?'':'display:none;' ?>">
    <!-- News Timeframe Settings Panel -->
    <div class="glass-panel" style="padding:24px;border:1px solid var(--glass-border);margin-bottom:24px;background:rgba(255,255,255,0.02);">
        <h3 style="margin:0 0 14px;color:#fff;font-size:1.05rem;font-weight:700;display:flex;align-items:center;gap:8px;">
            <span>⏱️ Rentang Waktu Tampil Berita</span>
        </h3>
        <p style="color:var(--text-muted);font-size:0.85rem;margin:0 0 16px;">Tentukan durasi/rentang waktu berita yang ditampilkan kepada user di frontend. Berita yang diposting di luar rentang waktu ini akan disembunyikan otomatis.</p>
        <form method="POST" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <input type="hidden" name="active_tab" class="active-tab-input" value="news">
            <div style="flex:1;min-width:240px;">
                <?php $curTf = (int)($settings['news_timeframe_hours'] ?? 0); ?>
                <select name="news_timeframe_hours" style="<?= $s ?>">
                    <option value="0" <?= $curTf === 0 ? 'selected' : '' ?>>🌐 Tanpa Batas Waktu (Tampilkan Selamanya)</option>
                    <option value="1" <?= $curTf === 1 ? 'selected' : '' ?>>⚡ 1 Jam Terakhir</option>
                    <option value="2" <?= $curTf === 2 ? 'selected' : '' ?>>⏳ 2 Jam Terakhir</option>
                    <option value="6" <?= $curTf === 6 ? 'selected' : '' ?>>🕒 6 Jam Terakhir</option>
                    <option value="12" <?= $curTf === 12 ? 'selected' : '' ?>>🕛 12 Jam Terakhir</option>
                    <option value="24" <?= $curTf === 24 ? 'selected' : '' ?>>📅 24 Jam Terakhir (1 Hari)</option>
                    <option value="48" <?= $curTf === 48 ? 'selected' : '' ?>>🗓️ 48 Jam Terakhir (2 Hari)</option>
                    <option value="168" <?= $curTf === 168 ? 'selected' : '' ?>>🗓️ 7 Hari Terakhir (1 Minggu)</option>
                    <option value="720" <?= $curTf === 720 ? 'selected' : '' ?>>📆 30 Hari Terakhir (1 Bulan)</option>
                </select>
            </div>
            <button type="submit" name="save_news_timeframe" class="btn-primary" style="padding:11px 22px;font-size:0.88rem;">
                Simpan Rentang Waktu
            </button>
        </form>
    </div>

    <!-- Post News Form -->
    <div class="glass-panel" style="padding:28px;border:1px solid var(--glass-border);margin-bottom:24px;">
        <h3 style="margin:0 0 22px;color:#fff;font-size:1.1rem;font-weight:700;">📰 Posting Berita Baru</h3>
        <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:16px;">
            <input type="hidden" name="active_tab" class="active-tab-input" value="news">
            <div>
                <label style="<?= $lbl ?>">Foto Berita (Opsional — tampil dengan rasio asli)</label>
                <label id="news-img-label" style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:rgba(0,0,0,0.3);border:1px dashed var(--glass-border);border-radius:10px;cursor:pointer;transition:border-color 0.2s;" onmouseenter="this.style.borderColor='var(--primary)'" onmouseleave="this.style.borderColor='var(--glass-border)'">
                    <img id="news-img-preview" src="" alt="" style="display:none;height:60px;border-radius:6px;object-fit:contain;max-width:120px;">
                    <i data-lucide="image" id="news-img-icon" style="width:18px;height:18px;color:var(--text-muted);"></i>
                    <span id="news-img-text" style="color:var(--text-muted);font-size:0.85rem;">Klik untuk pilih foto (jpg/png/webp)</span>
                    <input type="file" name="news_image" accept="image/*" style="display:none;" onchange="previewNewsImg(this)">
                </label>
            </div>
            <div>
                <label style="<?= $lbl ?>">Judul Berita</label>
                <input type="text" name="news_title" required placeholder="Masukkan judul berita..." style="<?= $s ?>">
            </div>
            <div>
                <label style="<?= $lbl ?>">Link / URL Eksternal (Opsional — misal: https://example.com)</label>
                <input type="url" name="news_link" placeholder="https://..." style="<?= $s ?>">
            </div>
            <div>
                <label style="<?= $lbl ?>">Isi Berita</label>
                <textarea name="news_content" rows="6" required placeholder="Tulis isi berita di sini..." style="<?= $s ?>resize:vertical;"></textarea>
            </div>
            <button type="submit" name="post_news" class="btn-primary" style="padding:12px 28px;font-size:0.9rem;display:inline-flex;align-items:center;gap:8px;align-self:flex-start;">
                <i data-lucide="send" style="width:16px;height:16px;"></i> Posting Berita
            </button>
        </form>
    </div>

    <!-- Existing News List -->
    <div class="glass-panel" style="padding:28px;border:1px solid var(--glass-border);">
        <h3 style="margin:0 0 20px;color:#fff;font-size:1.1rem;font-weight:700;">Berita Terpublikasi (<?= count($newsList) ?>)</h3>
        <?php if (empty($newsList)): ?>
            <p style="color:var(--text-muted);text-align:center;padding:30px 0;margin:0;">Belum ada berita. Post berita pertama di atas!</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <?php foreach ($newsList as $n): ?>
            <div style="display:flex;align-items:flex-start;gap:14px;padding:16px;background:rgba(255,255,255,0.025);border:1px solid rgba(255,255,255,0.06);border-radius:12px;">
                <?php if ($n['image_url']): ?>
                    <img src="<?= htmlspecialchars($n['image_url']) ?>" style="max-width:110px;max-height:80px;object-fit:contain;border-radius:8px;flex-shrink:0;background:rgba(0,0,0,0.3);" onerror="this.style.display='none'">
                <?php endif; ?>
                <div style="flex-grow:1;min-width:0;">
                    <p style="margin:0 0 4px;color:#fff;font-weight:700;font-size:0.92rem;"><?= htmlspecialchars($n['title']) ?></p>
                    <?php if(!empty($n['link_url'])): ?>
                        <p style="margin:0 0 6px;font-size:0.78rem;">
                            <a href="<?= htmlspecialchars($n['link_url']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--primary);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                                🔗 <?= htmlspecialchars($n['link_url']) ?> <i data-lucide="external-link" style="width:12px;height:12px;"></i>
                            </a>
                        </p>
                    <?php endif; ?>
                    <p style="margin:0 0 6px;color:var(--text-muted);font-size:0.8rem;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"><?= htmlspecialchars($n['content']) ?></p>
                    <p style="margin:0;font-size:0.74rem;color:var(--text-muted);">oleh <strong style="color:#ccc;"><?= htmlspecialchars($n['author'] ?? 'Admin') ?></strong> · <?= date('d M Y H:i', strtotime($n['created_at'])) ?></p>
                </div>
                <form method="POST" onsubmit="return confirm('Hapus berita ini?')" style="flex-shrink:0;">
                    <input type="hidden" name="active_tab" class="active-tab-input" value="news">
                    <input type="hidden" name="delete_news" value="<?= $n['id'] ?>">
                    <button type="submit" style="background:rgba(255,59,48,0.15);border:1px solid rgba(255,59,48,0.3);color:#ff8080;padding:7px 12px;border-radius:8px;cursor:pointer;font-size:0.78rem;display:flex;align-items:center;gap:5px;">
                        <i data-lucide="trash-2" style="width:13px;height:13px;"></i> Hapus
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ TAB: NOTIFICATION SETTINGS ═══ -->
<div id="tab-notifications" class="admin-tab" style="<?= $active_tab==='notifications'?'':'display:none;' ?>">
    <!-- Create Notification Form -->
    <div class="glass-panel" style="padding:28px;border:1px solid var(--glass-border);margin-bottom:24px;">
        <h3 style="margin:0 0 22px;color:#fff;font-size:1.1rem;font-weight:700;">🔔 Buat Notifikasi Baru</h3>
        <form method="POST" style="display:flex;flex-direction:column;gap:16px;">
            <input type="hidden" name="active_tab" class="active-tab-input" value="notifications">
            <div>
                <label style="<?= $lbl ?>">Judul Notifikasi</label>
                <input type="text" name="notif_title" required placeholder="Judul notifikasi..." style="<?= $s ?>">
            </div>
            <div>
                <label style="<?= $lbl ?>">Tipe Notifikasi</label>
                <select name="notif_type" style="<?= $s ?>">
                    <option value="info">Info (Hijau Muda)</option>
                    <option value="success">Sukses (Matcha Green)</option>
                    <option value="warning">Peringatan (Oranye)</option>
                </select>
            </div>
            <div>
                <label style="<?= $lbl ?>">Pesan / Isi Notifikasi</label>
                <textarea name="notif_message" rows="3" required placeholder="Tulis isi pesan notifikasi untuk user..." style="<?= $s ?>resize:vertical;"></textarea>
            </div>
            <button type="submit" name="create_notification" class="btn-primary" style="padding:12px 28px;font-size:0.9rem;align-self:flex-start;">
                Kirim / Simpan Notifikasi
            </button>
        </form>
    </div>

    <!-- Existing Notifications List -->
    <div class="glass-panel" style="padding:28px;border:1px solid var(--glass-border);">
        <h3 style="margin:0 0 20px;color:#fff;font-size:1.1rem;font-weight:700;">Daftar Pengaturan Notifikasi (<?= count($notifications) ?>)</h3>
        <?php if (empty($notifications)): ?>
            <p style="color:var(--text-muted);text-align:center;padding:30px 0;margin:0;">Kondisi awal notifikasi kosong. Buat notifikasi pertama di atas!</p>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($notifications as $notif): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:rgba(255,255,255,0.025);border:1px solid rgba(255,255,255,0.06);border-radius:12px;gap:14px;">
                    <div style="flex-grow:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                            <strong style="color:#fff;font-size:0.92rem;"><?= htmlspecialchars($notif['title']) ?></strong>
                            <span style="font-size:0.7rem;padding:2px 8px;border-radius:10px;background:<?= $notif['is_active'] ? 'rgba(0,255,136,0.15)' : 'rgba(255,255,255,0.1)' ?>;color:<?= $notif['is_active'] ? '#00ff88' : 'var(--text-muted)' ?>;font-weight:bold;">
                                <?= $notif['is_active'] ? 'AKTIF' : 'NONAKTIF' ?>
                            </span>
                        </div>
                        <p style="margin:0;color:var(--text-muted);font-size:0.83rem;white-space:pre-line;"><?= htmlspecialchars($notif['message']) ?></p>
                        <small style="color:rgba(255,255,255,0.35);font-size:0.7rem;"><?= date('d M Y H:i', strtotime($notif['created_at'])) ?></small>
                    </div>
                    <div style="display:flex;gap:8px;flex-shrink:0;">
                        <form method="POST">
                            <input type="hidden" name="active_tab" class="active-tab-input" value="notifications">
                            <input type="hidden" name="toggle_notification" value="<?= $notif['id'] ?>">
                            <button type="submit" style="background:rgba(255,255,255,0.08);border:1px solid var(--glass-border);color:#fff;padding:6px 12px;border-radius:8px;cursor:pointer;font-size:0.78rem;">
                                <?= $notif['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                            </button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Hapus notifikasi ini?')">
                            <input type="hidden" name="active_tab" class="active-tab-input" value="notifications">
                            <input type="hidden" name="delete_notification" value="<?= $notif['id'] ?>">
                            <button type="submit" style="background:rgba(255,59,48,0.15);border:1px solid rgba(255,59,48,0.3);color:#ff8080;padding:6px 12px;border-radius:8px;cursor:pointer;font-size:0.78rem;">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div><!-- end max-width wrapper -->

<!-- Lightbox Modal for Gallery Images -->
<div id="gallery-lightbox" onclick="closeGalleryLightbox()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(8px); padding:20px;">
    <div onclick="event.stopPropagation()" style="position:relative; max-width:90vw; max-height:90vh; text-align:center;">
        <button onclick="closeGalleryLightbox()" style="position:absolute; top:-40px; right:0; background:rgba(255,255,255,0.2); border:none; color:#fff; width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:1.2rem; display:flex; align-items:center; justify-content:center;">✕</button>
        <img id="lightbox-img" src="" style="max-width:90vw; max-height:80vh; object-fit:contain; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.8); border:1px solid var(--glass-border);">
        <p id="lightbox-caption" style="margin-top:12px; color:#e0e0e0; font-size:0.95rem; font-weight:600;"></p>
    </div>
</div>

<script>
function showTab(id) {
    document.querySelectorAll('.admin-tab').forEach(t => t.style.display = 'none');
    const target = document.getElementById('tab-' + id);
    if (target) target.style.display = 'block';

    document.querySelectorAll('#admin-tabs button').forEach(b => {
        b.style.background = 'rgba(255,255,255,0.05)';
        b.style.color = 'var(--text-muted)';
        b.style.borderColor = 'var(--glass-border)';
    });
    const activeBtn = document.getElementById('tab-btn-' + id);
    if (activeBtn) {
        activeBtn.style.background = 'rgba(138,43,226,0.25)';
        activeBtn.style.color = '#fff';
        activeBtn.style.borderColor = 'rgba(138,43,226,0.5)';
    }

    // Preserve active tab state across interactions
    localStorage.setItem('admin_active_tab', id);
    document.querySelectorAll('input.active-tab-input').forEach(inp => inp.value = id);
}

function toggleUserAccordion(userId) {
    const body = document.getElementById('acc-body-' + userId);
    const icon = document.getElementById('acc-icon-' + userId);
    if (body) {
        const isHidden = body.style.display === 'none' || !body.style.display;
        body.style.display = isHidden ? 'block' : 'none';
        if (icon) icon.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
    }
}

function toggleGalleryMore() {
    const hiddenItems = document.querySelectorAll('.admin-gallery-hidden');
    const btn = document.getElementById('btn-gallery-more');
    let isShowing = false;
    hiddenItems.forEach(el => {
        if (el.style.display === 'none' || !el.style.display) {
            el.style.display = 'block';
            isShowing = true;
        } else {
            el.style.display = 'none';
        }
    });
    if (btn) btn.textContent = isShowing ? 'Sembunyikan' : 'Lihat Lainnya';
}

function openGalleryLightbox(url, caption) {
    const lb = document.getElementById('gallery-lightbox');
    const img = document.getElementById('lightbox-img');
    const cap = document.getElementById('lightbox-caption');
    if (lb && img) {
        img.src = url;
        if (cap) cap.textContent = caption || '';
        lb.style.display = 'flex';
    }
}

function closeGalleryLightbox() {
    const lb = document.getElementById('gallery-lightbox');
    if (lb) lb.style.display = 'none';
}

function previewNewsImg(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('news-img-preview');
        const icon = document.getElementById('news-img-icon');
        const text = document.getElementById('news-img-text');
        prev.src = e.target.result;
        prev.style.display = 'block';
        icon.style.display = 'none';
        text.textContent = input.files[0].name;
    };
    reader.readAsDataURL(input.files[0]);
}

document.addEventListener('DOMContentLoaded', () => {
    const phpTab = <?= json_encode($active_tab) ?>;
    const storedTab = localStorage.getItem('admin_active_tab');
    const targetTab = phpTab || storedTab || 'settings';
    showTab(targetTab);

    // Auto dismiss admin alert after 4 seconds
    document.querySelectorAll('.admin-alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 400);
        }, 4000);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
