/*
  notifications.js — real-time push client.

  Loaded once in the de-framed shell (archspace/index.html). It opens a single
  EventSource to the SSE bridge (/auth/events.php) and, when the server pushes
  an "update" (a new turn, an incoming diplomatic/council message, or a hostile
  action against the player — siege/blockade/raid/privateer/spy), it refreshes
  the news feed *in place* so the event appears in real time.

  We deliberately change only the *timing*: the content and appearance stay
  exactly as the original. The news feed lives on main.as (the dashboard), so a
  push simply reloads the content frame when it's showing main.as. On any other
  page the event is already queued in the feed and shows when the player returns
  to the dashboard — same as the original, minus the wait. No toasts, no new UI.
*/

(function () {
  if (!window.EventSource) return; // very old browser: silently no-op

  // The content iframe (named "contents" in the shell).
  function contentFrame() {
    return document.querySelector('iframe.as-contents')
        || document.querySelector('iframe[name="contents"]');
  }

  // Is the content frame currently showing the dashboard (main.as)?
  function onDashboard(frame) {
    try {
      var path = frame.contentWindow.location.pathname || '';
      return /\/main\.as$/.test(path) || path === '' || path === 'about:blank';
    } catch (e) {
      // Cross-origin shouldn't happen (same host); if it does, be conservative.
      return false;
    }
  }

  function refreshDashboard() {
    var frame = contentFrame();
    if (!frame) return;
    if (onDashboard(frame)) {
      try { frame.contentWindow.location.reload(); } catch (e) { /* ignore */ }
    }
    // If the player isn't on the dashboard, do nothing: the event is already in
    // the feed and will render when they navigate back to main.as.
  }

  function connect() {
    var es = new EventSource('/auth/events.php');

    es.addEventListener('update', function () {
      refreshDashboard();
    });

    // The bridge self-closes every ~55s; EventSource reconnects on its own, but
    // if it errors out hard we re-open after a short delay as a backstop.
    es.addEventListener('error', function () {
      if (es.readyState === EventSource.CLOSED) {
        setTimeout(connect, 3000);
      }
    });
  }

  connect();
})();
