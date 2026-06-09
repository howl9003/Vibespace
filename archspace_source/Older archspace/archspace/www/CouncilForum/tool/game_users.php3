<?
/**********************************************************************************
 * Council Forum Optimize tool: game user of Archspace DB -> users of CouncilForum
 *
 * config.php3 에서 임시 화일이 생성될 디렉토리 셋팅
 * 그 디렉토리의 퍼미션은 chmod 777 이어야 함
 *
 * by TheDAZ
 *********************************************************************************/

include "config.php3";
include "/space/portal/php/portal.php3";

mysql_pconnect("localhost", "space", "rlaclrnr");
@mysql_select_db("Archspace") or die("Unable to connect to the Database");
mysql_query("LOCK TALBES player READ");

$query = mysql_query("SELECT game_id, name, council_id FROM player");
$query1 = mysql_query("SELECT count(*) FROM player");

$total = mysql_fetch_array($query1);
mysql_free_result($query1);

$fd = fopen($game_user_temp, "w");

chmod($game_user_temp, 0777);

while (list($game_id, $name, $council_id) = mysql_fetch_row($query))
{
  $out_str = $game_id."/".$name."/".$council_id."\n";
  fputs($fd, $out_str);
}
mysql_free_result($query);

fclose($fd);

mysql_query("UNLOCK TABLES");
mysql_close();


mysql_pconnect("localhost", "space", "rlaclrnr");
@mysql_select_db("CouncilForum") or die("Unable to connect to the Database");
mysql_query("LOCK TALBES users WRITE");

$fd = fopen($game_user_temp, "r");

$i = 0;

while ($i != $total["count(*)"])
{
  $buf = fgets($fd, 1024);

  $raw = explode("/", $buf);

  $game_id = $raw[0];
  $name = $raw[1];
  $council_id = $raw[2];

  $query = mysql_query("SELECT * FROM users WHERE user_id=$game_id");
  
  $num = mysql_fetch_array($query);
  mysql_free_result($query);

  if ($num["count(*)"] == 1)
  {
    $query_update = mysql_query("UPDATE users SET cat_id=$council_id, username='$name' WHERE user_id=$game_id");

    if ($query_update != 1)
    {
      log_("THEDAZ: Error: Council Forum Optimize tool - query_update ".$name);
    }
  }
  else if ($num["count(*)"] == 0)
  {
    $query_insert = mysql_query("INSERT INTO users (user_id, username, cat_id) VALUES ('$game_id', '$game_name', '$council_id')");

    if ($query_insert != 1)
    {
      log_("THEDAZ: Error: Council Forum Optimize tool - query_insert ".$name);
    }
  }

  $i++;
}

fclose($fd);

mysql_query("UNLOCK TABLES");
mysql_close();
?>
