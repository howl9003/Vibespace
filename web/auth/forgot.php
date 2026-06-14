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
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));

    if (!verify_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Your form session expired. Please try again.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
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

    $submitted = ($error === '');
}

// Output — forgot-password form (original Archspace styling)
$csrfField = csrf_field();
require_once __DIR__ . '/theme.php';

auth_page_start('Forgot Password');
echo auth_error($error);
if (!empty($submitted)) {
    echo auth_ok('If that email exists, a reset link was sent.');
}
?>
<form method="post" action="/auth/forgot.php">
<?= $csrfField ?>
<?= auth_input('Email', 'email', 'email', '', 'email') ?>
<?= auth_submit('bu_reset.gif', 'reset', 120, 17) ?>
</form>
<?php
auth_page_end('<a href="/auth/login.php">Back to sign in</a>');
