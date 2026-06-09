<?php
/**
 * lib.php — shared helpers for the Archspace auth service.
 *
 * Functions provided:
 *   auth_hash($plain)              — hash a password (argon2id preferred, bcrypt fallback)
 *   auth_verify($plain, $hash)     — verify a password against its hash
 *   generate_token()               — 64 hex-char cryptographically-random token
 *   create_session($accountId)     — inserts session row, sets cookie; returns token
 *   delete_session($token)         — deletes session row, clears cookie
 *   current_account()              — reads as_session cookie → account array or null
 *   send_mail($to, $subject, $body)— sends via SMTP env vars or logs to file (dev)
 *
 * Environment variables for mail:
 *   SMTP_HOST, SMTP_PORT (default 587), SMTP_USER, SMTP_PASS, SMTP_FROM
 *   If SMTP_HOST is not set, the message is appended to /var/log/archspace/mail.log.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

/** Name of the session cookie. */
const AS_SESSION_COOKIE = 'as_session';

/** Session lifetime in seconds (7 days). */
const AS_SESSION_TTL = 7 * 24 * 3600;

// ---------------------------------------------------------------------------
// Password helpers
// ---------------------------------------------------------------------------

/**
 * Hash a plain-text password.
 * Uses PASSWORD_ARGON2ID when the extension is available, otherwise PASSWORD_BCRYPT.
 */
function auth_hash(string $plain): string
{
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    return password_hash($plain, $algo);
}

/**
 * Verify a plain-text password against a stored hash.
 */
function auth_verify(string $plain, string $hash): bool
{
    return password_verify($plain, $hash);
}

// ---------------------------------------------------------------------------
// Token generation
// ---------------------------------------------------------------------------

/**
 * Generate a 64 hex-character (32-byte) random token.
 *
 * @throws Exception if the CSPRNG fails (propagated from random_bytes).
 */
function generate_token(): string
{
    return bin2hex(random_bytes(32));
}

// ---------------------------------------------------------------------------
// Cookie helper
// ---------------------------------------------------------------------------

/**
 * Set the as_session cookie with secure attributes.
 * Secure flag is added only when the current request came over HTTPS.
 */
