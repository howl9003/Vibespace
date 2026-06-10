<?php
/**
 * register.php — New account registration.
 *
 * GET  — Display the registration form.
 * POST — Validate input, create account, establish session, redirect to the
 *        standalone create-character page (a new account has no character yet).
 *
 * Fields: email, password, password2 (confirmation).
 * Rules:
 *   - email must pass filter_var FILTER_VALIDATE_EMAIL
 *   - password >= 8 characters
 *   - password == password2
 *   - email must not already be in use
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib.php';

// If already logged in, go to the game (or create-character if none yet).
$acct = current_account();
if ($acct !== null) {
    header('Location: ' . game_entry_url((int)$acct['id']), true, 303);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = trim((string)($_POST['email']     ?? ''));
    $password  = (string)($_POST['password']  ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    // ------------------------------------------------------------------
    // Validation
    // ------------------------------------------------------------------
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        // Check uniqueness.
        $db   = db();
        $stmt = $db->prepare('SELECT id FROM accounts WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account with that email already exists.';
        }
        $stmt->close();
    }

    // ------------------------------------------------------------------
    // Insert account and establish session
    // ------------------------------------------------------------------
    if ($error === '') {
        $hash = auth_hash($password);
        $now  = time();

        $db   = db();
        $stmt = $db->prepare(
            'INSERT INTO accounts (email, password_hash, is_admin, created_at) VALUES (?, ?, 0, ?)'
        );
        $stmt->bind_param('ssi', $email, $hash, $now);
        $stmt->execute();
        $newId = (int)$db->insert_id;
        $stmt->close();

        create_session($newId);

        // A fresh account has no character yet -> the standalone create page
        // (not the shell, whose left frame would also render the create form).
        header('Location: ' . game_entry_url($newId), true, 303);
        exit;
    }
}

// ---------------------------------------------------------------------------
// Output — registration form (original Archspace styling)
// ---------------------------------------------------------------------------
require_once __DIR__ . '/theme.php';

auth_page_start('Register');
echo auth_error($error);
?>
<form method="post" action="/auth/register.php">
<?= auth_input('Email', 'email', 'email', (string)($_POST['email'] ?? ''), 'email') ?>
<?= auth_input('Password (min. 8)', 'password', 'password', '', 'new-password') ?>
<?= auth_input('Confirm password', 'password2', 'password', '', 'new-password') ?>
<?= auth_submit('bu_register.gif', 'register', 120, 16) ?>
</form>
<?php
auth_page_end('Already have an account? <a href="/auth/login.php">Sign in</a>');
