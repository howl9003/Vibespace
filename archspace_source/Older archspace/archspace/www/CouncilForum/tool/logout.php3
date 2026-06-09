<?
/*
  logout.php3

  CFT logout

  by thedaz (thedaz@maritel.com)
*/

include "lib.php3";

setcookie("AUTH", "logout", time()-3600, "/", "archmage.co.kr");

header("Location: /archspace/");
?>

