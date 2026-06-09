/*
  by thedaz

  instead of refresh META tag
*/

function goUrl()
{
  parent.parent.location = '/archspace/index.html';
}

function refresh_init(millisec)
{
  setTimeout('goUrl()', millisec*1000);
}

function goUrl_for_portal()
{
  parent.parent.location.href = 'http://www.magewar.com';
}

function refresh_init_for_portal(millisec)
{
  setTimeout('goUrl_for_portal()', millisec*1000);
}

