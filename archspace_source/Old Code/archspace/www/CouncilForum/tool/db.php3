<?
include "config.php3";
include "function.php3";
include "/space/portal/php/portal.php3";

/*
 *
 * VIEW
 *
 */
if ($mode == "view")
{
  // Archspace DB 의 council 의 개수
  connect_db("Archspace");
  lock_table_read("council");

  $query = mysql_query("SELECT count(*) FROM council");
  $council = mysql_fetch_array($query);
  mysql_free_result($query);

  disconnect_db();

  // CouncilForum DB 의 forums 의 개수
  connect_db("CouncilForum", "forums");
  mysql_query("LOCK TABLES catagories, forums, forum_mods, users READ");

  $query = mysql_query("SELECT count(*) FROM forums");
  $forum = mysql_fetch_array($query);
  mysql_free_result($query);

  $query = mysql_query("SELECT count(*) FROM catagories");
  $cat = mysql_fetch_array($query);
  mysql_free_result($query);

  $query = mysql_query("SELECT count(*) FROM forum_mods");
  $mod = mysql_fetch_array($query);
  mysql_free_result($query);

  disconnect_db();

  // council 과 council forum 개수 출력
  print_header();
  printf("total <U>councils</U> are <FONT COLOR=#ff0000>%d</FONT>.", $council["count(*)"]);
  printf("<HR>");
  printf("total <U>council forums</U> are <FONT COLOR=#ff0000>%d</FONT>.", $forum["count(*)"]);
  printf("<BR>");
  printf("total <U>catagories</U> are <FONT COLOR=#ff0000>%d</FONT>.", $cat["count(*)"]);
  printf("<BR>");
  printf("total <U>forum_mods</U> are <FONT COLOR=#ff0000>%d</FONT>.", $mod["count(*)"]);
  printf("<BR>");
  printf("<BR>");

  // council forum 과 council 의 개수 비교
  if ($forum["count(*)"] != $council["count(*)"] ||
      $forum["count(*)"] != $cat["count(*)"] ||
      $forum["count(*)"] != $mod["count(*)"])
  {
    printf("councils and forums are dis-match. so you need create council forums.");
    printf("<BR>");
    printf("<A HREF=$PHP_SELF?mode=create_council><B>auto create council forums</B></A>");
    print_tailer();
  }
  else
  {
    printf("<A HREF=$tool_index>main</A>");
    print_tailer();
  }
}
/*
 *
 * CREATE_COUNCIL
 *
 */
else if ($mode == "create_council")
{
  connect_db("Archspace");
  lock_table_read("council");

  $query = mysql_query("SELECT id, speaker FROM council");

  $fd = fopen($game_user_temp, "w");
  chmod($game_user_temp, 0777);

  // 동시에 두개의 DB 를 열 수 없기 때문에 임시로 화일을 만듬
  while (list($id, $speaker) = mysql_fetch_row($query))
  {
    $out_str = $id."|".$speaker."\n";
    fputs($fd, $out_str);
  }
  mysql_free_result($query);

  fputs($fd, "END");
  fclose($fd);
  disconnect_db();

  $fd = fopen($game_user_temp, "r");

  connect_db("CouncilForum");
  mysql_query("LOCK TABLES catagories, forums, forum_mods, users WRITE");

  while ($ch != "END")
  {
    $ch = fgets($fd, 1024);

    if ($ch != "END")
    {
      $data = explode("|", $ch);
      $query = mysql_query("INSERT INTO forums VALUES ('$data[0]', 'free board', NULL, '1', 'NULL', '$data[0]', '0')");
      $query = mysql_query("INSERT INTO catagories VALUES ('$data[0]', '', '1')");
      $query = mysql_query("INSERT INTO forum_mods VALUES ('$data[0]', '$data[1]', '$data[0]')");
    }
    else
    {
    }
  }

  print_header();
  printf("<BR>");
  printf("Done");
  printf("<BR>");

  disconnect_db();
  fclose($fd);

  unlink($game_user_temp);

  printf("<A HREF=$tool_index>main</A>");
  print_tailer();
}
/*
 *
 * DELETE_COUNCIL
 *
 */
else if ($mode == "delete_council")
{
  echo $mode;
}
/*
 *
 * WRONG URL QUERY
 *
 */
else
{
  header("Location: $tool_index");
}
?>
