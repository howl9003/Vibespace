<?
/*
  lib.php3

  ANG php3 library

  by thedaz (thedaz@maritel.com)
*/

/******************************************
 chech_admin()
******************************************/
Function check_admin($AUTH)
{
  if ($AUTH != "ok")
  {
    header("Location: out.html");
  }
}

/******************************************
 login()
******************************************/
Function login($pwd)
{
  if($pwd == "55555")
  {
    return "succ";
  }
  else
  {
    setcookie("AUTH", "logout", time(), "/", "archspace.co.kr");

    return "fail";
  }
}

/******************************************
 notice_main()
******************************************/
Function notice_main()
{
  echo "<UL>";
  echo "<LI><A HREF=write.php3 ALT=\"You can write notice\" TITLE=\"You can write notice\">Write</A>";
  echo "<LI><A HREF=view.php3 ALT=\"You can view notice or delete it\" TITLE=\"You can view notice or delete it\">View</A>";
  echo "<LI><A HREF=logout.php3 ALT=\"logout\" TITLE=\"logout\">Logout</A>";
  echo "<LI><A HREF=http://archspace.magewar.com ALT=\"go to Archspace Site\" TITLE=\"go to Archspace Site\">Go to Archspace</A>";
  echo "</UL>";
}

/******************************************
 connect_db()
******************************************/
Function connect_db($DB_NAME)
{
//  mysql_pconnect("localhost", "space", "rlaclrnr");
  mysql_connect("localhost", "space", "rlaclrnr");

  @mysql_select_db("$DB_NAME") or die("Unable to connect to the Database");
}

/******************************************
  view_notice()
******************************************/
Function view_notice($AUTH)
{
  check_admin($AUTH);

  connect_db("NOTICE");

  mysql_query("LOCK TABLES notice READ");

  $res = mysql_query("SELECT count(*) FROM notice");
  list($total) = mysql_fetch_row($res);
  mysql_free_result($res);

  echo "<BODY BGCOLOR=000000 LINK=999999 VLINK=999999 ALINK=999999 TEXT=999999>";
  echo "There are <I>$total</I> article posted so far.";
  echo "<BR>";
  echo "<A HREF=notice.html>Main</A>";
  echo "<BR>";

  echo "<FORM ACTION=lib.php3?mode=delete METHOD=post>";
  echo "<TABLE WIDTH=700 BORDER=1 CELLPADING=0 CELLSPACING=0>";
  echo "<TR>";
  echo "  <TD><INPUT TYPE=submit VALUE=Del></TD>";
  echo "  <TD ALIGN=center>Date</TD>";
  echo "  <TD ALIGN=center WIDTH=150>Title</TD>";
  echo "  <TD ALIGN=center>Description</TD>";
  echo "</TR>";

  $res = mysql_query("SELECT id, date, title, description FROM notice ORDER BY id DESC");
  $res2 = mysql_query("SELECT id FROM notice ORDER BY id DESC LIMIT 1");
  list($last_id) = mysql_fetch_row($res2);
  mysql_free_result($res2);

  while (list($id, $date, $title, $description) = mysql_fetch_row($res) )
  {
    $delnum = "del".$id;
	$desc = nl2br($description);
    echo "<TR>";
    echo "  <TD VALIGN=top TITLE=$id><INPUT TYPE=checkbox NAME=$delnum VALUE=del></TD>";
    echo "  <TD VALIGN=top>$date</TD>";
    echo "  <TD VALIGN=top>$title</TD>";
    echo "  <TD>$desc</TD>";
    echo "</TR>";
  }
  mysql_free_result($res);

  echo "</TABLE>";

  echo "<INPUT TYPE=hidden NAME=num VALUE=$last_id>";
  echo "</FORM>";

  mysql_query("UNLOCK TABLES");
  mysql_close();
}

/******************************************
  write_notice()
******************************************/
Function write_notice($DATE, $TITLE, $DESCRIPTION)
{
  $TITLE = addslashes ($TITLE);
  $DESCRIPTION = addslashes ($DESCRIPTION);

  connect_db("NOTICE");

  mysql_query("LOCK TABLES notice WRITE");

  mysql_query("INSERT INTO notice (date, title, description) VALUES ('$DATE', '$TITLE', '$DESCRIPTION')");
  mysql_query("UNLOCK TABLES");
  mysql_close();

  echo "NOTICE DB successfully updated!";
  echo "<BR><BR>";
  echo "<A HREF=notice.html>Main</A>";
}

