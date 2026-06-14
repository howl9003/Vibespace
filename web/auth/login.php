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

// Already logged in — skip to game (or to create-character if none yet).
$acct = current_account();
if ($acct !== null) {
    header('Location: ' . game_entry_url((int)$acct['id']), true, 303);
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
        $remember = !empty($_POST['remember']);
        create_session((int)$row['id'], $remember);
        header('Location: ' . game_entry_url((int)$row['id']), true, 303);
        exit;
    }

    $error = 'Invalid email or password.';
}

// ---------------------------------------------------------------------------
// Output — login form (original Archspace styling)
// ---------------------------------------------------------------------------
require_once __DIR__ . '/theme.php';

auth_page_start('Sign In');
echo auth_error($error);
?>
<form method="post" action="/auth/login.php">
<?= auth_input('Email', 'email', 'email', (string)($_POST['email'] ?? ''), 'email') ?>
<?= auth_input('Password', 'password', 'password', '', 'current-password') ?>
<label class="as-remember"><input type="checkbox" name="remember" value="1"> Remember me</label>
<div class="as-btnrow">
  <input class="as-btn" type="image" src="/image/as_login/bu_login.gif" width="120" height="16" alt="login">
  <a href="/auth/register.php"><img src="/image/as_login/bu_register.gif" width="120" height="16" border="0" alt="register"></a>
</div>
</form>
<div class="as-forgot-sm"><a href="/auth/forgot.php">forgot your password?</a></div>
<?php
auth_page_end();
