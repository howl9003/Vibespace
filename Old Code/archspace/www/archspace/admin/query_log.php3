<?
	include "/space/portal/php/portal.php3";
	include "libs.php3";

	$IsAdmin = get_is_admin($auth_server_con);

	if ($IsAdmin != "YES")
	{
		header("Location: not_admin_error.html");
	}

	if ($BY_WHAT != "ACTION" && $BY_WHAT != "NEW_ACCOUNT" &&
		$BY_WHAT != "NEW_PLAYER" && $BY_WHAT != "DEAD_PLAYER")
	{
		header("Location: select_log_error.html");
	}

	if ($YEAR < 2001 || $YEAR > 2001)
	{
		header("Location: select_log_error.html");
	}
	if ($MONTH < 1 || $MONTH > 12)
	{
		header("Location: select_log_error.html");
	}
	if ($DAY != "01-10" && $DAY != "11-20" && $DAY != "21-")
	{
		header("Location: select_log_error.html");
	}

	$Result = array();

	if ($DAY == "01-10")
	{
		$StartDay = 1;
		$EndDay = 10;
	}
	else if ($DAY == "11-20")
	{
		$StartDay = 11;
		$EndDay = 20;
	}
	else if ($DAY == "21-")
	{
		$StartDay = 21;
		$EndDay = 31;
	}

	$Result = get_log_by_type($BY_WHAT, $YEAR, $MONTH, $StartDay, $EndDay);
?>
<HTML>
<BODY>
<?
	$i = 0;
	while ($Result[$i])
	{
		echo $Result[$i];
		echo "<BR>\n";

		$i++;
	}

	echo "<BR>\n";
?>
<CENTER>
	<A HREF="admin.as">To Main Page</A>
</CENTER>
</BODY>
</HTML>