/******************************************
  notice()
******************************************/
Function notice($row)
{
  connect_db("NOTICE");

  mysql_query("LOCK TABLES notice READ");

  $res = mysql_query("SELECT id, date, title FROM notice ORDER BY id DESC LIMIT $row");

  while (list($id, $date, $title) = mysql_fetch_row($res) )
  {
    echo "<TR>"; 
    echo "  <TD VALIGN=top CLASS=maintext><FONT COLOR=666666>$date</FONT></TD>";
    echo "  <TD CLASS=maintext><A HREF=notice/lib.php3?mode=select&id=$id onMouseOver=\"window.status=('$title'); return true;\" onMouseOut=\"window.status=(''); return true;\">$title</A></TD>";
    echo "</TR>";
  }
  mysql_free_result($res);

  mysql_query("UNLOCK TABLES");
  mysql_close();
}

/******************************************
  view()
******************************************/
Function view($id)
{
  connect_db("NOTICE");

  mysql_query("LOCK TABLES notice READ");

  $res = mysql_query("SELECT date, title, description FROM notice WHERE id=$id");

  list($date, $title, $description) = mysql_fetch_row($res);
  mysql_free_result($res);
  $desc = nl2br($description);

  mysql_query("UNLOCK TABLES");
  mysql_close();

  echo "<HTML>";
  echo "<HEAD>";
  echo "<TITLE>as</TITLE>";
  echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; iso-8859-1\">";
  echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\"/>";
  echo "<meta http-equiv=\"Expires\" content=\"0\"/>";
  echo "<meta http-equiv=\"Pragma\" content=\"no-cache\"/>";
  echo "<LINK REL=\"stylesheet\" HREF=\"/archspace.css\">";
  echo "<LINK REL=\"stylesheet\" HREF=\"/cssLib.css\">";
  echo "</HEAD>";
  echo "<BODY BGCOLOR=000000 LINK=999999 VLINK=999999 ALINK=999999>";
  echo "<TABLE WIDTH=610 BORDER=0 CELLSPACING=0 CELLPADDING=0>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD CLASS=maintext><IMG SRC=http://space.magewar.com/image/as_login/news/news_title.gif WIDTH=245 HEIGHT=42></TD>";
  echo "  </TR>";
  echo "  <TR>";
  echo "    <TD>&nbsp;</TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TH CLASS=maintext>$date $title</TH>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD>&nbsp;</TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD>";
  echo "      <TABLE WIDTH=500 BORDER=0 CELLSPACING=1 CELLPADDING=0>";
  echo "        <TR>";
  echo "          <TD CLASS=maintext>$desc</TD>";
  echo "        </TR>";
  echo "      </TABLE>";
  echo "    </TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD>&nbsp;</TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD CLASS=pointer_h><IMG SRC=http://space.magewar.com/image/as_login/bu_back.gif onClick=history.back()></TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD>&nbsp;</TD>";
  echo "  </TR>";
  echo "</TABLE>";
  echo "</BODY>";
  echo "</HTML>";

  mysql_query("UNLOCK TABLES");
//  mysql_close();
}

/******************************************
  all()
******************************************/
Function all()
{
  connect_db("NOTICE");

  mysql_query("LOCK TABLES notice READ"); 

  $res = mysql_query("SELECT id, date, title FROM notice ORDER BY id DESC");

  while (list($id, $date, $title) = mysql_fetch_row($res) )
  {
    echo "<TR>";
    echo "  <TD CLASS=maintext WIDTH=57 ALIGN=RIGHT><FONT COLOR=666666>$date </FONT></TD>";
    echo "  <TD CLASS=maintext WIDTH=12>&nbsp;</TD>";
    echo "  <TD CLASS=maintext WIDTH=374><A HREF=../notice/lib.php3?mode=select&id=$id onMouseOver=\"window.status=('$title'); return true;\" onMouseOut=\"window.status=(''); return true;\">$title</A></TD>";
    echo "</TR>";
  }
  mysql_free_result($res);

  mysql_query("UNLOCK TABLES");
  mysql_close();
}




// if mode is "write", call write_notice()
if ($mode == "write")
{
  check_admin($AUTH);

  write_notice($DATE, $TITLE, $DESCRIPTION);
}
// if mode is "view", call view_notice()
else if ($mode == "view")
{
  view_notice($AUTH);
}
// if mode == delete, call delete_notice()
else if ($mode == "delete")
{
  check_admin($AUTH);

  connect_db("NOTICE");

  mysql_query("LOCK TABLES notice WRITE");

  for ($id=1; $id<=$num; $id++)
  {
    $delnum = "del".$id;

    if ($$delnum == "del")
    {
      mysql_query("DELETE FROM notice WHERE id=$id");
      echo "notice #$id has been successfully deleted!<BR>";
    }
  }

  echo "<BR>";
  echo "<A HREF=lib.php3?mode=view>View</A>";

  mysql_query("UNLOCK TABLES");
  mysql_close();
}
else if ($mode == "select")
{
  view($id);
}
?>
