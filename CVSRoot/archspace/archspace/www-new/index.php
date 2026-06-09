<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Archspace</TITLE>
<LINK REL="stylesheet" HREF="archspace.css">
<LINK REL="stylesheet" HREF="cssLib.css">
<?
require_once "ipbsdk_class.inc.php";
include 'include/libstd.php';
include 'include/libportal.php';
$is_admin = get_is_admin( "localhost 5000");
include 'include/libnotice.php';

function st_open_connection( $SERVER_CON )
{
  $hostcon = explode(" ", $SERVER_CON);

  return fsockopen( $hostcon[0], $hostcon[1] );
}

function st_check_connection( $SERVER_CON )
{
  $server_fd = st_open_connection ( $SERVER_CON );

  if( $server_fd <= 0 )
  {
    return "<FONT COLOR=#AAAAAA><B>OFFLINE</B></FONT>";
  }
  fclose( $server_fd );
  return "<FONT COLOR=#AAAAAA><B>ONLINE</B></FONT>";
}

function st_check_portal_connection()
{
  $server_fd = st_open_connection ( "localhost 4000" );

  if( $server_fd <= 0 )
  {
    //return "<IMG SRC=\"/image/status_down.png\" BORDER=0>";
    return "<FONT COLOR=#AAAAAA><B>OFFLINE</B></FONT>";
  }
  fclose( $server_fd );

  $server_fd = st_open_connection ( "localhost 6000" );

  if( $server_fd <= 0 )
  {
    //return "<IMG SRC=\"/image/status_down.png\" BORDER=0>";
//    return "<B>DOWN!</B>";
    return "<FONT COLOR=#AAAAAA><B>OFFLINE</B></FONT>";
  }

  fclose( $server_fd);
	
  $server_fd = st_open_connection ( "localhost 5000" );

  if( $server_fd <= 0 )
  {
    //return "<IMG SRC=\"/image/status_down.png\" BORDER=0>";
//    return "<B>DOWN!</B>";
    return "<FONT COLOR=#AAAAAA><B>OFFLINE</B></FONT>";
  }

  fclose( $server_fd );


  $server_fd = st_open_connection ( "localhost 11114" );

  if( $server_fd <= 0 )
  {
    //return "<IMG SRC=\"/image/status_down.png\" BORDER=0>";
//    return "<B>DOWN!</B>";
    return "<FONT COLOR=#AAAAAA><B>OFFLINE</B></FONT>";
  }
  fclose( $server_fd );

  //return "<IMG SRC=\"/image/status_ok.png\" BORDER=0>";
//  return "<B>UP!</B>";
    return "<FONT COLOR=#AAAAAA><B>ONLINE</B></FONT>";
}
?>
<SCRIPT LANGUAGE = "JavaScript">
<!-- 
if (navigator.appName.indexOf('Microsoft') == -1) {
 	document.write('<'+'link rel="stylesheet" href="mozilla.css" />');
 }

function trySubmit(event)
{
 if (event.keyCode == 13) // user pressed enter
 {
	document.login.submit();
 }
 return true;
}
 // -->
</SCRIPT>
<SCRIPT TYPE="text/javascript" LANGUAGE=javascript SRC=/jsLib.js></SCRIPT>

