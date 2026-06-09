<?
include "include/libstd.php";
include 'include/libportal.php';

$result = "ERROR: You forgot to enter a user name or password";

if(!empty($_POST["username"]) && !empty($_POST["password"]))
{
  require_once "./ipbsdk_class.inc.php";
  $SDK =& new IPBSDK();

//  if ($_SERVER['REQUEST_METHOD'] == "POST") {

	// The form was submitted. Lets authenticate!
	$username  = $_POST['username'];
	$password  = $_POST['password'];
	$anonlogin = "0";//$_POST['anonlogin'] ? "1" : "0";
	$remember  = "1";$_POST['remember'] ? "1" : "0";

	if ($SDK->login($username, $password, 1, $anonlogin, $remember))
  {
	   $result = "ok";
  }
  else
  {
     $result = "ERROR: Forum login failure -- incorrect username or password?";
  }
  // The login worked.
  if ($result == "ok")
  {
    $result = enter_portal($_POST["username"], md5($_POST["password"]),  "localhost 6000");
  }
  if ($result == "ok")
   {
     header("Location: /archspace/login.as");  
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
    <TD><A HREF="/index.php"><IMG SRC="/image/as_login/bu_back.gif" BORDER="0"></A></TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD>&nbsp; </TD>
  </TR>
</TABLE>
</BODY>
</HTML>

