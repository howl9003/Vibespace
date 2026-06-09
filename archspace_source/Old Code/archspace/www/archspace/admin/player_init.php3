<?
	include "/space/portal/php/portal.php3";

	$IsAdmin = get_is_admin($auth_server_con);

	if ($IsAdmin != "YES")
	{
		header("Location: not_admin_error.html");
	}
	else
	{
		mysql_connect("www.magewar.com", "space", "rlaclrnr");

		echo $PortalHost;
		echo "<BR>";
		echo $PortalUser;
		echo "<BR>";
		echo $PortalPassword;
		echo "<BR>";
		echo $PortalDatabase;
		echo "<BR>";
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
				$FirstName = $Row["first_name"];
				$LastName = $Row["last_name"];
				$ICQ = $Row["icq"];
				$Gender = $Row["gender"];
				$Age = $Row["age"];
				$Country = $Row["country"];
				$HowKnowUs = $Row["howknowus"];
				$CreatedTime = $Row["c_time"];
				$LastLogin = $Row["last_login"];
				$IsAdmin = $Row["is_admin"];

				mysql_query("UNLOCK TABLES");
				mysql_close();
			}
		}
	}
?>
<HTML>
<BODY>
<FORM METHOD=post ACTION=player.as>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_ID" VALUE=<? echo $ID ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_NAME" VALUE=<? echo $Name ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_PASSWORD" VALUE=<? echo $Password ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_EMAIL" VALUE=<? echo $EMail ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_FIRST_NAME" VALUE=<? echo $FirstName ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_LAST_NAME" VALUE=<? echo $LastName ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_ICQ" VALUE=<? echo $ICQ ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_GENDER" VALUE=<? echo $Gender ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_AGE" VALUE=<? echo $Age ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_COUNTRY" VALUE=<? echo $Country ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_HOW_KNOW_US" VALUE=<? echo $HowKnowUs ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_CREATED_TIME" VALUE=<? echo $CreatedTime ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_LAST_LOGIN" VALUE=<? echo $LastLogin ?>>
<INPUT TYPE=hidden NAME="ADMIN_TOOL_IS_ADMIN" VALUE=<? echo $IsAdmin ?>>
<INPUT TYPE=hidden NAME="PLAYER_INIT" VALUE=YES>
Now portal information is loaded.<BR>
Please click this. <INPUT TYPE=submit VALUE="Next">
</FORM>
</BODY>
</HTML>

