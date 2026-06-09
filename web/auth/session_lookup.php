<?php
/**
 * session_lookup.php — Internal session-to-account resolver.
 *
 * Intended to be called from the same host (CGI adapter / game server glue).
 * Reads the as_session cookie from the incoming request (or falls back to
 * reading it as a query-string parameter "token" for non-cookie callers),
 * validates it against the sessions table, and returns:
 *
 *   200  {"id": <int>, "is_admin": 0|1, "email": "<string>"}
 *   401  {"error": "no valid session"}
 *
 * This file can also be included by other PHP scripts that need the
 * current_account() function directly — just require_once it and call
 * current_account() from lib.php.
 *
 * No external callers should be able to reach this endpoint; restrict it
 * at the web-server level (e.g. allow only 127.0.0.1 in nginx/apache config).
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib.php';

// Allow token to be passed as a query parameter as a fallback for the adapter
// (some CGI environments cannot forward cookies easily).
if (!isset($_COOKIE[AS_SESSION_COOKIE])) {
    $qsToken = trim((string)($_GET['token'] ?? ''));
    if ($qsToken !== '') {
        // Inject into $_COOKIE so current_account() picks it up transparently.
        $_COOKIE[AS_SESSION_COOKIE] = $qsToken;
    }
}

$account = current_account();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($account === null) {
    http_response_code(401);
    echo json_encode(['error' => 'no valid session'], JSON_UNESCAPED_SLASHES);
} else {
    http_response_code(200);
    echo json_encode(
        [
            'id'       => $account['id'],
            'is_admin' => $account['is_admin'],
            'email'    => $account['email'],
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}
