<?php
/**
 * logout.php — Terminate the current session.
 *
 * Deletes the session row from the database, clears the as_session cookie,
 * and redirects to /auth/login.php.  Safe to visit even when not logged in.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$token = $_COOKIE[AS_SESSION_COOKIE] ?? '';

if ($token !== '') {
    delete_session($token);
}

header('Location: /auth/login.php', true, 303);
exit;
