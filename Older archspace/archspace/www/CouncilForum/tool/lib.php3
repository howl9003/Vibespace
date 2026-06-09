<?
/*
  lib.php3

  CFT php3 library

  by thedaz (thedaz@maritel.com)
*/

/******************************************
 chech_admin()
******************************************/
Function check_admin($AUTH)
{
  if ($AUTH != "ok")
  {
    header("Location: out.html");
  }
}

/******************************************
 login()
******************************************/
Function login($pwd)
{
  if($pwd == "55555")
  {
    return "succ";
  }
  else
  {
    setcookie("AUTH", "logout", time(), "/", "archmage.co.kr");

    return "fail";
  }
}
?>
