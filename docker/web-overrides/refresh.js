/*
  by thedaz — partial-refresh variant.

  The original goUrl() did `top.location = '/archspace/index.html'`, which
  reloaded the ENTIRE de-framed shell (banner + left menu + content) and
  bounced the player back to the dashboard on every turn tick.

  Here goUrl() reloads only the *content* frame (the .as page that scheduled
  the refresh), so the left navbar stays put, there's no full-page flash, and
  the player stays on whatever page they were viewing — it just refreshes with
  the new turn's data.
*/

function goUrl()
{
  // window here = the "contents" iframe (the current .as page). Reload just it.
  try { window.location.reload(); }
  catch (e) { top.location = '/archspace/index.html'; }
}

function refresh_init(millisec)
{
  setTimeout(goUrl, millisec * 1000);
}

function goUrl_for_portal()
{
  parent.parent.location.href = '/';
}

function refresh_init_for_portal(millisec)
{
  setTimeout(goUrl_for_portal, millisec * 1000);
}
