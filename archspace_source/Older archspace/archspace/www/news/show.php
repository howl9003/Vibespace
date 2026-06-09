<?
  include "libnotice.php";
  $id=$_GET["id"];
  load_entry($id);

  // TODO: get rid of all these damned echos
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
  echo "    <TD CLASS=maintext><IMG SRC=/image/as_login/news/news_title.gif WIDTH=245 HEIGHT=42></TD>";
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
  echo "    <TD CLASS=pointer_h><IMG SRC=/image/as_login/bu_back.gif onClick=history.back()></TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD>&nbsp;</TD>";
  echo "  </TR>";
  echo "</TABLE>";
  echo "</BODY>";
  echo "</HTML>";

  ?>
