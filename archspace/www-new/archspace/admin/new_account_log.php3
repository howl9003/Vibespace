<?
	include "libadmin.php";
  include "libportal.php";
  
	$IsAdmin = get_is_admin($auth_server_con);

	if ($IsAdmin != "YES")
	{
		header("Location: not_admin_error.html");
	}
	else
	{
		exec("grep ADMIN_NEW_ACCOUNT /var/log/archspace/portal.log", $Result);
	}
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
?>
<CENTER>
	<A HREF="admin.as">To Main Page</A>
</CENTER>
</BODY>
</HTML>

