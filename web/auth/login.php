<?php
/**
 * login.php — Player login.
 *
 * GET  — Display the login form.
 * POST — Validate credentials, create session, redirect to /archspace/index.html (303).
 *        On failure, re-render form with "Invalid email or password."
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib.php';

// Already logged in — skip to game.
if (current_account() !== null) {
    header('Location: /archspace/index.html', true, 303);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim((string)($_POST['email']    ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // Fetch account by email using a prepared statement.
    $db   = db();
    $stmt = $db->prepare(
        'SELECT id, password_hash FROM accounts WHERE email = ? LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();

    // Constant-time-ish check: always call auth_verify to avoid timing oracle.
    $hashToCheck = $row['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$x$x';
    if ($row !== null && auth_verify($password, $hashToCheck)) {
        create_session((int)$row['id']);
        header('Location: /archspace/index.html', true, 303);
        exit;
    }

    $error = 'Invalid email or password.';
}

// ---------------------------------------------------------------------------
// Output — login form (original Archspace styling)
// ---------------------------------------------------------------------------
require_once __DIR__ . '/theme.php';

auth_page_start('Sign In');
echo auth_title('Enter Archspace');
echo auth_error($error);
?>
<form method="post" action="/auth/login.php">
<?= auth_input('Email', 'email', 'email', (string)($_POST['email'] ?? ''), 'email') ?>
<?= auth_input('Password', 'password', 'password', '', 'current-password') ?>
<?= auth_submit('bu_login.gif', 'login', 120, 16) ?>
</form>
<?php
auth_page_end(
    '<div class="as-forgot"><a href="/auth/forgot.php">Forgot your password?</a></div>'
    . 'New player? <a href="/auth/register.php"><img src="/image/as_login/bu_register.gif"'
    . ' width="120" height="16" border="0" alt="register" style="vertical-align:middle"></a>'
);
