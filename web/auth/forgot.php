<?php
/**
 * forgot.php — Password reset request.
 *
 * GET  — Display the "enter your email" form.
 * POST — Generate a reset token, store it, email the link, always show
 *        "If that email exists, a reset link was sent." (no user enumeration).
 *
 * Env vars used (via send_mail / lib.php):
 *   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM
 *   BASE_URL   — prefix for the reset link (e.g. https://archspace.example.com)
 *                Defaults to empty string → relative URL in the email.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib.php';

$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));

    if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
        $db   = db();

        // Look up the account.
        $stmt = $db->prepare('SELECT id FROM accounts WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();

        if ($row !== null) {
            // Generate and store the reset token (valid 1 hour).
            $token   = generate_token();
            $expires = time() + 3600;

            $stmt = $db->prepare(
                'UPDATE accounts SET reset_token = ?, reset_expires = ? WHERE id = ?'
            );
            $stmt->bind_param('sii', $token, $expires, $row['id']);
            $stmt->execute();
            $stmt->close();

            // Build the reset link.
            $baseUrl = rtrim((string)(getenv('BASE_URL') ?: ''), '/');
            $link    = $baseUrl . '/auth/reset.php?token=' . urlencode($token);

            $subject = 'Archspace password reset';
            $body    = "You requested a password reset for your Archspace account.\n\n"
                     . "Click the link below to set a new password (valid for 1 hour):\n\n"
                     . $link . "\n\n"
                     . "If you did not request this, you can safely ignore this message.\n";

            send_mail($email, $subject, $body);
        }
        // Intentional: no branch for "email not found" — same response either way.
    }

    $submitted = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Archspace — Forgot Password</title>
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
        input[type=email] {
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
        .notice {
            background: #0a1a0a;
            border-left: 3px solid #27ae60;
            color: #2ecc71;
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
    <h1>Reset Password</h1>

    <?php if ($submitted): ?>
        <div class="notice">
            If that email exists, a reset link was sent.
        </div>
    <?php endif; ?>

    <form method="post" action="/auth/forgot.php">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required autocomplete="email">

        <button type="submit">Send Reset Link</button>
    </form>

    <div class="footer-link">
        <a href="/auth/login.php">Back to sign in</a>
    </div>
</div>
</body>
</html>
