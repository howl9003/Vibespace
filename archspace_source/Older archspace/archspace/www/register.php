<html>
<body bgcolor="#000000" text="white">
<p><center>

<form method=post>
<font size=3>Sign up below</font><p>
<table cellpadding=2 cellspacing=0 border=0>

<td><font size="2" face="Times New Roman, Times, serif">Username:</td><td><input type="text" name="user" size=20></td><tr>
<td><font size="2" face="Times New Roman, Times, serif">Password:</td><td><input type="password" name="pass" size=20></td><tr>
<td><font size="2" face="Times New Roman, Times, serif">Email:</td><td><input type="text" name="email" size=20></td><tr>
<td><font size="2" face="Times New Roman, Times, serif">Age:</td><td><input type="text" name="age" size=20></td><tr>
<td><font size="2" face="Times New Roman, Times, serif">Country:</td><td><input type="text" name="country" size=20></td><tr>
<td><font size="2" face="Times New Roman, Times, serif">Sex:</td><td><select name="sex" width=20><option value="Male">Male</option><option value="Female">Female</option></select></td><tr>
<td>&nbsp;</td><td>&nbsp;&nbsp;&nbsp;<input type="submit" name="signup" value="Sign Up"></td>
</table></form>
<?
	
	$bool = "True";
	
	$signup = $_POST["signup"];
	
	$user = $_POST["user"];
	
	$pass = $_POST["pass"];
	
	$email = $_POST["email"];
	
	$age = $_POST["age"];
	
	$sex = $_POST["sex"];
	
	$country = $_POST["country"];
	
	$comments = htmlspecialchars($_POST["comments"]);
	
	$hidden = (int)$_POST["hidden"];
	if(($user != "") && ($pass != ""))
	{
		mysql_pconnect("localhost", "space", "comconq1") or die("ERROR1");
		mysql_select_db("Archspace") or die("Unable to connect to the Database");
		if($signup== "Sign Up")
		{
	/*		$res = mysql_query("SELECT * FROM acf_members");
			$num = mysql_num_rows($res);
			for ($i=0; $i<$num; $i++)
			{
				$tmpuser = strtolower(mysql_result($res, $i, "name"));
				if($tmpuser==strtolower($user))
				{
				$bool = "FALSE";
				}
			}
			if($bool=="FALSE")
			{
				echo($user." is already in use, please select a different username");
			}else
			{    */
				if(!$user || !$pass || !$age || !$sex || !$email || !$country)
				{
					echo "Please fill in ALL the required information.";
				}elseif (!eregi ("^([a-z0-9_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,4}$", $email))
				{
					echo "Invalid Email Address.";
				}elseif(!ereg("[_a-zA-Z0-9-]$", $user) || strstr($user, " ") || strstr($user, ">") || strstr($user, "<"))
				{
					echo("Invalid characters in username (only a-z A-Z 0-9 allowed), please try again.");
				}elseif(!ereg("[_a-zA-Z0-9-]$", $country) || strstr($country, " ") || strstr($country, ">") || strstr($country, "<"))
				{
					echo("Invalid characters in country (only a-z A-Z 0-9 allowed), please try again.");
				}elseif(!ereg("[_a-zA-Z0-9-]$", $pass) || strstr($pass, " ") || strstr($pass, ">") || strstr($pass, "<"))
				{
					echo("Invalid characters in password (only a-z A-Z 0-9 allowed), please try again.");
				}elseif(strtolower($sex) != "male" && strtolower($sex) != "female")
				{
					echo("You can only be a male or a female, sorry.");
				}elseif((int)$age < 13 || (int)$age > 125)
				{
					echo("You are either too young or too old to play Archspace, sorry.");
				}elseif(strlen($user)>36 || strlen($user)<2)
				{
					echo("Name cannot be more than 20 characters and no less than 2 characters.");
				}else
				{
					$firstlogin=date("Y-m-d H:i:s");
					
//					mysql_query ("INSERT INTO Users (name, password, email, age, sex, country, ip, firstlogin, is_admin) VALUES ('$user', '$pass', '$email', '$age', '$sex', '$country', '$ip', '$firstlogin', 'NO')") or die(mysql_error());
          require_once "./ipbsdk_class.inc.php";
          $SDK =& new IPBSDK();
							if ($uid = $SDK->create_account($user, $pass, $email))
              {
          			echo '<strong>Success:</strong> Registration Complete.<br />';
      					echo "<script>setTimeout(\"document.location=\'/main.php\'\", 2000);</script>";
		          }
		          else {
			           echo '<strong>Error:</strong> The Forum Registration Failed.<br />'.$SDK->sdk_error();
		          }

//					echo("<font color='#00ff00'>".$user." added to database!</font><P>");
//			}
			}
		}
	}else
	{
		echo("Please fill in the required information.");
	}

?>
</center></p></font>
</body>
</html>
