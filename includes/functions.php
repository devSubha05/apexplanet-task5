<?php
require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $isPortOpen = function(string $host, int $port = 3306, float $timeout = 0.25): bool {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    };

    $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
    $dbName = defined('DB_NAME') ? DB_NAME : 'elearn';
    $user = defined('DB_USER') ? DB_USER : 'root';
    $pass = defined('DB_PASS') ? DB_PASS : '';

    if (!$isPortOpen($host, 3306, 0.25)) {
        $vbs = __DIR__ . '/start-mariadb.vbs';
        $bat = __DIR__ . '/start-mariadb.bat';

        if (file_exists($vbs)) {
            @pclose(@popen('wscript.exe //B "' . $vbs . '"', 'r'));
        } elseif (file_exists($bat)) {
            @pclose(@popen('cmd /c "' . $bat . '"', 'r'));
        } else {
            $mysqld = 'C:\\Program Files\\MariaDB 13.0\\bin\\mysqld.exe';
            $myini  = 'C:\\Program Files\\MariaDB 13.0\\data\\my.ini';
            if (file_exists($mysqld)) {
                $psCmd = 'powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath \'' . $mysqld . '\' -ArgumentList \'--defaults-file=\"' . $myini . '\"\' -WorkingDirectory \'C:\Program Files\MariaDB 13.0\' -WindowStyle Hidden"';
                @pclose(@popen($psCmd, 'r'));
            }
        }

        for ($k = 0; $k < 20; $k++) {
            usleep(300000); // 0.3s
            if ($isPortOpen($host, 3306, 0.2)) {
                break;
            }
        }
    }

    $lastException = null;
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        try {
            $pdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            $lastException = $e;
            if ($attempt < 3 && (strpos($e->getMessage(), '2002') !== false || strpos($e->getMessage(), 'Connection refused') !== false)) {
                usleep(500000); 
            }
        }
    }

    http_response_code(500);
    die('<div style="font-family:system-ui,-apple-system,sans-serif;padding:2.2rem;max-width:620px;margin:3rem auto;border:1px solid #fed7d7;border-radius:14px;background:#fff5f5;color:#9b2c2c;box-shadow:0 8px 24px rgba(0,0,0,0.06);">'
        . '<h2 style="margin-top:0;color:#c53030;display:flex;align-items:center;gap:.5rem;">⚠️ Connecting to Database...</h2>'
        . '<p style="font-size:1.02rem;line-height:1.5;"><strong>' . htmlspecialchars($lastException ? $lastException->getMessage() : 'Database unreachable') . '</strong></p>'
        . '<hr style="border:none;border-top:1px solid #fed7d7;margin:1.2rem 0;">'
        . '<p style="color:#4a5568;line-height:1.6;">MariaDB is starting up in the background. This page will <strong id="cd">auto-reload in 3 seconds</strong>.</p>'
        . '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin:1.2rem 0;color:#2d3748;">'
        . '<p style="margin:0 0 .5rem;font-weight:700;">💡 Manual start alternatives:</p>'
        . '<ol style="margin:0;padding-left:1.4rem;line-height:1.7;font-size:.92rem;">'
        . '<li>Run <strong>run.bat</strong> in your project folder (starts both MariaDB and PHP).</li>'
        . '<li>Or double-click <strong>start-database.bat</strong>.</li>'
        . '</ol>'
        . '</div>'
        . '<p style="margin-bottom:0;"><a href="javascript:location.reload()" style="display:inline-block;background:#0e6b63;color:#fff;padding:.65rem 1.6rem;border-radius:8px;text-decoration:none;font-weight:700;box-shadow:0 2px 6px rgba(14,107,99,0.3);">🔄 Refresh Now</a></p>'
        . '<script>'
        . 'var s = 3; var el = document.getElementById("cd");'
        . 'setInterval(function(){ s--; if (el && s > 0) el.textContent = "auto-reload in " + s + " second" + (s>1?"s":""); if (s <= 0) location.reload(); }, 1000);'
        . '</script>'
        . '</div>');
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $to): never { header("Location: $to"); exit; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function current_user(): ?array {
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare('SELECT id,name,email,role,avatar,bio,created_at FROM users WHERE id=? AND is_verified=1');
            $s->execute([$_SESSION['uid']]);
            $user = $s->fetch() ?: null;
        }
    }
    return $user;
}
function login_user(int $id): void { session_regenerate_id(true); $_SESSION['uid'] = $id; }
function require_login(): array {
    $u = current_user();
    if (!$u) { flash('error', 'Log in to continue.'); redirect('login.php'); }
    return $u;
}
function require_instructor(): array {
    $u = current_user();
    if (!$u || !in_array($u['role'], ['instructor', 'admin'], true)) {
        http_response_code(403);
        flash('error', 'Instructor access required.');
        redirect($u ? 'dashboard.php' : 'login.php');
    }
    return $u;
}
function require_admin(): array {
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') { http_response_code(403); exit('Admins only.'); }
    return $u;
}

function issue_otp(int $userId, string $email, string $name): void {
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    db()->prepare('DELETE FROM otp_codes WHERE user_id=?')->execute([$userId]);
    db()->prepare('INSERT INTO otp_codes (user_id,code_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL ' . (int)OTP_TTL_MINUTES . ' MINUTE))')
        ->execute([$userId, password_hash($code, PASSWORD_DEFAULT)]);
    $_SESSION['otp_user'] = $userId;
    $_SESSION['otp_sent_at'] = time();

    $subject = SITE_NAME . ' verification code';
    $body = "Hi $name,\n\nYour verification code is $code. It expires in " . OTP_TTL_MINUTES . " minutes.\nIf you did not sign up, ignore this email.\n";
    $headers = 'From: ' . MAIL_FROM . "\r\nContent-Type: text/plain; charset=UTF-8";
    $sent = @mail($email, $subject, $body, $headers);
    if (DEV_SHOW_OTP) flash('info', "Dev mode: your code is $code" . ($sent ? '' : ' (email was not sent from this machine)'));
}

function avatar_url(?array $u): string {
    return ($u && !empty($u['avatar'])) ? UPLOAD_URL . $u['avatar'] : 'assets/img/avatar.svg';
}
function course_progress(int $userId, int $courseId): array {
    $s = db()->prepare('SELECT COUNT(*) FROM lessons WHERE course_id=?'); $s->execute([$courseId]); $total = (int)$s->fetchColumn();
    $s = db()->prepare('SELECT COUNT(*) FROM lesson_progress p JOIN lessons l ON l.id=p.lesson_id WHERE p.user_id=? AND l.course_id=?');
    $s->execute([$userId, $courseId]); $done = (int)$s->fetchColumn();
    return ['done' => $done, 'total' => $total, 'pct' => $total ? (int)round($done * 100 / $total) : 0];
}
