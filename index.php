<?php
// index.php - Main Router
session_start();

// Disable browser caching for real-time score & state updates
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/config/database.php';

$page = $_GET['page'] ?? 'home';

// Simple Router
switch ($page) {
    case 'home':
    case '':
        require __DIR__ . '/pages/home.php';
        break;
    case 'community':
        header("Location: ?page=chat");
        exit;
    case 'games':
        require __DIR__ . '/pages/games.php';
        break;
    case 'play':
        require __DIR__ . '/pages/play.php';
        break;
    case 'profile':
        require __DIR__ . '/pages/profile.php';
        break;
    case 'login':
        require __DIR__ . '/pages/login.php';
        break;
    case 'register':
        require __DIR__ . '/pages/register.php';
        break;
    case 'members':
        require __DIR__ . '/pages/members.php';
        break;
    case 'achievements':
        require __DIR__ . '/pages/achievements.php';
        break;
    case 'chat':
        require __DIR__ . '/pages/chat.php';
        break;
    case 'leaderboards':
        require __DIR__ . '/pages/leaderboards.php';
        break;
    case 'daily-mission':
        require __DIR__ . '/pages/daily-mission.php';
        break;
    case 'perks':
    case 'shop':
        require __DIR__ . '/pages/perks.php';
        break;
    case 'admin':
        require __DIR__ . '/pages/admin.php';
        break;
    case 'news':
        require __DIR__ . '/pages/news.php';
        break;
    case 'admin-login':
        require __DIR__ . '/pages/admin-login.php';
        break;
    case 'logout':
        session_destroy();
        header('Location: ?page=home');
        break;
    default:
        http_response_code(404);
        require __DIR__ . '/pages/404.php';
        break;
}
