<HTML>
<HEAD>
<?
/*
  write.php3

  write notice

  by thedaz (thedaz@maritel.com)
*/
  include "../include/libstd.php";
  include "../include/libportal.php";
  $is_admin = get_is_admin ( "localhost 5000");
  include "../include/libnotice.php";

  $date = date("d/m/y");
  
?>

</HEAD>
<BODY BGCOLOR=000000 LINK=999999 VLINK=999999 ALINK=999999 TEXT=999999 onLoad=document.all.TITLE.focus()>
<?
if ($is_admin == "YES")
{
  if ($_GET["mode"] == "write")
   {
     $msg = write_notice($_POST["DATE"], $_POST["TITLE"],$_POST["DESCRIPTION"]);
   }
  else
  {
?>
<FORM ACTION=write.php?mode=write METHOD=post>
<TABLE>
<TR>
  <TD>Date (mm/dd/yy)</TD>
  <TD><INPUT TYPE=text SIZE=7 NAME=DATE VALUE=<? echo $date; ?>></TD>
</TR>
<TR>
  <TD>Title (max size: 50)</TD>
  <TD><INPUT TYPE=text SIZE=50 NAME=TITLE></TD>
</TR>
<TR>
  <TD VALIGN=top>Article</TD>
  <TD><TEXTAREA NAME=DESCRIPTION ROWS=15 COLS=50 WRAP=Physical></TEXTAREA></TD>
</TR>
<TR>
  <TD COLSPAN=2 ALIGN=center><INPUT TYPE=submit VALUE="E n t e r"></TD>
</TR>
</TABLE>
</FORM>
<?
 }
} 
else 
 {
  $msg = "You do not have permission to do this.";
 }
?>
<HTML>
<HEAD>
<TITLE>as</TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
<meta http-equiv="Cache-Control" content="no-cache"/>
<meta http-equiv="Expires" content="0"/>
<meta http-equiv="Pragma" content="no-cache"/>
<LINK REL="stylesheet" HREF="/archspace.css">
</HEAD>

<BODY BGCOLOR="000000" LINK="999999" VLINK="999999" ALINK="999999">
<TABLE WIDTH="610" BORDER="0" CELLSPACING="0" CELLPADDING="0">
  <TR ALIGN="CENTER">
    <TD>&nbsp;</TD>
  </TR>
  <TR ALIGN="CENTER">
    <TD>&nbsp;</TD>
  </TR>
  <TR>
    <TD>&nbsp;</TD>
  </TR>
  <TR>
    <TD>&nbsp;</TD>
  </TR>
  <TR ALIGN="CENTER">
    <TD CLASS="maintext"><IMG SRC="/image/as_login/result/error.gif" WIDTH="314" HEIGHT="153">
    </TD>
  </TR>
  <TR ALIGN="CENTER">
    <TD>&nbsp; </TD>
  </TR>
  <TR ALIGN="CENTER">
    <TD CLASS="maintext"><? echo $msg; ?></TD>
  </TR>
  <TR ALIGN="CENTER">
    <TD>&nbsp; </TD>
  </TR>
  <TR ALIGN="CENTER">
    <TD><A HREF="/main.php"><IMG SRC="/image/as_login/bu_back.gif" BORDER="0"></A></TD>
  </TR>
  <TR ALIGN="CENTER">
    <TD>&nbsp; </TD>
  </TR>
</TABLE>
</BODY>
</HTML>
