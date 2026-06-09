<?
/*
  login.php3

  CFT login check page

  by thedaz (thedaz@maritel.com)
*/

include "lib.php3";

$ret = login($pwd);

if ($ret == "succ")
{
  setcookie("AUTH", "ok", 0, "/", "archmage.co.kr");
  header("Location: forum.html");
}
else
{
  header("Location: out.html");
}
?>

