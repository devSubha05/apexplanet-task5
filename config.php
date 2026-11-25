<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'elearn');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_NAME', 'LearnLoop');
define('MAIL_FROM', 'no-reply@yourdomain.com');
define('DEV_SHOW_OTP', true);

define('OTP_TTL_MINUTES', 10);
define('UPLOAD_DIR', __DIR__ . '/uploads/avatars/');
define('UPLOAD_URL', 'uploads/avatars/');
define('MATERIALS_DIR', __DIR__ . '/uploads/materials/');
define('MATERIALS_URL', 'uploads/materials/');
define('SUBMISSIONS_DIR', __DIR__ . '/uploads/submissions/');
define('SUBMISSIONS_URL', 'uploads/submissions/');
define('VIDEOS_DIR', __DIR__ . '/uploads/videos/');
define('VIDEOS_URL', 'uploads/videos/');

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS'])) ini_set('session.cookie_secure', '1');
session_start();
