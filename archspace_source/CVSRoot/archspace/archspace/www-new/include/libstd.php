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

	function active_players($turns)
	{
	        mysql_pconnect("localhost", "space", "comconq1") or die("Unable to onnect to to the database");
                mysql_select_db("Archspace") or die("Unable to connect to the Database");
                $res = mysql_query("SELECT turn, news_turn FROM player");
                $num = mysql_num_rows($res);
                $active = 0;
                for ($i=0; $i<$num; $i++)
                {
                        $turn = intval(mysql_result($res, $i, "turn"));
                        $newsturn = intval(mysql_result($res, $i, "news_turn"));
                        if ($turn - $newsturn <= $turns) // 15 turns * 2 minutes == 30 minutes
                                $active++;
                }
		return $active;
	}
}?>
