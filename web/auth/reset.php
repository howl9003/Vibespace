<?php
/**
 * reset.php — Password reset (consume a reset token).
 *
 * GET  ?token=<64hex>  — Show the set-new-password form if token is valid & not expired.
 * POST {token, password, password2} — Validate, update password_hash, clear token,
 *                                      redirect to /auth/login.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib.php';

$error  = '';
$notice = '';

// Retrieve the token from GET or POST.
$token = trim((string)($_REQUEST['token'] ?? ''));

// ---------------------------------------------------------------------------
// Validate the token (needed for both GET and POST)
// ---------------------------------------------------------------------------
$account = null;

if ($token !== '' && strlen($token) === 64) {
    $now  = time();
    $db   = db();
    $stmt = $db->prepare(
        'SELECT id FROM accounts WHERE reset_token = ? AND reset_expires > ? LIMIT 1'
    );
    $stmt->bind_param('si', $token, $now);
    $stmt->execute();
    $result  = $stmt->get_result();
    $account = $result->fetch_assoc();
    $stmt->close();
}

// ---------------------------------------------------------------------------
// Handle POST (set new password)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($account === null) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    } else {
        $password  = (string)($_POST['password']  ?? '');
        $password2 = (string)($_POST['password2'] ?? '');

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $password2) {
            $error = 'Passwords do not match.';
        } else {
            // Update password and clear reset token.
            $hash = auth_hash($password);
            $db   = db();
            $stmt = $db->prepare(
                'UPDATE accounts
                    SET password_hash = ?,
                        reset_token   = NULL,
                        reset_expires = NULL
                  WHERE id = ?'
            );
            $stmt->bind_param('si', $hash, $account['id']);
            $stmt->execute();
            $stmt->close();

            header('Location: /auth/login.php', true, 303);
            exit;
        }
    }
}

// ---------------------------------------------------------------------------
// Output
// ---------------------------------------------------------------------------
$safeError = htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeToken = htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$tokenValid = ($account !== null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Archspace — Set New Password</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            background: #000;
            color: #c8c8c8;
            font-family: 'Courier New', Courier, monospace;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #0a0a0a;
            border: 1px solid #2a2a2a;
            padding: 2rem 2.5rem;
            width: 100%;
            max-width: 380px;
        }
        h1 {
            margin: 0 0 1.5rem;
            font-size: 1.4rem;
            color: #5a9fd4;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        label {
            display: block;
            margin-bottom: .25rem;
            font-size: .85rem;
            color: #888;
        }
        input[type=password] {
            display: block;
            width: 100%;
            padding: .45rem .6rem;
            margin-bottom: 1rem;
            background: #111;
            border: 1px solid #333;
            color: #ddd;
            font-family: inherit;
            font-size: .95rem;
            outline: none;
        }
        input:focus { border-color: #5a9fd4; }
        .error {
            background: #2a0a0a;
            border-left: 3px solid #c0392b;
            color: #e74c3c;
            padding: .5rem .75rem;
            margin-bottom: 1rem;
            font-size: .875rem;
        }
        button {
            width: 100%;
            padding: .55rem;
            background: #1a3a5c;
            color: #9ecfed;
            border: 1px solid #2e6da4;
            font-family: inherit;
            font-size: 1rem;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        button:hover { background: #234e7a; }
        .footer-link {
            margin-top: 1.25rem;
            text-align: center;
            font-size: .8rem;
            color: #666;
        }
        .footer-link a { color: #5a9fd4; text-decoration: none; }
        .footer-link a:hover { text-decoration: underline; }
        .invalid-msg { color: #e74c3c; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>Set New Password</h1>

    <?php if ($safeError !== ''): ?>
        <div class="error"><?= $safeError ?></div>
    <?php endif; ?>

    <?php if ($tokenValid): ?>
        <form method="post" action="/auth/reset.php">
            <input type="hidden" name="token" value="<?= $safeToken ?>">

            <label for="password">New password <small>(min. 8 characters)</small></label>
            <input type="password" id="password" name="password" required
                   autocomplete="new-password">

            <label for="password2">Confirm new password</label>
            <input type="password" id="password2" name="password2" required
                   autocomplete="new-password">

            <button type="submit">Set Password</button>
        </form>
    <?php else: ?>
        <p class="invalid-msg">
            This reset link is invalid or has expired.
        </p>
        <a href="/auth/forgot.php">
            <button type="button">Request a new reset link</button>
        </a>
    <?php endif; ?>

    <div class="footer-link">
        <a href="/auth/login.php">Back to sign in</a>
    </div>
</div>
</body>
</html>
