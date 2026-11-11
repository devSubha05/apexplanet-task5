<?php
require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $attempt = 0;
        while ($attempt < 2) {
            $attempt++;
            try {
                $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                break;
            } catch (PDOException $e) {
                // If connection refused, attempt to auto-launch local MariaDB on Windows and retry
                if ($attempt === 1 && PHP_OS_FAMILY === 'Windows') {
                    $mysqld = 'C:\\Program Files\\MariaDB 13.0\\bin\\mysqld.exe';
                    $myini  = 'C:\\Program Files\\MariaDB 13.0\\data\\my.ini';
                    if (file_exists($mysqld)) {
                        @pclose(@popen("start /B \"\" \"$mysqld\" --defaults-file=\"$myini\" --console", "r"));
                        usleep(1500000);
                        continue;
                    }
                }
                http_response_code(500);
                die('<div style="font-family:sans-serif;padding:2rem;max-width:600px;margin:2rem auto;border:1px solid #e2e8f0;border-radius:8px;background:#fff5f5;color:#9b2c2c;">'
                    . '<h2 style="margin-top:0;">Database Connection Error</h2>'
                    . '<p><strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>'
                    . '<p style="color:#4a5568;">Please make sure your MariaDB/MySQL server is running on port 3306 and the <code>' . htmlspecialchars(DB_NAME) . '</code> database has been imported.</p>'
                    . '</div>');
            }
        }
    }
    return $pdo;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $to): never { header("Location: $to"); exit; }

// ---------- CSRF ----------
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

// ---------- Flash messages ----------
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

// ---------- Auth ----------
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
function require_admin(): array {
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') { http_response_code(403); exit('Admins only.'); }
    return $u;
}

// ---------- Email OTP ----------
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

// ---------- Misc ----------
function avatar_url(?array $u): string {
    return ($u && !empty($u['avatar'])) ? UPLOAD_URL . $u['avatar'] : 'assets/img/avatar.svg';
}
function course_progress(int $userId, int $courseId): array {
    $s = db()->prepare('SELECT COUNT(*) FROM lessons WHERE course_id=?'); $s->execute([$courseId]); $total = (int)$s->fetchColumn();
    $s = db()->prepare('SELECT COUNT(*) FROM lesson_progress p JOIN lessons l ON l.id=p.lesson_id WHERE p.user_id=? AND l.course_id=?');
    $s->execute([$userId, $courseId]); $done = (int)$s->fetchColumn();
    return ['done' => $done, 'total' => $total, 'pct' => $total ? (int)round($done * 100 / $total) : 0];
}
