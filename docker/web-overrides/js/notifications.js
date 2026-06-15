/*
  notifications.js — real-time push client + live top-bar updates.

  Loaded once in the de-framed shell (archspace/index.html). It opens a single
  EventSource to the SSE bridge (/auth/events.php). On a push ("update" — a new
  turn, an incoming diplomatic/council message, or a hostile action: siege /
  blockade / raid / privateer / spy) it:

    * on the dashboard (main.as) — reloads the content frame, exactly as before,
      so the whole summary refreshes; and
    * on every OTHER page — updates the top-bar stats (PP / Planet / Power) *in
      place*, with no reload and no lost form input.

  It also reconciles the top bar against the engine's live values after every
  content navigation, so a page that rendered a pre-spend PP (the spend
  acknowledgement pages) is corrected the instant it loads. A value that actually
  changed is briefly flashed so the player notices.

  Contract with the engine: head_title.cc tags the three values as
  <span class="as-stat" data-as-stat="pp|planet|power"> on every page, and
  /archspace/events.as exposes their current strings as JSON.
*/

(function () {
  var STAT_KEYS  = ['pp', 'planet', 'power'];
  var EVENTS_URL = '/archspace/events.as';
  var hasSSE     = !!window.EventSource;
  var hasFetch   = !!window.fetch;
  if (!hasSSE && !hasFetch) return; // very old browser: silently no-op

  // The content iframe (named "contents" in the shell).
  function contentFrame() {
    return document.querySelector('iframe.as-contents')
        || document.querySelector('iframe[name="contents"]');
  }

  function contentDoc(frame) {
    // Same host, so this is same-origin; guard anyway.
    try { return frame.contentDocument || frame.contentWindow.document; }
    catch (e) { return null; }
  }

  // Is the content frame currently showing the dashboard (main.as)?
  function onDashboard(frame) {
    try {
      var path = frame.contentWindow.location.pathname || '';
      return /\/main\.as$/.test(path) || path === '' || path === 'about:blank';
    } catch (e) {
      return false; // be conservative if it somehow isn't readable
    }
  }

  // Briefly highlight a span so the player notices the value moved.
  function flash(el) {
    el.classList.remove('as-stat-flash');
    void el.offsetWidth;            // force reflow so the animation restarts
    el.classList.add('as-stat-flash');
  }

  // Patch the top-bar spans in the content frame from a stats object. Only a
  // field whose displayed text actually changed is touched (and flashed).
  function patchStats(stats) {
    if (!stats) return;
    var frame = contentFrame();
    if (!frame) return;
    var doc = contentDoc(frame);
    if (!doc) return;
    STAT_KEYS.forEach(function (key) {
      if (stats[key] == null) return;
      var next = String(stats[key]);
      var el = doc.querySelector('[data-as-stat="' + key + '"]');
      if (!el || el.textContent === next) return;
      el.textContent = next;
      flash(el);
    });
  }

  // After a navigation, reconcile the top bar against the engine's live values.
  // This is what corrects the spend acknowledgement pages (which render the
  // pre-spend PP). The dashboard is already fresh on load and reloads on a push,
  // so skip it.
  function reconcile() {
    if (!hasFetch) return;
    var frame = contentFrame();
    if (!frame || onDashboard(frame)) return;
    fetch(EVENTS_URL, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) { if (j && !j.auth) patchStats(j); })
      .catch(function () { /* transient — ignore */ });
  }

  function onUpdate(e) {
    var frame = contentFrame();
    if (!frame) return;
    if (onDashboard(frame)) {
      // Keep the original behavior on the dashboard: reload so the full summary
      // (not just the trio) refreshes in place.
      try { frame.contentWindow.location.reload(); } catch (e2) { /* ignore */ }
      return;
    }
    var stats = null;
    try { stats = JSON.parse(e.data); } catch (e2) { /* not JSON — ignore */ }
    patchStats(stats);
  }

  function connect() {
    var es = new EventSource('/auth/events.php');

    es.addEventListener('update', onUpdate);

    // The bridge self-closes every ~55s; EventSource reconnects on its own, but
    // if it errors out hard we re-open after a short delay as a backstop.
    es.addEventListener('error', function () {
      if (es.readyState === EventSource.CLOSED) {
        setTimeout(connect, 3000);
      }
    });
  }

  // Reconcile the top bar on every content navigation (incl. spend results).
  var frame = contentFrame();
  if (frame) frame.addEventListener('load', reconcile);

  if (hasSSE) connect();
})();
