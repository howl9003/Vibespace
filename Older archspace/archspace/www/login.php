<?
include "include/libstd.php";
include 'include/libportal.php';

$result = "ERROR: You forgot to enter a user name or password";

// attempt to login - if successfull back to main page
/*function is_admin()
{
 create_db_connection("EntryServer");
 $res = mysql_query("SELECT is_admin FROM Users WHERE name='".$_POST["username"]."' and password='".$_POST["password"]."'");

 $val = mysql_fetch_object($res);
 mysql_free_result($res);

 if ($val == false || $val->is_admin != "YES")
     return false;
 return true;
}*/

if(!empty($_POST["username"]) && !empty($_POST["password"]))
{
  $result = enter_portal($_POST["username"], $_POST["password"],  "localhost 6000");
 
  if ($result == "ok")
   {
     header("Location: /main.php");  
     exit();
   }
}
// otherwise output correct error page!
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
    <TD CLASS="maintext"><? echo $result; ?></TD>
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