</HEAD>
<BODY bgColor=#000000 topmargin=0 onLoad="MM_preloadImages('/images/space_22_over.jpg', '/images/space_22_down.jpg', '/images/space_24_over.jpg', '/images/space_24_down.jpg', 'images/space_04_down.jpg', 'images/space_04_over.jpg', 'images/space_07_over.jpg', 'images/space_07_down.jpg', 'images/space_09_down.jpg', 'images/space_09_over.jpg', 'images/space_11_over.jpg', 'images/space_11_down.jpg', 'images/space_13_over.jpg', 'images/space_13_down.jpg')">
<center>
<form method=post name=login action="/board/login.php">
<input type=hidden name=redirect value="../archspace/login.as">
<input type=hidden name=login value="Log in">
<TABLE cellSpacing=0 cellPadding=0 width=800 border=0>
  <TBODY>
  <TR>
    <TD><IMG height=1 src="images/spacer.gif" width=18></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=68></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=9></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=17></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=55></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=1></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=74></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=39></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=44></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=53></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=41></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=63></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=26></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=46></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=87></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=44></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=71></TD>
    <TD><IMG height=1 src="images/spacer.gif" width=44></TD>
    <TD></TD></TR>
  <TR>
    <TD colSpan=5 rowSpan=5 background="images/space_01.jpg" height=50 width=167 valign=top>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE="text" NAME="username" SIZE=10 MAXLENGTH=20 CLASS=newInput><br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE="password" NAME="password" SIZE=10 MAXLENGTH=20 CLASS=newInput onKeyUp="trySubmit(event)"></TD>
    <TD colSpan=13><IMG height=17 src="images/space_02.jpg" width=633></TD>
    <TD><IMG height=17 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD colSpan=2 rowSpan=5><IMG height=36 src="images/space_03.jpg" width=75></TD>
    <TD rowSpan=3><a href="news/" onMouseOut="MM_swapImgRestore()" onMouseUp="MM_swapImage('NEWS','','images/space_04_over.jpg',1)" onMouseDown="MM_swapImage('NEWS','','images/space_04_down.jpg')" onMouseOver="MM_swapImage('NEWS','','images/space_04_over.jpg')"><IMG height=17 src="/images/space_04.jpg" width=39 border=0 NAME="NEWS"></a></TD>
    <TD colSpan=10><IMG height=4 src="images/space_05.jpg" width=519></TD>
    <TD><IMG height=4 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD rowSpan=6><IMG height=63 src="images/space_06.jpg" width=44></TD>
    <TD><a href="manual/" onMouseOut="MM_swapImgRestore()" onMouseUp="MM_swapImage('MANUAL','','images/space_07_over.jpg',1)" onMouseDown="MM_swapImage('MANUAL','','images/space_07_down.jpg')" onMouseOver="MM_swapImage('MANUAL','','images/space_07_over.jpg')"><IMG height=9 src="images/space_07.jpg" width=53 border=0 NAME="MANUAL"></a></TD>
    <TD rowSpan=6><IMG height=63 src="images/space_08.jpg" width=41></TD>
    <TD colSpan=2><a href="galactic_law/galactic_law.html" onMouseOut="MM_swapImgRestore()" onMouseUp="MM_swapImage('RULES','','images/space_09_over.jpg',1)" onMouseDown="MM_swapImage('RULES','','images/space_09_down.jpg')" onMouseOver="MM_swapImage('RULES','','images/space_09_over.jpg')"><IMG height=9 src="images/space_09.jpg" width=89 border=0 NAME="RULES"></a></TD>
    <TD rowSpan=6><IMG height=63 src="images/space_10.jpg" width=46></TD>
    <TD><a href="encyclopedia/encyclopedia.html" onMouseOut="MM_swapImgRestore()" onMouseUp="MM_swapImage('ENCYCLOPEDIA','','images/space_11_over.jpg',1)" onMouseDown="MM_swapImage('ENCYCLOPEDIA','','images/space_11_down.jpg')" onMouseOver="MM_swapImage('ENCYCLOPEDIA','','images/space_11_over.jpg')"><IMG height=9 src="images/space_11.jpg" width=87 border=0 NAME="ENCYCLOPEDIA"></a></TD>
    <TD rowSpan=6><IMG height=63 src="images/space_12.jpg" width=44></TD>
    <TD><a href="board/" onMouseOut="MM_swapImgRestore()" onMouseUp="MM_swapImage('FORUM','','images/space_13_over.jpg',1)" onMouseDown="MM_swapImage('FORUM','','images/space_13_down.jpg')" onMouseOver="MM_swapImage('FORUM','','images/space_13_over.jpg')"><IMG height=9 src="images/space_13.jpg" width=71 border=0 NAME="FORUM"></a></TD>
    <TD rowSpan=6><IMG height=63 src="images/space_14.jpg" width=44></TD>
    <TD><IMG height=9 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD rowSpan=5><IMG height=54 src="images/space_15.jpg" width=53></TD>
    <TD colSpan=2 rowSpan=5><IMG height=54 src="images/space_16.jpg" width=89></TD>
    <TD rowSpan=5><IMG height=54 src="images/space_17.jpg" width=87></TD>
    <TD rowSpan=5><IMG height=54 src="images/space_18.jpg" width=71></TD>
    <TD><IMG height=4 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD rowSpan=4><IMG height=50 src="images/space_19.jpg" width=39></TD>
    <TD><IMG height=16 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD colSpan=5><IMG height=3 src="images/space_20.jpg" width=167></TD>
    <TD><IMG height=3 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD rowSpan=2><IMG height=31 src="images/space_21.jpg" width=18></TD>
    <TD><a href="register.php" onMouseOut="MM_swapImgRestore()" onMouseUp="MM_swapImage('Image1','','images/space_22_over.jpg',1)"  onMouseDown="MM_swapImage('Image1','','images/space_22_down.jpg')" onMouseOver="MM_swapImage('Image1','','images/space_22_over.jpg',1)"><IMG height=15 src="images/space_22.jpg" width=68 border=0 NAME="Image1"></a></TD>
    <TD rowSpan=2><IMG height=31 src="images/space_23.jpg" width=9></TD>
    <TD colSpan=3><IMG height=15 src="images/space_24.jpg" onMouseOut="MM_swapImgRestore()" onMouseUp="MM_swapImage('Image2','','images/space_24_over.jpg',1); document.login.submit(); return true;"  onMouseDown="MM_swapImage('Image2','','images/space_24_down.jpg')" onMouseOver="MM_swapImage('Image2','','images/space_24_over.jpg',1)" width=73 border=0 NAME="Image2"></TD>
    <TD rowSpan=2><IMG height=31 src="images/space_25.jpg" width=74></TD>
    <TD><IMG height=15 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD><IMG height=16 src="images/space_26.jpg" width=68></TD>
    <TD colSpan=3><IMG height=16 src="images/space_27.jpg" width=73></TD>
    <TD><IMG height=16 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD colSpan=18><IMG height=386 src="images/space_28.jpg" width=800></TD>
    <TD><IMG height=386 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD colSpan=18><IMG height=6 src="images/space_29.jpg" width=800></TD>
    <TD><IMG height=6 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD colSpan=4 rowSpan=2><IMG height=90 src="images/space_30.jpg" width=112></TD>
    <TD colSpan=8 height=76 background="images/space_31.jpg" width=370 valign=top><font color="white"><table cellpadding=1 cellspacing=1> <? notice(3) ?> </table></font></TD>
<!--    <TD colSpan=6 rowSpan=2><IMG height=90 src="images/space_32.jpg" width=318></TD>-->
    <TD colSpan=6 rowSpan=2 background="images/space_32.jpg" width=318 valign=top>

	<FONT face='Verdana, Arial, Helvetica, sans-serif' size=2>
	&nbsp;&nbsp;&nbsp;<B><?php echo active_players(15) ?></B> players online!
	<BR>
	&nbsp;&nbsp;&nbsp;Archspace is currently: <?php echo st_check_connection('localhost 12350') ?>	
	<BR>
	&nbsp;&nbsp;&nbsp;Portal is currently: <?php echo st_check_portal_connection() ?>	
	<BR>
	&nbsp;&nbsp;&nbsp;Server Time: <B><?php echo date("h:i:s A")?></B>
	</FONT>
  </TD>	
    <TD><IMG height=76 src="images/spacer.gif" width=1></TD></TR>
  <TR>
    <TD colSpan=8><IMG height=14 src="images/space_33.jpg" width=370></TD>
    <TD><IMG height=14 src="images/spacer.gif" 
width=1></TD></TR></TBODY></TABLE>
</form>
</center></BODY></HTML>
