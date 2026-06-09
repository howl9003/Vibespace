<HTML>
<HEAD>
<TITLE>as</TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
<meta http-equiv="Cache-Control" content="no-cache"/>
<meta http-equiv="Expires" content="0"/>
<meta http-equiv="Pragma" content="no-cache"/>
<LINK REL="stylesheet" type="text/css" HREF="./archspace.css">
<LINK REL="stylesheet" type="text/css" HREF="./cssLib.css">
<?
require_once "./ipbsdk_class.inc.php";
include 'include/libstd.php';
include 'include/libportal.php';
$is_admin = get_is_admin( "localhost 5000");
include "include/libnotice.php";
?>
</HEAD>

<BODY BGCOLOR="000000" LINK="999999" VLINK="999999" ALINK="999999">
<TABLE WIDTH="610" BORDER="0" CELLSPACING="0" CELLPADDING="0">
  <TR ALIGN="CENTER"> 
    <TD><IMG SRC="/image/as_login/mainimg.gif" BORDER="0"></TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD>&nbsp;</TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD> 
	<?
		$name = $HTTP_COOKIE_VARS["NAME"];
		if(!empty($name))
		{
	?>
      <TABLE WIDTH="400" BORDER="0" CELLSPACING="1" CELLPADDING="0">
        <TR ALIGN="CENTER"> 
          <TD COLSPAN="2" CLASS="maintext">Welcome, <? echo $name; ?>!</TD>
        </TR>
        <TR> 
          <TD>&nbsp;</TD>
          <TD>&nbsp;</TD>
        </TR>
        <TR> 
          <TD COLSPAN="2"><A HREF="/archspace/login.as"><IMG SRC="/image/as_login/bu_enter_as.gif" WIDTH="246" HEIGHT="16" BORDER="0"></A><A HREF="logout.phtml"><IMG SRC="/image/as_login/bu_logout.gif" BORDER="0"></A></TD>
        </TR>
        <TR> 
          <TD>&nbsp;</TD>
          <TD>&nbsp;</TD>
        </TR>
      </TABLE>
	 <?
	 	}else
		{
	?>
	<table border=0 width=150 cellspacing=0 cellpadding=3 bgcolor=252525>
                <form name = "login" action="login.php" method="post">
                <tr class="txt2">  
                <td>
                    <font color="#FFFFFF">&nbsp;Username</font><br>
                    &nbsp;&nbsp;
                    <input size="13" maxlength="15" name="username" value="" class="newInput">
                </td>
                <td>
                  <font color="#FFFFFF">&nbsp;Password</font><br>
                    &nbsp;&nbsp;
                    <input size="13" maxlength="30" name="password" type="password" value="" class="newInput">
                </td>
              </tr>
              <tr class="txt2">
                  <td valign="bottom" colspan=2>
                    <div align="right"><br>
                      <a href="javascript:document.login.submit()"><img src="signin.gif" width="47" height="13" border="0"></a> 
                      <a href="register.php"> <img src="register.gif" width="47" height="13" border="0"><br></a>
<!--
<a href="/html/account.html"><font color="B5721E">Forgot your password?<br></font></a> 
-->
                    </div>
                  </td>
              </tr>
              </form>
            </table>
	<?
		}
	?>
    </TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD HEIGHT="25"><IMG SRC="/image/as_login/line.gif" WIDTH="486" HEIGHT="3"></TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD> 
      <TABLE WIDTH="570" BORDER="0" CELLSPACING="1" CELLPADDING="0">
        <TR VALIGN="TOP"> 
          <TD ALIGN="LEFT"><A HREF=/news/><IMG SRC="/image/as_login/new.gif" BORDER=0></A></TD>
          <TD>&nbsp;</TD>
          <TD ALIGN="RIGHT"><IMG SRC="/image/as_login/info.gif" WIDTH="267" HEIGHT="24"></TD>
        </TR>
        <TR> 
          <TD ALIGN="CENTER" WIDTH="280" VALIGN="TOP">
            <TABLE WIDTH="275" BORDER="0" CELLSPACING="0" CELLPADDING="1" ALIGN=LEFT>
			<?
			  notice(5);
			?>
              <TR> 
                <TD CLASS="maintext" ALIGN=right VALGIN="BOTTOM" COLSPAN=3><A HREF=/news/><FONT SIZE=-1>go to news archive</FONT></A></TD>
              </TR>
            </TABLE>
          </TD>
          <TD WIDTH="100">&nbsp;</TD>
          <TD WIDTH="300" VALIGN="TOP"> 
		<A HREF="/tracker/">Archcave Bug Tracker and Feature Request</a>
		<?
		  if ($is_admin == "YES")
		    {
	   	     echo '<BR>';		
		     echo '<font color=white><b><img src="/image/as_login/admin.gif" alt="Admin Pages:"></b></font><br>';
 		     echo '<A HREF="/CVS/">Archcave CVS</A><br>';
		     echo '<A HREF="/doc/tmp/html/">Archspace Documentation</A><br>';		
		     echo '<A HREF="http://www.stack.nl/~dimitri/doxygen/docblocks.html">Code Documentation Examples</A><br>';
		     echo '<A HREF="/phpsysinfo/?template=black">System Information</A><br>';
		    }
		?>
          </TD>
        </TR>
      </TABLE>
    </TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD>&nbsp;</TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD CLASS="maintext"><FONT SIZE="2">This site is best viewed with Explorer 
      6.0+ and a resolution of more than 1024x768.<BR>
      Copyright 2004  <A HREF="http://www.kenisware.org" TARGET="_blank">KENISWARE.ORG</A> 
      All rights reserved </FONT></TD>
  </TR>
  <TR ALIGN="CENTER"> 
    <TD CLASS="maintext">&nbsp; </TD>
  </TR>
</TABLE>
</BODY>
</HTML>
