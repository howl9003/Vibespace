<?php
/**
 * register.php — New account registration.
 *
 * GET  — Display the registration form.
 * POST — Validate input, create account, establish session, redirect to /archspace/index.html.
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

// If already logged in, go straight to the game.
if (current_account() !== null) {
    header('Location: /archspace/index.html', true, 303);
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

        header('Location: /archspace/index.html', true, 303);
        exit;
    }
}

// ---------------------------------------------------------------------------
// Output — registration form (GET, or POST with errors)
// ---------------------------------------------------------------------------
$safeError = htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Archspace — Register</title>
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
        input[type=email],
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
    </style>
</head>
<body>
<div class="card">
    <h1>Create Account</h1>

    <?php if ($safeError !== ''): ?>
        <div class="error"><?= $safeError ?></div>
    <?php endif; ?>

    <form method="post" action="/auth/register.php">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required
               value="<?= $safeEmail ?>" autocomplete="email">

        <label for="password">Password <small>(min. 8 characters)</small></label>
        <input type="password" id="password" name="password" required
               autocomplete="new-password">

        <label for="password2">Confirm password</label>
        <input type="password" id="password2" name="password2" required
               autocomplete="new-password">

        <button type="submit">Register</button>
    </form>

    <div class="footer-link">
        Already have an account? <a href="/auth/login.php">Sign in</a>
    </div>
</div>
</body>
</html>
