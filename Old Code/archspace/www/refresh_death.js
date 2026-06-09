/*
  by thedaz

  instead of refresh META tag
*/

function goUrl()
{
  parent.parent.location = '/archspace/index_death.html';
}

function refresh_init(millisec)
{
  setTimeout('goUrl()', millisec*1000);
}