function set_session_cookie(string $value, int $expires): void
{
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    setcookie(AS_SESSION_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
}

// ---------------------------------------------------------------------------
// Session management
// ---------------------------------------------------------------------------

/**
 * Create a new session for an account and set the session cookie.
 *
 * @param  int $accountId  The accounts.id value.
 * @return string          The 64-char session token.
 */
function create_session(int $accountId): string
{
    $token     = generate_token();
    $now       = time();
    $expires   = $now + AS_SESSION_TTL;

    $db   = db();
    $stmt = $db->prepare(
        'INSERT INTO sessions (id, account_id, created_at, expires) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('siii', $token, $accountId, $now, $expires);
    $stmt->execute();
    $stmt->close();

    set_session_cookie($token, $expires);

    return $token;
}

/**
 * Delete a session row from the database and clear the cookie.
 *
 * @param  string $token  The session token from the cookie.
 */
function delete_session(string $token): void
{
    $db   = db();
    $stmt = $db->prepare('DELETE FROM sessions WHERE id = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->close();

    // Clear the cookie by setting an expiry in the past.
    set_session_cookie('', time() - 3600);
}

/**
 * Read the as_session cookie and validate it against the database.
 *
 * @return array{id:int, email:string, is_admin:int}|null
 *         Account data for the authenticated user, or null if not logged in
 *         or the session has expired.
 */
function current_account(): ?array
{
    $token = $_COOKIE[AS_SESSION_COOKIE] ?? '';

    if ($token === '' || strlen($token) !== 64) {
        return null;
    }

    $now  = time();
    $db   = db();

    $stmt = $db->prepare(
        'SELECT a.id, a.email, a.is_admin
           FROM sessions s
           JOIN accounts a ON a.id = s.account_id
          WHERE s.id = ?
            AND s.expires > ?
          LIMIT 1'
    );
    $stmt->bind_param('si', $token, $now);
    $stmt->execute();

    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();

    if ($row === null) {
        return null;
    }

    return [
        'id'       => (int)$row['id'],
        'email'    => (string)$row['email'],
        'is_admin' => (int)$row['is_admin'],
    ];
}

// ---------------------------------------------------------------------------
// Mail helper
// ---------------------------------------------------------------------------

/**
 * Send an email.
 *
 * When SMTP_HOST is set in the environment, this function connects directly
 * to the SMTP server over a raw TCP socket and delivers the message using
 * SMTP AUTH PLAIN (with STARTTLS when SMTP_PORT is 587).
 *
 * When SMTP_HOST is not set (dev/test), the message is appended to
 * /var/log/archspace/mail.log and the function returns true.
 *
 * Environment variables consumed:
 *   SMTP_HOST  — hostname or IP of the SMTP relay
 *   SMTP_PORT  — port (default 587)
 *   SMTP_USER  — SMTP username
 *   SMTP_PASS  — SMTP password
 *   SMTP_FROM  — envelope / From: address
 *
 * @param  string $to       Recipient address.
 * @param  string $subject  Email subject.
 * @param  string $body     Plain-text email body.
 * @return bool             True on success, false on failure.
 */
function send_mail(string $to, string $subject, string $body): bool
{
    $smtpHost = (string)(getenv('SMTP_HOST') ?: '');

    // ------------------------------------------------------------------
    // Dev fallback: log to file
    // ------------------------------------------------------------------
    if ($smtpHost === '') {
        return _mail_log($to, $subject, $body);
    }

    // ------------------------------------------------------------------
    // SMTP delivery
    // ------------------------------------------------------------------
    $port = (int)(getenv('SMTP_PORT') ?: 587);
    $user = (string)(getenv('SMTP_USER') ?: '');
    $pass = (string)(getenv('SMTP_PASS') ?: '');
    $from = (string)(getenv('SMTP_FROM') ?: $user);

    try {
        return _smtp_send($smtpHost, $port, $user, $pass, $from, $to, $subject, $body);
    } catch (Throwable $e) {
        error_log('send_mail: SMTP error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Append the mail message to the dev log file.
 *
 * @internal
 */
function _mail_log(string $to, string $subject, string $body): bool
{
    $logDir  = '/var/log/archspace';
    $logFile = $logDir . '/mail.log';

    // Best-effort: create directory if it doesn't exist.
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $sep     = str_repeat('-', 72);
    $date    = date('Y-m-d H:i:s T');
    $entry   = "\n{$sep}\nDate: {$date}\nTo: {$to}\nSubject: {$subject}\n\n{$body}\n";

    return file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Minimal raw-socket SMTP client supporting STARTTLS + AUTH PLAIN.
 *
 * Limitations (intentional — this is a single-file helper, not a library):
 *  - Only PLAIN auth is attempted.
 *  - STARTTLS is attempted when port is 587 or SMTP server advertises it.
 *  - No MIME encoding; body is sent as-is (ASCII / UTF-8 plain text).
 *  - Single recipient only.
 *
 * @internal
 * @throws RuntimeException on connection or protocol error.
 */
function _smtp_send(
    string $host,
    int    $port,
    string $user,
    string $pass,
    string $from,
    string $to,
    string $subject,
    string $body
): bool {
    // Helper: read one SMTP reply; throws on transient/permanent failure.
    $read = static function (/** @var resource */ $sock) use ($host): array {
        $lines  = [];
        $status = 0;
        while (true) {
            $line = fgets($sock, 512);
            if ($line === false) {
                throw new RuntimeException("SMTP {$host}: connection closed unexpectedly");
            }
            $status = (int)substr($line, 0, 3);
            $lines[] = rtrim($line);
            // Last line of a multi-line response has a space after the code.
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if ($status >= 400) {
            throw new RuntimeException("SMTP {$host}: server error: " . implode(' | ', $lines));
        }
        return $lines;
    };

    // Helper: send a command and assert a specific expected reply code.
    $cmd = static function (/** @var resource */ $sock, string $command, int $expect) use ($read, $host): array {
        fwrite($sock, $command . "\r\n");
        $lines = $read($sock);
        $code  = (int)substr($lines[0], 0, 3);
        if ($code !== $expect) {
            throw new RuntimeException(
                "SMTP {$host}: expected {$expect}, got {$code}: " . implode(' | ', $lines)
            );
        }
        return $lines;
    };

    // Open connection (plain TCP; TLS upgrade happens via STARTTLS below).
    $errno  = 0;
    $errstr = '';
    $sock = @fsockopen($host, $port, $errno, $errstr, 10);
    if ($sock === false) {
        throw new RuntimeException("SMTP: cannot connect to {$host}:{$port} — {$errstr} ({$errno})");
    }
    stream_set_timeout($sock, 15);

    // Read banner.
    $read($sock);

    // EHLO — get capability list.
    $myHost    = gethostname() ?: 'localhost';
    $ehloLines = $cmd($sock, "EHLO {$myHost}", 250);

    // Collect capabilities.
    $caps = [];
    foreach ($ehloLines as $line) {
        if (strlen($line) > 4) {
            $caps[] = strtoupper(substr($line, 4));
        }
    }

    // STARTTLS when available (mandatory on port 587).
    $useTls = in_array('STARTTLS', $caps, true) || $port === 587;
    if ($useTls) {
        $cmd($sock, 'STARTTLS', 220);

        // Upgrade the stream.
        $crypto = stream_socket_enable_crypto(
            $sock,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );
        if ($crypto === false) {
            fclose($sock);
            throw new RuntimeException("SMTP {$host}: STARTTLS crypto negotiation failed");
        }

        // Re-issue EHLO after TLS.
        $ehloLines = $cmd($sock, "EHLO {$myHost}", 250);
        $caps = [];
        foreach ($ehloLines as $line) {
            if (strlen($line) > 4) {
                $caps[] = strtoupper(substr($line, 4));
            }
        }
    }

    // AUTH PLAIN (only if credentials provided).
    if ($user !== '' || $pass !== '') {
        $authString = base64_encode("\0{$user}\0{$pass}");
        $cmd($sock, "AUTH PLAIN {$authString}", 235);
    }

    // Envelope.
    $cmd($sock, "MAIL FROM:<{$from}>", 250);
    $cmd($sock, "RCPT TO:<{$to}>", 250);

    // Message data.
    $cmd($sock, 'DATA', 354);

    $date    = date('r');
    $message = "Date: {$date}\r\n"
             . "From: {$from}\r\n"
             . "To: {$to}\r\n"
             . "Subject: {$subject}\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=utf-8\r\n"
             . "Content-Transfer-Encoding: 8bit\r\n"
             . "\r\n"
             . str_replace("\n.", "\n..", $body) // dot-stuffing
             . "\r\n.";
    $cmd($sock, $message, 250);

    $cmd($sock, 'QUIT', 221);
    fclose($sock);

    return true;
}
