<?
  include "/space/portal/php/portal.php3";

  echo "ID_STRING is ";
  echo $HTTP_COOKIE_VARS["ID_STRING"];
  echo "<P>";
  echo "AS_STRING is ";
  echo $HTTP_COOKIE_VARS["AS_STRING"];
  echo "<P>";
  echo "cCouncilForum is ";
  echo $HTTP_COOKIE_VARS["cCouncilForum"];
  echo "<P>";
  echo "phpBBsession is ";
  echo $HTTP_COOKIE_VARS["phpBBsession"];
  echo "<P>";
  echo "send data to localhost 11114 -> ";
  echo get_as_string("localhost 11114"); 
  echo "<P>";
  echo "get_is_admin -> ";
  echo get_is_admin("localhost 11113"); 
?>
