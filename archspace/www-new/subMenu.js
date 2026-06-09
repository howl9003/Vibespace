function subMenu(curTitle)
{
  if (curTitle == 'domestic')
  {
    top.frames['contents'].location ='/archspace/domestic/domestic.as';
  }
  else if (curTitle == 'diplomacy')
  {
    top.frames['contents'].location ='/archspace/diplomacy/diplomacy.as';
  }
  else if (curTitle == 'fleet')
  {
    top.frames['contents'].location ='/archspace/fleet/fleet.as';
  }
  else if (curTitle == 'council')
  {
    top.frames['contents'].location ='/archspace/council/council.as';
  }
  else if (curTitle == 'blackmarket')
  {
    top.frames['contents'].location ='/archspace/black_market/black_market.as';
  }
  else if (curTitle == 'info')
  {
    top.frames['contents'].location ='/archspace/info/info.as';
  }
  else if (curTitle == 'forum')
  {
    top.frames['contents'].location ='/archspace/forum/forum.as';
  }
  else if (curTitle == 'help')
  {
    top.frames['contents'].location ='/archspace/help/help.as';
  }

/* code for IE */
  if (document.all)
  {
    thisMenu = eval("document.all." + curTitle + ".style");

    if (thisMenu.display == "block")
    {
      thisMenu.display = "none";
    }
    else
    {
      thisMenu.display = "block";
    }
    return false;
  }
  
/* code for NS */
  else if (document.layers)
  {
    thisMenu = eval("document.layers." + curTitle + ".visibility");

/*
    if (thisMenu.display == "visible")
    {
      document.layers[curTitle].visibility = "hidden";
    }
    else
    {
      document.layers[curTitle].visibility = "visible";
    }
*/

    if (thisMenu.display == "show")
    {
      document.layers[curTitle].visibility = "hide";
    }
    else
    {
      document.layers[curTitle].visibility = "show";
    }

    return false;
  }
}
