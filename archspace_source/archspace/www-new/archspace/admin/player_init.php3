<?
	include "libadmin.php";
  include "libportal.php";

  $PORTAL_ID = $_POST['PORTAL_ID'];
  $PORTAL_NAME = $_POST['PORTAL_NAME'];
  
  if (!$PORTAL_ID)
    $PORTAL_ID = $_GET['PORTAL_ID'];
  if (!$PORTAL_NAME)
    $PORTAL_NAME = $_GET['PORTAL_NAME'];

	$IsAdmin = get_is_admin($auth_server_con);

	if ($IsAdmin != "YES")
	{
		header("Location: not_admin_error.html");
	}
	else
	{
		mysql_connect("localhost", "space", "comconq1");

/*  // debugging shiz
		echo $PortalHost;
		echo "<BR>";
		echo $PortalUser;
		echo "<BR>";
		echo $PortalPassword;
		echo "<BR>";
		echo $PortalDatabase;
		echo "<BR>";
*/
    // Load Portal Info
		@mysql_select_db("EntryServer") or die("Unable to connect to the Database");

		mysql_query("LOCK TABLES Users READ");

		if (!$PORTAL_ID && !$PORTAL_NAME)
		{
			mysql_query("UNLOCK TABLES");
			mysql_close();

			header("Location: null_data.html");
		}
		else
		{
			$Result;

			if ($PORTAL_ID)
			{
				$Result = mysql_query("SELECT * FROM Users WHERE id=$PORTAL_ID");
			}
			else
			{
				$Result = mysql_query("SELECT * FROM Users WHERE name='$PORTAL_NAME'");
			}

			if (!$Result)
			{
				mysql_query("UNLOCK TABLES");
				mysql_close();

				header("Location: no_player_error.html");
			}
			else
			{
				$Row = mysql_fetch_array($Result);

				$ID = $Row["id"];
				$Name = $Row["name"];
				$Password = $Row["password"];
				$EMail = $Row["email"];
				list($FirstName ,$LastName) = split(" ", $Row["name"]);
        //$FirstName = $Row["first_name"];
				//$LastName = $Row["last_name"];
				//$ICQ = $Row["icq"];
				$Gender = $Row["sex"];
				$Age = $Row["age"];
				$Country = $Row["country"];
				$CreatedTime = $Row["firstlogin"];
        //$HowKnowUs = $Row["howknowus"];
				//$CreatedTime = $Row["c_time"];
				//$LastLogin = $Row["last_login"];
				$IsAdmin = $Row["is_admin"];

				mysql_query("UNLOCK TABLES");
			}

      // Load archspace game info (if any)
  		@mysql_select_db("Archspace") or die("Unable to connect to the Database");
      // no table locking today ;p
      //  mysql_query("LOCK TABLES  READ");
			if ($PORTAL_ID)
			{
				$Result = mysql_query("SELECT * FROM player WHERE portal_id=$PORTAL_ID");
				if ($Result)
				{
         $Row = mysql_fetch_array($Result);
				 $GAME_ID = $Row['game_id'];
			  }
			}
		  mysql_close();	
		}
	}
?>
<HTML>
<BODY bgcolor=black text=white>
<?
/*// More debugging shiz
echo "Debug!!";
echo $ID;
echo " ";
echo $Name;
echo " ";
echo $Password;
echo " ";
echo $EMail;
echo $FirstName;
echo $LastName;
echo $ICQ;*/
?>
<FORM METHOD=post name=query ACTION=player.as>
<!-- Player Portal Info -->
<INPUT TYPE=hidden NAME="PORTAL_ID" VALUE=<? echo $ID ?>>
<INPUT TYPE=hidden NAME="PORTAL_NAME" VALUE=<? echo $Name ?>>
<INPUT TYPE=hidden NAME="PORTAL_PASSWORD" VALUE=<? echo $Password ?>>
<INPUT TYPE=hidden NAME="PORTAL_EMAIL" VALUE=<? echo $EMail ?>>
<INPUT TYPE=hidden NAME="PORTAL_FIRST_NAME" VALUE=<? echo $FirstName ?>>
<INPUT TYPE=hidden NAME="PORTAL_LAST_NAME" VALUE=<? echo $LastName ?>>
<INPUT TYPE=hidden NAME="PORTAL_ICQ" VALUE=<? echo $ICQ ?>>
<INPUT TYPE=hidden NAME="PORTAL_GENDER" VALUE=<? echo $Gender ?>>
<INPUT TYPE=hidden NAME="PORTAL_AGE" VALUE=<? echo $Age ?>>
<INPUT TYPE=hidden NAME="PORTAL_COUNTRY" VALUE=<? echo $Country ?>>
<INPUT TYPE=hidden NAME="PORTAL_HOW_KNOW_US" VALUE=<? echo $HowKnowUs ?>>
<INPUT TYPE=hidden NAME="PORTAL_CREATED_TIME" VALUE=<? echo $CreatedTime ?>>
<INPUT TYPE=hidden NAME="PORTAL_LAST_LOGIN" VALUE=<? echo $LastLogin ?>>
<INPUT TYPE=hidden NAME="PORTAL_IS_ADMIN" VALUE=<? echo $IsAdmin ?>>
<!-- Player Game Info (if any)-->
<?
if (($GAME_ID) && !empty($GAME_ID)){ ?>
<INPUT TYPE=hidden NAME="GAME_ID" VALUE=<? echo $GAME_ID ?>>
<? } ?>
<!-- Other variables -- >
<INPUT TYPE=hidden NAME="PLAYER_INIT" VALUE=YES>
Now portal information is loaded.<BR>
Please click this if not automatically redirected. <INPUT TYPE=submit VALUE="Next">
</FORM>
<script language="JavaScript">
query.submit();
</script>
</BODY>
</HTML>

