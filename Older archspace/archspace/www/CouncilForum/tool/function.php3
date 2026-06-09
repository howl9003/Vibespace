<?
function connect_db($db)
{
  mysql_pconnect("localhost", "space", "rlaclrnr");
  @mysql_select_db($db) or die("Unable to connect to the Database");
}

function disconnect_db()
{
  mysql_query("UNLOCK TABLES");
  mysql_close();
}

function lock_table_read($table)
{
  mysql_query("LOCK TABLES $table READ");
}

function lock_table_write($table)
{
  mysql_query("LOCK TABLES $table WRITE");
}

function print_header()
{
  printf("<BODY BGCOLOR=000000 LINK=999999 VLINK=999999 ALINK=999999 TEXT=999999>");
}

function print_tailer()
{
  printf("</BODY>");
  printf("</HTML>");
}
?>
