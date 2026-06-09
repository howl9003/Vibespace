<?
if (!$libstd_include)
{
	$libstd_include = true;
	include "config.php";
	
	function create_db_connection($db)
	{
	  // grab the global vars from config.php
          global $PortalHost;
	  global $PortalUser;
	  global $PortalPassword;	  

	  $link = mysql_pconnect($PortalHost, $PortalUser, $PortalPassword);
	  if (!$link)
	   {
		die('Could not connect: ' . mysql_error());
	   }
	   
	  $db_selected = mysql_select_db($db, $link);

	  if (!$db_selected)
	   {
		die('Could not use \'$db\': ' . mysql_error());
	   }
	}
}?>
