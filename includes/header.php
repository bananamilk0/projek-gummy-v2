<?php
global $pdo;
if (isset($pdo)) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) DEFAULT 'info',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_read_notifications (
            user_id INT NOT NULL,
            notification_id INT NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, notification_id)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NULL
        )");
        $defaultSettings = [
            'news_enabled' => '1',
            'news_timeframe_hours' => '0'
        ];
        $stSetting = $pdo->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaultSettings as $k => $v) {
            $stSetting->execute([$k, $v]);
        }
        $colCheck = $pdo->query("SHOW COLUMNS FROM news LIKE 'link_url'")->fetch();
        if (!$colCheck) {
            $pdo->exec("ALTER TABLE news ADD COLUMN link_url VARCHAR(500) NULL");
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Gummy - Gaming Clubhouse</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <link rel="stylesheet" href="assets/css/chat.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/chat.css') ?>">
    <style>
    /* ── Classes ────────────────────────────────────────────────────────── */
    .avatar-cosmetic-effect .equipped-border { position:relative; z-index:3; }
    .nameplate-preview { position:relative; overflow:visible; }

    /* Universal PNG Border Overlay System */
    .png-border-overlay {
        position: absolute;
        inset: -14px;
        background-size: contain;
        background-position: center;
        background-repeat: no-repeat;
        pointer-events: none;
        z-index: 5;
        filter: drop-shadow(0 0 8px rgba(0, 0, 0, 0.6));
        animation: pngBorderPulse 2.5s ease-in-out infinite alternate;
    }
    @keyframes pngBorderPulse {
        0%   { transform: scale(1); }
        100% { transform: scale(1.03); }
    }

    #game-container:fullscreen,
    #game-container:fullscreen *,
    #game-container:-webkit-full-screen,
    #game-container:-webkit-full-screen *,
    #game-container:-moz-full-screen,
    #game-container:-moz-full-screen * {
        cursor: default !important;
    }
    :fullscreen .custom-cursor, :fullscreen .custom-cursor-follower { display:none !important; }
    :-webkit-full-screen .custom-cursor, :-webkit-full-screen .custom-cursor-follower { display:none !important; }

    body.game-active { overflow:hidden; }
    </style>
    
    <!-- GSAP & Lenis -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.32/dist/lenis.min.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

    <!-- Custom Cursor -->
    <div class="custom-cursor"></div>
    <div class="custom-cursor-follower"></div>

    <!-- Navigation Sidebar / Mobile Bottom Nav -->
    <?php 
    $currPage = $_GET['page'] ?? 'home';
    $usrAvatar = 'default.png';
    $usrName = 'User';
    if(isset($_SESSION['user_id'])) {
        $usrStmt = $pdo->prepare("SELECT avatar, username FROM users WHERE id = ?");
        $usrStmt->execute([$_SESSION['user_id']]);
        $usrRow = $usrStmt->fetch();
        $usrAvatar = $usrRow['avatar'] ?? 'default.png';
        $usrName = $usrRow['username'] ?? $_SESSION['username'] ?? 'User';
    }
    ?>
    <nav class="sidebar glass-panel">
        <ul class="nav-links">
            <li><a href="?page=home" class="<?= $currPage==='home'?'active':'' ?>"><i data-lucide="house"></i><span class="nav-label"> Home</span></a></li>
            <li><a href="?page=chat" class="<?= $currPage==='chat'?'active':'' ?>"><i data-lucide="message-circle"></i><span class="nav-label"> Chat</span></a></li>
            <li><a href="?page=games" class="<?= $currPage==='games'?'active':'' ?>"><i data-lucide="gamepad-2"></i><span class="nav-label"> Games</span></a></li>
            <li><a href="?page=perks" class="<?= $currPage==='perks'?'active':'' ?>"><i data-lucide="shopping-cart"></i><span class="nav-label"> Shop</span></a></li>
            <li><a href="?page=members" class="<?= $currPage==='members'?'active':'' ?>"><i data-lucide="users-round"></i><span class="nav-label"> Members</span></a></li>
            <li><a href="?page=achievements" class="<?= $currPage==='achievements'?'active':'' ?>"><i data-lucide="trophy"></i><span class="nav-label"> Achievements</span></a></li>
            <li><a href="?page=news" class="<?= $currPage==='news'?'active':'' ?>"><i data-lucide="newspaper"></i><span class="nav-label"> News</span></a></li>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="?page=admin" class="<?= $currPage==='admin'?'active':'' ?>"><i data-lucide="shield-check"></i><span class="nav-label"> Admin</span></a></li>
            <?php endif; ?>
        </ul>
        
        <div class="auth-section">
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="auth-user-wrap">
                    <a href="?page=profile" class="auth-profile-link" title="Profil Saya: <?= htmlspecialchars($usrName) ?>">
                        <div class="auth-avatar-box">
                            <img src="uploads/avatars/<?= htmlspecialchars($usrAvatar) ?>"
                                 class="auth-avatar-img"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($usrName) ?>&size=36&background=2d5a38&color=fff'">
                            <span class="auth-status-dot"></span>
                        </div>
                        <span class="nav-label auth-username-text"><?= htmlspecialchars($usrName) ?></span>
                    </a>
                    <a href="?page=logout" class="auth-logout-btn" title="Keluar">
                        <i data-lucide="log-out"></i>
                    </a>
                </div>
            <?php else: ?>
                <a href="?page=login" class="sidebar-login-link" title="Login">
                    <div class="sidebar-login-icon-box">
                        <i data-lucide="log-in"></i>
                    </div>
                    <span class="nav-label sidebar-login-text">Login</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <script>
    // Prevent sidebar collapse during menu navigation & preserve hover expand state
    (function() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        if (sessionStorage.getItem('sidebarHovered') === 'true') {
            sidebar.classList.add('sidebar-nav-expanded');
        }

        sidebar.addEventListener('mouseenter', () => {
            sessionStorage.setItem('sidebarHovered', 'true');
        });

        sidebar.addEventListener('mouseleave', () => {
            sessionStorage.removeItem('sidebarHovered');
            sidebar.classList.remove('sidebar-nav-expanded');
        });

        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                sessionStorage.setItem('sidebarHovered', 'true');
            });
        });
    })();
    </script>

    <!-- Main Content Wrapper -->
    <main id="smooth-wrapper">
        <div id="smooth-content">
            <!-- Topbar (Currency, Notifications, Mobile Brand) -->
            <header class="topbar glass-panel">
                <div class="topbar-left">
                    <button type="button" class="mobile-menu-trigger" onclick="toggleMobileDrawer(true)" aria-label="Buka Menu">
                        <i data-lucide="menu"></i>
                    </button>
                    <a href="?page=home" class="topbar-brand">
                        <span class="brand-dot"></span>
                        <span class="brand-name">Gummy</span>
                    </a>
                </div>
                
                <?php 
                $ann = '';
                if(isset($pdo)) {
                    $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'announcement'");
                    if($row = $stmt->fetch()) $ann = $row['setting_value'];
                }
                if($ann):
                ?>
                <div class="announcement-banner">
                    <i data-lucide="info" style="width: 15px; height: 15px; vertical-align: middle; flex-shrink: 0;"></i> 
                    <span class="announcement-text"><?= htmlspecialchars($ann) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="user-stats">
                    <?php if(isset($_SESSION['user_id'])): 
                        require_once __DIR__ . '/level_system.php';
                        $xp = $_SESSION['xp'] ?? 0;
                        $level = $_SESSION['level'] ?? 1;
                        $coins = $_SESSION['coins'] ?? 0;
                        
                        $current_level_xp = get_xp_for_level($level);
                        $next_level_xp = get_xp_for_level($level + 1);
                        $xp_progress = $xp - $current_level_xp;
                        $xp_needed = $next_level_xp - $current_level_xp;
                        $percent = min(100, max(0, ($xp_progress / $xp_needed) * 100));

                        // Query active notifications and read status
                        $activeNotifs = [];
                        $readNotifIds = [];
                        if (isset($pdo)) {
                            try {
                                $nStmt = $pdo->query("SELECT * FROM site_notifications WHERE is_active = 1 ORDER BY id DESC LIMIT 20");
                                $activeNotifs = $nStmt->fetchAll();

                                $rStmt = $pdo->prepare("SELECT notification_id FROM user_read_notifications WHERE user_id = ?");
                                $rStmt->execute([$_SESSION['user_id']]);
                                $readNotifIds = $rStmt->fetchAll(PDO::FETCH_COLUMN);
                            } catch (Exception $e) {}
                        }

                        $unreadCount = 0;
                        $firstUnreadNotif = null;
                        foreach ($activeNotifs as $an) {
                            if (!in_array($an['id'], $readNotifIds)) {
                                $unreadCount++;
                                if ($firstUnreadNotif === null) {
                                    $firstUnreadNotif = $an;
                                }
                            }
                        }
                    ?>
                        <div class="stat badge-xp" title="XP: <?= $xp ?> / <?= $next_level_xp ?>">
                            <div class="badge-xp-text">
                                <i data-lucide="star"></i> <span>LVL <?= $level ?></span>
                            </div>
                            <div class="xp-progress-bar-wrap">
                                <div style="width: <?= $percent ?>%; height: 100%; background: var(--primary);"></div>
                            </div>
                        </div>
                        <div class="stat badge-coins" title="<?= number_format($coins) ?> Koin">
                            <i data-lucide="coins"></i> <span class="coin-count"><?= number_format($coins) ?></span>
                        </div>

                        <!-- Notification Bell & Dropdown Wrap -->
                        <div class="notification-bell-wrap">
                            <button type="button" class="notification-bell" id="notification-trigger" aria-label="Buka Notifikasi">
                                <i data-lucide="bell"></i>
                                <span class="dot" id="notif-dot" style="<?= $unreadCount > 0 ? 'display:inline-block;' : 'display:none;' ?>"></span>
                            </button>
                            
                            <!-- Notifications Dropdown Menu -->
                            <div class="notifications-dropdown glass-panel" id="notifications-dropdown">
                                <div class="notifications-dropdown-header">
                                    <h4 class="notifications-dropdown-title">
                                        <i data-lucide="bell" style="width:16px;height:16px;color:var(--primary);"></i> Notifikasi
                                    </h4>
                                    <button type="button" class="notifications-dropdown-close" onclick="closeNotificationsDropdown()" aria-label="Tutup Notifikasi">
                                        <i data-lucide="x" style="width:16px;height:16px;"></i>
                                    </button>
                                </div>
                                <div class="notifications-list" id="notifications-list">
                                    <?php if (empty($activeNotifs)): ?>
                                        <div style="text-align: center; color: var(--text-muted); padding: 24px 0; font-size: 0.85rem;">Tidak ada notifikasi baru</div>
                                    <?php else: ?>
                                        <?php foreach ($activeNotifs as $notif): 
                                            $isUnread = !in_array($notif['id'], $readNotifIds);
                                            $typeClass = in_array($notif['type'], ['info', 'success', 'warning']) ? 'type-'.$notif['type'] : 'type-info';
                                        ?>
                                            <div class="notif-item <?= $typeClass ?>">
                                                <div class="notif-item-top">
                                                    <span class="notif-item-title"><?= htmlspecialchars($notif['title']) ?></span>
                                                    <?php if($isUnread): ?>
                                                        <span class="notif-badge-new">Baru</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="notif-item-msg"><?= htmlspecialchars($notif['message']) ?></div>
                                                <div class="notif-item-time">
                                                    <i data-lucide="clock" style="width:12px;height:12px;"></i>
                                                    <?= date('d M Y, H:i', strtotime($notif['created_at'])) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <a href="?page=profile" class="topbar-avatar-btn mobile-only-inline" title="Profil Saya: <?= htmlspecialchars($usrName) ?>">
                            <img src="uploads/avatars/<?= htmlspecialchars($usrAvatar) ?>" class="topbar-avatar-img" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($usrName) ?>&size=30&background=2d5a38&color=fff'">
                        </a>
                    <?php else: ?>
                        <a href="?page=login" class="btn-primary topbar-login-btn">
                            <i data-lucide="log-in" style="width:14px;height:14px;"></i> <span>Login</span>
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Mobile Drawer Backdrop & Drawer Menu -->
            <div id="mobile-drawer-backdrop" class="mobile-drawer-backdrop" onclick="toggleMobileDrawer(false)"></div>
            <aside id="mobile-drawer" class="mobile-drawer glass-panel" aria-label="Menu Navigasi Mobile">
                <div class="mobile-drawer-header">
                    <div class="topbar-brand">
                        <span class="brand-dot"></span>
                        <span class="brand-name">Gummy</span>
                    </div>
                    <button type="button" class="mobile-drawer-close" onclick="toggleMobileDrawer(false)" aria-label="Tutup Menu">
                        <i data-lucide="x"></i>
                    </button>
                </div>

                <?php if(isset($_SESSION['user_id'])): ?>
                <div class="drawer-user-card">
                    <a href="?page=profile" onclick="toggleMobileDrawer(false)" style="display:flex; align-items:center; gap:12px; text-decoration:none; flex:1; min-width:0;">
                        <div class="auth-avatar-box">
                            <img src="uploads/avatars/<?= htmlspecialchars($usrAvatar) ?>" class="auth-avatar-img" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($usrName) ?>&size=36&background=2d5a38&color=fff'">
                            <span class="auth-status-dot"></span>
                        </div>
                        <div style="min-width:0;">
                            <div style="color:#fff; font-weight:700; font-size:0.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($usrName) ?></div>
                            <div style="color:var(--text-muted); font-size:0.75rem;">Lv. <?= $level ?? 1 ?> • <?= number_format($coins ?? 0) ?> Koin</div>
                        </div>
                    </a>
                    <a href="?page=logout" class="drawer-logout-btn" title="Keluar"><i data-lucide="log-out"></i></a>
                </div>
                <?php else: ?>
                <div class="drawer-login-wrap" style="padding:16px;">
                    <a href="?page=login" onclick="toggleMobileDrawer(false)" class="btn-primary" style="width:100%; text-align:center; padding:10px 16px; font-size:0.88rem; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <i data-lucide="log-in" style="width:16px;height:16px;"></i> Masuk / Daftar
                    </a>
                </div>
                <?php endif; ?>

                <ul class="drawer-nav-list">
                    <li><a href="?page=home" onclick="toggleMobileDrawer(false)" class="<?= $currPage==='home'?'active':'' ?>"><i data-lucide="house"></i> <span>Home</span></a></li>
                    <li><a href="?page=chat" onclick="toggleMobileDrawer(false)" class="<?= $currPage==='chat'?'active':'' ?>"><i data-lucide="message-circle"></i> <span>Chat Room</span></a></li>
                    <li><a href="?page=games" onclick="toggleMobileDrawer(false)" class="<?= $currPage==='games'?'active':'' ?>"><i data-lucide="gamepad-2"></i> <span>Games & Arcade</span></a></li>
                    <li><a href="?page=members" onclick="toggleMobileDrawer(false)" class="<?= $currPage==='members'?'active':'' ?>"><i data-lucide="users-round"></i> <span>Members</span></a></li>
                    <li><a href="?page=achievements" onclick="toggleMobileDrawer(false)" class="<?= $currPage==='achievements'?'active':'' ?>"><i data-lucide="trophy"></i> <span>Achievements</span></a></li>
                    <li><a href="?page=perks" onclick="toggleMobileDrawer(false)" class="<?= $currPage==='perks'?'active':'' ?>"><i data-lucide="shopping-cart"></i> <span>Shop & Perks</span></a></li>
                    <li><a href="?page=news" onclick="toggleMobileDrawer(false)" class="<?= $currPage==='news'?'active':'' ?>"><i data-lucide="newspaper"></i> <span>News</span></a></li>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="?page=admin" onclick="toggleMobileDrawer(false)" class="<?= $currPage==='admin'?'active':'' ?>"><i data-lucide="shield-check"></i> <span>Admin Panel</span></a></li>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="?page=profile" onclick="toggleMobileDrawer(false)" class="<?= $currPage==='profile'?'active':'' ?>"><i data-lucide="user"></i> <span>Profil Saya</span></a></li>
                    <?php endif; ?>
                </ul>
            </aside>

            <script>
            function toggleMobileDrawer(forceState) {
                const drawer = document.getElementById('mobile-drawer');
                const backdrop = document.getElementById('mobile-drawer-backdrop');
                if (!drawer) return;
                const isOpen = drawer.classList.contains('open');
                const shouldOpen = (typeof forceState === 'boolean') ? forceState : !isOpen;
                if (shouldOpen) {
                    drawer.classList.add('open');
                    if (backdrop) backdrop.classList.add('open');
                    document.body.classList.add('drawer-open');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                } else {
                    drawer.classList.remove('open');
                    if (backdrop) backdrop.classList.remove('open');
                    document.body.classList.remove('drawer-open');
                }
            }
            </script>
                        
            <script>
            let notifDropdownTimer = null;
            let siteToastTimer = null;

            function dismissSiteToast() {
                const toast = document.getElementById("site-toast-notification");
                if (!toast) return;
                if (siteToastTimer) {
                    clearTimeout(siteToastTimer);
                    siteToastTimer = null;
                }
                toast.classList.add("toast-hiding");
                setTimeout(() => {
                    const container = document.getElementById("site-toast-container");
                    if (container) container.remove();
                }, 350);
                fetch('api/mark_notifications_read.php', { method: 'POST' }).catch(() => {});
                const dot = document.getElementById("notif-dot");
                if (dot) dot.style.display = "none";
            }

            function openNotificationsDropdown() {
                const dropdown = document.getElementById("notifications-dropdown");
                const dot = document.getElementById("notif-dot");
                if (!dropdown) return;
                dropdown.style.display = "block";
                if (dot) dot.style.display = "none";
                if (typeof lucide !== 'undefined') lucide.createIcons();
                fetch('api/mark_notifications_read.php', { method: 'POST' }).catch(() => {});
                resetDropdownTimer();
            }

            function closeNotificationsDropdown() {
                const dropdown = document.getElementById("notifications-dropdown");
                if (!dropdown) return;
                dropdown.style.display = "none";
                if (notifDropdownTimer) {
                    clearTimeout(notifDropdownTimer);
                    notifDropdownTimer = null;
                }
            }

            function resetDropdownTimer() {
                if (notifDropdownTimer) clearTimeout(notifDropdownTimer);
                notifDropdownTimer = setTimeout(() => {
                    closeNotificationsDropdown();
                }, 4000);
            }

            document.addEventListener("DOMContentLoaded", () => {
                const trigger = document.getElementById("notification-trigger");
                const dropdown = document.getElementById("notifications-dropdown");
                const toast = document.getElementById("site-toast-notification");

                // Auto dismiss floating toast notification after 4 seconds
                if (toast) {
                    siteToastTimer = setTimeout(() => {
                        dismissSiteToast();
                    }, 4000);

                    // Pause timer on hover, resume on leave
                    toast.addEventListener("mouseenter", () => {
                        if (siteToastTimer) clearTimeout(siteToastTimer);
                    });
                    toast.addEventListener("mouseleave", () => {
                        siteToastTimer = setTimeout(() => {
                            dismissSiteToast();
                        }, 2000);
                    });
                }

                if (trigger && dropdown) {
                    trigger.addEventListener("click", (e) => {
                        e.stopPropagation();
                        const isVisible = dropdown.style.display === "block";
                        if (isVisible) {
                            closeNotificationsDropdown();
                        } else {
                            openNotificationsDropdown();
                        }
                    });

                    // Pause 4s auto-close on dropdown hover, restart on mouse leave
                    dropdown.addEventListener("mouseenter", () => {
                        if (notifDropdownTimer) clearTimeout(notifDropdownTimer);
                    });
                    dropdown.addEventListener("mouseleave", () => {
                        resetDropdownTimer();
                    });

                    document.addEventListener("click", (e) => {
                        if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
                            closeNotificationsDropdown();
                        }
                    });
                }
            });
            </script>

            <div class="page-transition-overlay"></div>

            <?php if (isset($_SESSION['user_id']) && !empty($firstUnreadNotif)): 
                $toastType = in_array($firstUnreadNotif['type'], ['info', 'success', 'warning']) ? $firstUnreadNotif['type'] : 'info';
                $toastBorderColor = $toastType === 'warning' ? '#ff9500' : ($toastType === 'success' ? 'var(--primary)' : 'var(--secondary)');
                $toastIcon = $toastType === 'warning' ? 'alert-triangle' : ($toastType === 'success' ? 'check-circle' : 'bell');
            ?>
            <!-- Floating Toast Notification (Auto Disappear after 4s) -->
            <div id="site-toast-container">
                <div class="site-toast-card" id="site-toast-notification">
                    <div class="toast-indicator" style="background: <?= $toastBorderColor ?>;"></div>
                    <div class="toast-icon-box" style="border-color: <?= $toastBorderColor ?>; color: <?= $toastBorderColor ?>;">
                        <i data-lucide="<?= $toastIcon ?>" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="toast-content">
                        <div class="toast-header-row">
                            <h4 class="toast-title"><?= htmlspecialchars($firstUnreadNotif['title']) ?></h4>
                            <span class="toast-badge-pill">Baru</span>
                        </div>
                        <p class="notif-item-msg" style="margin-bottom:0;"><?= htmlspecialchars($firstUnreadNotif['message']) ?></p>
                    </div>
                    <button type="button" class="toast-close-btn" onclick="dismissSiteToast()" aria-label="Tutup Notifikasi">
                        <i data-lucide="x" style="width:16px;height:16px;"></i>
                    </button>
                    <div class="toast-progress-bar" style="background: <?= $toastBorderColor ?>;"></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="content-container">

