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
    if (!verify_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Your form session expired. Please try again.';
    } elseif ($account === null) {
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
$tokenValid = ($account !== null);

// Output — set-new-password form (original Archspace styling)
$csrfField = csrf_field();
require_once __DIR__ . '/theme.php';

auth_page_start('Set New Password');
echo auth_error($error);

if ($tokenValid):
?>
<form method="post" action="/auth/reset.php">
<?= $csrfField ?>
<input type="hidden" name="token" value="<?= h($token) ?>">
<?= auth_input('New password (min. 8)', 'password', 'password', '', 'new-password') ?>
<?= auth_input('Confirm new password', 'password2', 'password', '', 'new-password') ?>
<?= auth_submit('bu_ok.gif', 'set password', 120, 20) ?>
</form>
<?php else: ?>
<p style="color:#e88">This reset link is invalid or has expired.</p>
<a href="/auth/forgot.php"><img src="/image/as_login/bu_reset.gif" width="120" height="17" border="0" alt="request a new link"></a>
<?php endif;
auth_page_end('<a href="/auth/login.php">Back to sign in</a>');
