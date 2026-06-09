<?
/*
  write.php3

  write notice

  by thedaz (thedaz@maritel.com)
*/

  include "lib.php3";

  check_admin($AUTH);

  $date = date("m/d/y");
?>
<BODY BGCOLOR=000000 LINK=999999 VLINK=999999 ALINK=999999 TEXT=999999 onLoad=document.all.TITLE.focus()>

<FORM ACTION=lib.php3?mode=write METHOD=post>
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
