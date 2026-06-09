<?
// AS_STRING 을 잡아오기 위해 include 
include('/var/www/localhost/htdocs/notice/lib.php');
include('/space/portal/etc/config');

// AS auth server 에 접속해서 AS_STRING 가져옴
Function as_get_string()
{
  global $cForum;
  $cForum = -1;

  $cForum = get_as_string("localhost 11114");

  if ($cForum != -1)
  {
//    as_string_parsing($cForum);
    return $cForum;
  }
  else
  {
    return -1;
  }
}

// AS_STRING 파싱
Function as_string_parsing($string)
{
  $raw = "";
  $raw2 = "";

  global $GAME_ID, $GAME_NAME, $COUNCIL_ID, $COUNCIL_NAME, $IS_SPEAKER, $HAS_SPEAKER;

  $raw = explode("&", $string);

  $raw2 = explode("=", $raw[0]);
  $GAME_ID = $raw2[1];
  echo $GAME_ID;

  $raw2 = explode("=", $raw[1]);
  $GAME_NAME = $raw2[1];
  $GAME_NAME = addslashes($GAME_NAME);
  echo $GAME_NAME;

  $raw2 = explode("=", $raw[2]);
  $COUNCIL_ID = $raw2[1];
  echo $COUNCIL_ID;

  $raw2 = explode("=", $raw[3]);
  $COUNCIL_NAME = $raw2[1];
  $COUNCIL_NAME = addslashes($COUNCIL_NAME);
  echo $COUNCIL_NAME;

  $raw2 = explode("=", $raw[4]);
  $IS_SPEAKER = $raw2[1];
  echo $IS_SPEAKER;

  $raw2 = explode("=", $raw[5]);
  $HAS_SPEAKER = $raw2[1];
  echo $HAS_SPEAKER;
}

echo as_get_string();
echo "<HR>";
as_string_parsing($cForum);
?>
