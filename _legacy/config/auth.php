<?php
// Authentication & Security Middleware for RMS
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

// Rate-limiting configuration
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_WINDOW_MINUTES = 15;

/**
 * Retrieve sanitized client IP address.
 * 
 * @return string
 */
function get_client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    // Validate IP format
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    return '127.0.0.1';
}

/**
 * Check if the current IP or identity is currently locked out due to excessive failed logins.
 * 
 * @param PDO $pdo
 * @param string $ip
 * @param string $identity
 * @return array [bool $isLocked, int $remainingMinutes, int $attemptCount]
 */
function check_rate_limit(PDO $pdo, string $ip, string $identity): array {
    $cutoff = date('Y-m-d H:i:s', strtotime("-" . LOCKOUT_WINDOW_MINUTES . " minutes"));

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS failed_count, MAX(attempted_at) AS last_attempt
        FROM `login_attempts`
        WHERE (`ip_address` = :ip OR `identity` = :identity)
          AND `is_successful` = 0
          AND `attempted_at` >= :cutoff
    ");
    $stmt->execute([
        ':ip'       => $ip,
        ':identity' => $identity,
        ':cutoff'   => $cutoff
    ]);
    $result = $stmt->fetch();

    $failedCount = (int)($result['failed_count'] ?? 0);

    if ($failedCount >= MAX_LOGIN_ATTEMPTS) {
        $lastAttemptTime = strtotime($result['last_attempt']);
        $lockoutExpiry = $lastAttemptTime + (LOCKOUT_WINDOW_MINUTES * 60);
        $remainingSeconds = max(0, $lockoutExpiry - time());
        $remainingMinutes = ceil($remainingSeconds / 60);

        if ($remainingSeconds > 0) {
            return [true, $remainingMinutes, $failedCount];
        }
    }

    return [false, 0, $failedCount];
}

/**
 * Log a login attempt into the audit table.
 * 
 * @param PDO $pdo
 * @param string $ip
 * @param string $identity
 * @param bool $isSuccessful
 * @return void
 */
function record_login_attempt(PDO $pdo, string $ip, string $identity, bool $isSuccessful): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO `login_attempts` (`ip_address`, `identity`, `is_successful`)
            VALUES (:ip, :identity, :success)
        ");
        $stmt->execute([
            ':ip'       => $ip,
            ':identity' => $identity,
            ':success'  => $isSuccessful ? 1 : 0
        ]);

        // If login succeeded, clear recent failed attempts for this identity/ip to reset the penalty
        if ($isSuccessful) {
            $clearStmt = $pdo->prepare("
                DELETE FROM `login_attempts`
                WHERE (`ip_address` = :ip OR `identity` = :identity)
                  AND `is_successful` = 0
            ");
            $clearStmt->execute([
                ':ip'       => $ip,
                ':identity' => $identity
            ]);
        }
    } catch (PDOException $e) {
        // Silently fail logging if database issue to avoid exposing DB errors to user
        error_log("Failed to record login attempt: " . $e->getMessage());
    }
}

/**
 * Attempt to authenticate user with identity (username or email) and password.
 * 
 * @param PDO $pdo
 * @param string $identity
 * @param string $password
 * @param bool $rememberMe
 * @return array [bool 'success', string 'error', array 'user']
 */
function attempt_login(PDO $pdo, string $identity, string $password, bool $rememberMe = false): array {
    $ip = get_client_ip();
    $cleanIdentity = trim($identity);

    // 1. Check Rate Limit / Lockout
    [$isLocked, $remainingMinutes, $failedCount] = check_rate_limit($pdo, $ip, $cleanIdentity);
    if ($isLocked) {
        return [
            'success' => false,
            'error'   => "Too many failed login attempts. Please wait {$remainingMinutes} minute(s) before trying again.",
            'locked'  => true,
            'user'    => null
        ];
    }

    // 2. Query user by Username OR Email
    $stmt = $pdo->prepare("
        SELECT `id`, `username`, `full_name`, `email`, `password`, `role`, `status`
        FROM `users`
        WHERE `username` = :uname OR `email` = :uemail
        LIMIT 1
    ");
    $stmt->execute([
        ':uname'  => $cleanIdentity,
        ':uemail' => $cleanIdentity
    ]);
    $user = $stmt->fetch();

    // 3. Verify Password using constant-time hashing check
    if (!$user || !password_verify($password, $user['password'])) {
        record_login_attempt($pdo, $ip, $cleanIdentity, false);
        $attemptsLeft = MAX_LOGIN_ATTEMPTS - ($failedCount + 1);
        $warning = ($attemptsLeft > 0 && $attemptsLeft <= 2) 
            ? " ({$attemptsLeft} attempt(s) remaining before temporary lockout)" 
            : "";
        return [
            'success' => false,
            'error'   => "Invalid username/email or password." . $warning,
            'locked'  => false,
            'user'    => null
        ];
    }

    // 4. Check user account status
    if ($user['status'] !== 'Active') {
        record_login_attempt($pdo, $ip, $cleanIdentity, false);
        return [
            'success' => false,
            'error'   => "This account has been deactivated. Please contact an administrator.",
            'locked'  => false,
            'user'    => null
        ];
    }

    // 5. Success: record attempt & establish session
    record_login_attempt($pdo, $ip, $cleanIdentity, true);

    // Regenerate session ID to prevent Session Fixation attacks
    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    // Store sanitized user payload in session
    $_SESSION['user'] = [
        'id'        => (int)$user['id'],
        'username'  => $user['username'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
        'logged_in_at' => time()
    ];

    // Handle "Remember Me" extended cookie if checked
    if ($rememberMe) {
        $thirtyDays = 30 * 24 * 60 * 60;
        setcookie(session_name(), session_id(), [
            'expires'  => time() + $thirtyDays,
            'path'     => '/',
            'domain'   => '',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    return [
        'success' => true,
        'error'   => '',
        'locked'  => false,
        'user'    => $_SESSION['user']
    ];
}

/**
 * Return currently authenticated user data, or null if unauthenticated.
 * 
 * @return array|null
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if the visitor is currently authenticated.
 * 
 * @return bool
 */
function is_authenticated(): bool {
    return !empty($_SESSION['user']);
}

/**
 * Protect a route - redirects unauthenticated visitors to login.php.
 * 
 * @return void
 */
function require_auth(): void {
    if (!is_authenticated()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Require a specific role or list of roles to view page.
 * 
 * @param array|string $roles
 * @return void
 */
function require_role($roles): void {
    require_auth();
    $user = current_user();
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'] ?? '', $allowed, true)) {
        http_response_code(403);
        die("Access Denied: You do not have permission to view this page.");
    }
}

/**
 * Safely terminate user session and clear cookies.
 * 
 * @return void
 */
function logout_user(): void {
    $_SESSION = [];

    if (ini_get("session.use_cookies") && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}
