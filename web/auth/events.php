<?php
/**
 * events.php — real-time push bridge (Server-Sent Events).
 *
 * The game is an early-2000s request/response engine: real-time events
 * (incoming diplomatic/council messages, and hostile actions — siege,
 * blockade, raid, privateer, spy ops) are recorded against the victim the
 * instant they happen, but the original only ever surfaced them on the
 * player's next page load.
 *
 * This endpoint turns that into a genuine push. The browser opens a single
 * EventSource here; we hold it open and cheaply drain the engine's
 * /archspace/events.as fingerprint (turn + unread diplomatic/council counts +
 * pending real-time events). When the fingerprint advances we emit an SSE
 * "update" so the client refreshes the news feed immediately. We never author
 * notification text or change game state — only the *timing* of when the
 * existing feed updates changes.
 *
 * Notes:
 *  - Detection is increase-only: a count going *down* (the player reading a
 *    message, or the feed being consumed on navigate-away) never triggers a
 *    refresh; only new events do.
 *  - The stream self-closes after ~55s and the browser's EventSource
 *    reconnects automatically — this keeps PHP-FPM workers from being pinned
 *    indefinitely. Latency to first notice is the poll interval (~2s).
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib.php';

// Only authenticated sessions may open a stream.
$account = current_account();
$token   = $_COOKIE[AS_SESSION_COOKIE] ?? '';
if ($account === null || $token === '') {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: text/plain');
    echo "not authenticated\n";
    exit;
}

// --- SSE response headers --------------------------------------------------
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
// Tell nginx not to buffer this response (so events flush immediately).
header('X-Accel-Buffering: no');

// Flush anything buffered and switch to implicit flushing.
while (ob_get_level() > 0) { ob_end_flush(); }
ob_implicit_flush(true);

ignore_user_abort(false);
set_time_limit(0);

// Where to reach the engine fingerprint endpoint (same nginx, internal).
$internalBase = getenv('AS_INTERNAL_URL') ?: 'http://127.0.0.1';
$eventsUrl    = $internalBase . '/archspace/events.as';

/**
 * Fetch the engine fingerprint, forwarding the player's session cookie so the
 * game resolves the right character. Returns a {t,d,c,n} array or null.
 */
function fetch_fingerprint(string $url, string $token): ?array
{
    // Built-in HTTP stream wrapper (no php-curl dependency). Forward the
    // player's session cookie so the engine resolves their character.
    $ctx = stream_context_create(['http' => [
        'method'  => 'GET',
        'header'  => 'Cookie: ' . AS_SESSION_COOKIE . '=' . $token . "\r\n",
        'timeout' => 4,
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false || $body === '') {
        return null;
    }
    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data['t'], $data['d'], $data['c'], $data['n'])) {
        return null; // e.g. {"auth":0} — no character yet
    }
    return [
        't' => (int)$data['t'],
        'd' => (int)$data['d'],
        'c' => (int)$data['c'],
        'n' => (int)$data['n'],
    ];
}

/** Emit one SSE message. */
function sse_send(string $event, string $data): void
{
    echo "event: {$event}\n";
    echo "data: {$data}\n\n";
    flush();
}

// Tell the client we're live and how often we re-check (so it can show status
// if it wants). retry: also sets the EventSource reconnect delay.
echo "retry: 3000\n";
sse_send('ready', json_encode(['interval' => 2]));

// Establish the baseline WITHOUT pushing: anything already pending was already
// rendered when the player loaded the page, so we only announce *new* events.
$prev  = fetch_fingerprint($eventsUrl, $token);
$start = time();
$pollEvery   = 2;   // seconds between engine drains
$maxLifetime = 55;  // close before FPM/proxy timeouts; client auto-reconnects

while (!connection_aborted() && (time() - $start) < $maxLifetime) {
    sleep($pollEvery);

    if (connection_aborted()) {
        break;
    }

    $cur = fetch_fingerprint($eventsUrl, $token);
    if ($cur === null) {
        // transient (not logged in / no character yet) — keep the line alive
        echo ": ping\n\n";
        flush();
        continue;
    }

    if ($prev === null) {
        $prev = $cur;
        continue;
    }

    // Increase-only: a new turn, a new message, or a new real-time event.
    $advanced = ($cur['t'] > $prev['t'])
             || ($cur['d'] > $prev['d'])
             || ($cur['c'] > $prev['c'])
             || ($cur['n'] > $prev['n']);

    if ($advanced) {
        sse_send('update', json_encode($cur));
    }

    $prev = $cur;
}

// Graceful end; the browser reconnects on its own.
sse_send('bye', '{}');
