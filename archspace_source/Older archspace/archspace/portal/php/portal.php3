<?
if(!$portal_include):
$portal_include = 1;
include "portal_config.php3";

/*
  by thedaz
  get name with portal number from Portal DB
*/
Function get_name( $portal_id )
{
  mysql_pconnect("localhost", "space", "comconq1");

  @mysql_select_db("Portal") or die("Unable to connect to the Database");

  mysql_query("LOCK TALBES Users READ"); 

  $result = mysql_query("SELECT * FROM Users WHERE id=$portal_id");
  $row = mysql_fetch_array($result);

  $name = $row["name"];

  mysql_query("UNLOCK TABLES");
  mysql_close();

  return $name;
}

Function get_portal_id( $AUTH_SERVER_CON )
{
  $server_fd = open_connection ( $AUTH_SERVER_CON );

  if( $server_fd <= 0 )
  {
  //  log_( "connection to " . $AUTH_SERVER_CON . " failed." );
    return "there is a server problem.";
  }
  fputs( $server_fd, "ID_STRING=" . $GLOBALS["ID_STRING"] . "&HOST=" . getenv("REMOTE_ADDR") . "~");
  //log_("ID_STRING=" . $GLOBALS["ID_STRING"] . "&HOST=" . getenv("REMOTE_ADDR") . "~");
  sleep(1);

  $str = get_network_string( $server_fd );
  fclose( $server_fd );
 //log_("str = (".$str.")");

  $a = split('&', $str);
  $i = 0;
  while( $i < count($a) )
  {
    $b = explode ('=', urldecode($a[$i]));

    if(!strcmp($b[0], "ID"))
	{
      if( $b[1] == -1 )
	  {
	    return "authentification failure";
	  }
      else
	  {
        $logid = $b[1];
        return $logid;
      }
    }
    $i++;
  }

  return -1;
}

Function get_is_admin( $AUTH_SERVER_CON )
{
  $server_fd = open_connection ( $AUTH_SERVER_CON );

  if( $server_fd <= 0 )
  {
    log_( "connection to " . $AUTH_SERVER_CON . " failed." );
    return "there is a server problem.";
  }
  fputs( $server_fd, "ID_STRING=" . $GLOBALS["ID_STRING"] . "&HOST=" . getenv("REMOTE_ADDR") . "~");
  sleep(1);

  $str = get_network_string( $server_fd );
  fclose( $server_fd );

  //log_("str = ".$str);
  $a = split('&', $str);
  $i = 0;
  while( $i < count($a) )
  {
    $b = explode ('=', urldecode($a[$i]));

    if(!strcmp($b[0], "IS_ADMIN"))
	{
      if( $b[1] == -1 )
	  {
	    return "authentification failure";
	  }
      else
	  {
        $is_admin = $b[1];
        return $is_admin;
      }
    }
    $i++;
  }

  return -1;
}


// added by thedaz
Function get_as_string ($AUTH_SERVER_CON)
{
  $server_fd = open_connection ( $AUTH_SERVER_CON );

  if ($server_fd <= 0)
  {
    log_("connection to " . $AUTH_SERVER_CON . " failed.");
	return "there is a server problem";
  }

  fputs( $server_fd, "AS_STRING=" . $GLOBALS["AS_STRING"] . "&HOST=" . getenv("REMOTE_ADDR") . "~");
  sleep(1);
//  log_("YOSHIKI : ".$GLOBALS["AS_STRING"]);

  $str = get_network_string( $server_fd );
  fclose( $server_fd );

  if ($str == "GAME_ID=-1")
  {
    return -1;
  }

  return $str;
}

// test by thedaz
Function get_as_string_bak( $AUTH_SERVER_CON )
{
  $server_fd = open_connection ( $AUTH_SERVER_CON );

  if( $server_fd <= 0 )
  {
    log_( "connection to " . $AUTH_SERVER_CON . " failed." );
    return "there is a server problem.";
  }

  fputs( $server_fd, "AS_STRING=" . $GLOBALS["AS_STRING"] . "&HOST=" . getenv("REMOTE_ADDR") . "~"); 
  sleep(1);

  $str = get_network_string( $server_fd );
  fclose( $server_fd );

  // test
  echo "str is $str";
  echo "<P>";
  // test end

  $a = split('&', $str);
  $i = 0; 

  while( $i < count($a) )
  {
    $b = explode ('=', urldecode($a[$i]));

    if(!strcmp($b[0], "ID"))
    {
      if( $b[1] == -1 ) 
      {
        return "authentification failure";
      }
      else    
      {
        $logid = $b[1];
        return $logid; 
      }
    }
    $i++;   
  }

  return -1;
}

Function enter_portal( $NAME, $PASSWORD, $ENTRY_SERVER_CON )
{
  //log_("NAME : ".$NAME." "."PASSWORD : "." ".$PASSWORD);
  if ($NAME == "" || $PASSWORD == "") return -1;

  $server_fd = open_connection( $ENTRY_SERVER_CON );
  //log_("server_fd : ".$server_fd);

  if( $server_fd <= 0 )
  {
    log_( "connection to " . $entry_server_con . " failed." );
    return "there is a server problem.";
  }
  $temp = "NAME=" . urlencode( $NAME ) . "&PASSWORD=" . urlencode(  $PASSWORD ) . 
		"&HOST=" . getenv( "REMOTE_ADDR" ) . "~";
  fputs( $server_fd, "NAME=" . urlencode( $NAME ) . 
  		"&PASSWORD=" . urlencode(  $PASSWORD ) . 
		"&HOST=" . getenv( "REMOTE_ADDR" ) . "~");

  sleep(2);

  $str = get_network_string( $server_fd );
  fclose( $server_fd );
  $a = split('&', $str);
  $i = 0;
  while( $i < count($a) )
  {
    $b = explode ('=', urldecode($a[$i]));
    if(!strcmp($b[0], "ID_STRING"))  
    {
      setcookie( "ID_STRING", $b[1], 0, "/", "as.mlsecurity.com" );
//      log_("ID_STRING : ".$NAME."(".$b[1].")");
//      log_("____________ ".$HTTP_COOKIE_VARS["ID_STRING"]);
      return "ok";
    }

    if(!strcmp($b[0], "MSG"))
    {
      if(!strcmp($b[1], "You entered wrong password."))
      {
        return "wrong password";
      } else
      {
        return $b[1];
      }
    }
    $i++;
  }

  return -2;
}

Function open_connection( $SERVER_CON )
{
  $hostcon = explode(" ", $SERVER_CON);

  return fsockopen( $hostcon[0], $hostcon[1] );
}

Function log_( $str )
{
  global $logfile;
  if( $logfile == "" ) $logfile = "/var/log/archspace/portal.log";
  $fd = fopen( $logfile, "a" );

  $out_str = date( "M d H:i:s Y", time() ) . " :: " . $str . "\n";
  fputs( $fd, $out_str );

  fclose( $fd );
}

Function register($name, $pass)
{
	mysql_pconnect("localhost", "space", "comconq1") or die("ERROR1");
	mysql_select_db("Portal") or die("Unable to connect to the Database");
	mysql_query("INSERT INTO Users (name, password) VALUES ('$name', '$pass')") or die("ERROR");

}

Function get_network_string( $fd )
{
  $ret = "";
  while( ($c = fgetc( $fd )) != '*' )  {
    $ret = $ret . $c;
  }

//  log_("get_network_string : ".$ret);
  return $ret;
}
endif;
?>
