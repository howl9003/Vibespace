<html>
<body bgcolor="#000000" text="white">
<p><center>
<?

		mysql_pconnect("localhost", "space", "comconq1") or die("Unable to connect to the Database");
		mysql_select_db("Archspace") or die("Unable to connect to the Database");
		$res = mysql_query("SELECT turn, news_turn FROM player");
		$num = mysql_num_rows($res);
		$active = 0;		
		for ($i=0; $i<$num; $i++)
		{
			$turn = intval(mysql_result($res, $i, "turn"));
			$newsturn = intval(mysql_result($res, $i, "news_turn"));
			if ($turn - $newsturn <= 15) // 15 turns * 2 minutes == 30 minutes
				$active++;
		}
		echo $active." players active within the last 30 minutes";
?>
</center></p></font>
</body>
</html>
