<?php
// ---- Edit these for your server (InfinityFree gives you the host/user/db name in its panel) ----
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'elearn');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_NAME', 'LearnLoop');
define('MAIL_FROM', 'no-reply@yourdomain.com');
// true on localhost: shows the OTP on screen because local PHP cannot send mail. SET TO false WHEN DEPLOYED.
define('DEV_SHOW_OTP', true);

define('OTP_TTL_MINUTES', 10);
define('UPLOAD_DIR', __DIR__ . '/uploads/avatars/');
define('UPLOAD_URL', 'uploads/avatars/');

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS'])) ini_set('session.cookie_secure', '1');
session_start();
