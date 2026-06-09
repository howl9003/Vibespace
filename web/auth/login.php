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
// Output — login form
// ---------------------------------------------------------------------------
$safeError = htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Archspace — Sign In</title>
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
            max-width: 360px;
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
        .footer-links {
            margin-top: 1.25rem;
            text-align: center;
            font-size: .8rem;
            color: #666;
            line-height: 1.8;
        }
        .footer-links a { color: #5a9fd4; text-decoration: none; }
        .footer-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <h1>Archspace</h1>

    <?php if ($safeError !== ''): ?>
        <div class="error"><?= $safeError ?></div>
    <?php endif; ?>

    <form method="post" action="/auth/login.php">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required
               value="<?= $safeEmail ?>" autocomplete="email">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required
               autocomplete="current-password">

        <button type="submit">Sign In</button>
    </form>

    <div class="footer-links">
        <a href="/auth/forgot.php">Forgot password?</a><br>
        New player? <a href="/auth/register.php">Create an account</a>
    </div>
</div>
</body>
</html>
