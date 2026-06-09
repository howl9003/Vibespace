#include "server.h"
#include <time.h>
#include <locale.h>
#include <unistd.h>
#include "utility.h"
#include "crypt.h"

#include "rlimit.h"
#include "runtime.h"
#include "cgi.h"
#include "mysqllib.h"

#include <sys/types.h>
#include <sys/stat.h>

#include "xlib.h"

// from any side
int receive_key( void )
{
  int fd;
  char buf[1000], *t;
  QUERY_STRING *q;
  extern char cur_key[], old_key[], fut_key[];
  extern char program_name[];

  fd = open_connection( "key_server_con" );
  if( fd == ABNORMAL ){
    log( LOG_WARNING, "key server connection failure %s", "key_server_con" );
    return 0;
  }

  write_just( fd, 2, "~" );
  usleep( 1000000 );
  read_just( fd, 1000, buf );

  log(LOG_IGNORE, "receive data %s", buf);

  q = parse_string( buf );

  t = search_query_string( q, "FUTKEY" );
  if( t == NULL ){
    log( LOG_FATAL, "%s NULL FutKey %s", program_name, buf );
    return 0;
  }
  strcpy( fut_key, t );

  t = search_query_string( q, "CURKEY" );
  if( t == NULL ){
    log( LOG_FATAL, "%s NULL CurKey %s", program_name, buf );
    return 0;
  }
  strcpy( cur_key, t );

  t = search_query_string( q, "OLDKEY" );
  if( t == NULL ){
    log( LOG_FATAL, "%s NULL OldKey %s", program_name, buf );
    return 0;
  }
  strcpy( old_key, t );

  junk_query_string_list( q );
  close( fd );

  return 0;
}


// from client
int enter_portal( char *name, char *password, char *host, char *id_string, char *msg )
{
  int server_fd, id;
  char buf[BUF_LEN], tmp[BUF_LEN], *id_str, *name_str, *id_string_str, *msg_str;
  QUERY_STRING *q;

  server_fd = open_connection( "entry_server_con" );
  if( server_fd == ABNORMAL ){
    log( LOG_FATAL, "configuration %s is illegal\n", "entry_server_con" );
    return -1;
  }

  // make query string to entry server
  urlencode( name, tmp );
  sprintf( buf, "NAME=%s", tmp );
  urlencode( crypt( password, password ), tmp );
  sprintf( buf+strlen(buf), "&PASSWORD=%s&HOST=%s", tmp, host );
  if( *id_string ){
    urlencode( id_string, tmp );
    sprintf( buf+strlen(buf), "&ID_STRING=%s", tmp );
  }

  write_just( server_fd, strlen(buf)+1, buf );
  sleep(1);
  read_just( server_fd, 1000, buf );

  q = parse_string( buf );

  id_str = search_query_string( q, "ID" );
  name_str = search_query_string( q, "NAME" );
  id_string_str = search_query_string( q, "ID_STRING" );
  msg_str = search_query_string( q, "MSG" );

  if( msg_str ) strcpy( msg, msg_str );

  if( strcmp( id_str, "-1" ) == 0 || name_str == NULL || id_string_str == NULL )
    return -1;

  id = atoi( id_str );
  strcpy( id_string, id_string_str );
  strcpy( name, name_str );

  junk_query_string_list( q );
  close( server_fd );

  return id;
}

// from client
int get_id_authentification( char *id_string, char *host, char *name )
{
  int server_fd, id;
  char buf[BUF_LEN], tmp[BUF_LEN], *id_str, *name_str, *id_string_str;
  QUERY_STRING *q;

  server_fd = open_connection( "auth_server_con" );
  if( server_fd == ABNORMAL ){
    log( LOG_FATAL, "configuration %s is illegal\n", "auth_server_con" );
    return -1;
  }

  urlencode( id_string, tmp );
  sprintf( buf, "ID_STRING=%s&HOST=%s", id_string, host );

  write_just( server_fd, strlen(buf)+1, buf );
  sleep(1);
  read_just( server_fd, 1000, buf );

  q = parse_string( buf );

  id_str = search_query_string( q, "ID" );
  name_str = search_query_string( q, "NAME" );
  id_string_str = search_query_string( q, "ID_STRING_STR" );

  if( name_str == NULL || strcmp( id_str, "-1" ) == 0 ){
    *name = 0;
    return -1;
  }

  strcpy( id_string, id_string_str );
  strcpy( name, name_str );
  id = atoi( id_str );

  junk_query_string_list( q );
  close( server_fd );

  return id;
}

// from client
int register_new_id( char *input_string, char *msg )
{
  int id, server_fd;
  char buf[BUF_LEN], *id_str, *msg_str;
  QUERY_STRING *q;

  server_fd = open_connection( "register_server_con" );
  if( server_fd == ABNORMAL ){
    log( LOG_FATAL, "configuration %s is illegal\n", "register_server_con" );
    return -1;
  }

  write_just( server_fd, strlen(buf)+1, input_string );
  sleep(1);
  read_just( server_fd, 1000, buf );

  q = parse_string( buf );

  id_str = search_query_string( q, "ID" );
  msg_str = search_query_string( q, "MSG" );

  if( msg_str ) strcpy( msg, msg_str );
  id = atoi( id_str );

  junk_query_string_list( q );
  close( server_fd );

  return id;
}

int get_input_string( char *input_string )
{
  char *ct, *cl;
  int input_len;

  ct = getenv( "CONTENT_TYPE" );
  cl = getenv( "CONTENT_LENGTH" );
  if( cl == NULL ){
    return 0;
  }
  if( strcmp( ct, "application/x-www-form-urlencoded" ) ) return -1;

  input_len = atoi( cl );
  if( input_len == 0 ){
    log( LOG_DEBUG, "content length is zero." );
    return -1;
  }

  if( fread( input_string, input_len, 1, stdin) != 1 ){
    log( LOG_DEBUG, "cannot read input stream %d bytes", input_len);
    return -1;
  }
  input_string[input_len] = 0;

  return input_len;
}   

