<?
/*
  logout.php3

  ANG logout

  by thedaz (thedaz@maritel.com)
*/
  
include "lib.php3";

setcookie("AUTH", "logout", time()-3600, "/", "archspace.co.kr");

header("Location: /index.html");
?>
