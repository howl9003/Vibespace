<?
	include "libadmin.php";
  include "libportal.php";
  
	$IsAdmin = get_is_admin($auth_server_con);

	if ($IsAdmin != "YES")
	{
		header("Location: not_admin_error.html");
	}
	else if (!$FINAL_PORTAL_NAME || !$FINAL_PORTAL_PASSWORD || !$FINAL_EMAIL || !$FINAL_IS_ADMIN)
	{
		echo "There is wrong data which is changed wrong.".
				" Maybe one or more of them are NULL.";
		echo "<BR>";
	}
	else
	{
		mysql_pconnect($PortalHost, $PortalUser, $PortalPassword);
		@mysql_select_db($PortalDatabase) or die("Unable to connect to the Database");

		mysql_query("LOCK TABLES Users WRITE");

		mysql_query("UPDATE Users SET".
						" name = '$FINAL_PORTAL_NAME',".
						" password = '$FINAL_PORTAL_PASSWORD',".
						" email = '$FINAL_EMAIL',".
						" is_admin = '$FINAL_IS_ADMIN'".
						" WHERE id=$FINAL_PORTAL_ID");

		$Error = mysql_error();

		mysql_query("UNLOCK TABLES");
		mysql_close();

		if (substr($Error, 0, 15) == "Duplicate entry")
		{
			echo "There is duplicated field in changed data.".
					" The portal name you entered can be wrong.";
			echo "<BR>";
		}
		else
		{
			echo "Portal information of account ".$FINAL_PORTAL_NAME."(".$FINAL_PORTAL_ID.") has been saved.";
			echo "<BR>";
		}
	}
?>
<HTML>
<BODY>
Please click <A HREF="admin.as">HERE</A> to go to the main page.
</BODY>
</HTML>

