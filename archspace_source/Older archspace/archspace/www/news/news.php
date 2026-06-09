<?
  include '../include/libstd.php';
  include '../include/libnotice.php';
?>
<HTML>
<HEAD>
<TITLE>as</TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; iso-8859-1">
<meta http-equiv="Cache-Control" content="no-cache"/>
<meta http-equiv="Expires" content="0"/>
<meta http-equiv="Pragma" content="no-cache"/>
<LINK REL="stylesheet" HREF="/archspace.css">
<LINK REL="stylesheet" HREF="/cssLib.css">
</HEAD>

<BODY BGCOLOR="000000" LINK="999999" VLINK="999999" ALINK="999999">
<TABLE WIDTH="610" BORDER="0" CELLSPACING="0" CELLPADDING="0">
  <TR ALIGN="CENTER"> 
    <TD CLASS="maintext"><IMG SRC="/image/as_login/news/news_title.gif" WIDTH="245" HEIGHT="42"></TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD>&nbsp; </TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD> 
      <TABLE WIDTH="450" BORDER="0" CELLSPACING="1" CELLPADDING="0">
        <TR> 
          <TD CLASS="maintext" WIDTH="60" ALIGN="LEFT"><FONT COLOR="white">Date: </FONT></TD>
          <TD CLASS="maintext" WIDTH="35"><FONT COLOR="white">ID: </FONT></TD>
          <TD CLASS="maintext" WIDTH="375"><FONT COLOR="white">&nbsp;TITLE: </FONT></TD>
        </TR>
		<?
		  all();
		?>
      </TABLE>
    </TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD>&nbsp; </TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD CLASS=pointer_h><IMG SRC="/image/as_login/bu_back.gif" WIDTH="120" HEIGHT="16" onClick=history.back()></TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD>&nbsp;</TD>
  </TR>
</TABLE>
</BODY>
</HTML>

