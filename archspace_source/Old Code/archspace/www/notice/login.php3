<?
/*
  login.php3

  ANG login check page

  by thedaz (thedaz@maritel.com)
*/
  
include "lib.php3";

$ret = login($pwd);

if ($ret == "succ")
{
  setcookie("AUTH", "ok", 0, "/", "archspace.co.kr");
  header("Location: notice.html");
}
else
{
  header("Location: out.html");
}
?>
