#include "server.h"
#include <time.h>
#include <locale.h>
#include <unistd.h>
#include "utility.h"

#include "rlimit.h"
#include "runtime.h"
#include "cgi.h"
#include "mysqllib.h"

#include <sys/types.h>
#include <sys/stat.h>

void process_page( DESCRIPTOR *d, QUERY_STRING *q );
void initialize( void ), prepare_exit( void );

#define KEY_FILE "keyfile"
char old_key[9], cur_key[9], fut_key[9];
char key_file[BUF_LEN];

MYSQL *mysql_sock;

void initialize( void )
{
  FILE *fp;
  char db_host[100], account[100], password[100], db_name[100];
  int new_key_generation( void );

  get_configuration( "key_server", program_name );
  strcpy( server_name, "key_server_con" );

  init_runtime_trigger( "new key generation", 3600, new_key_generation );

  get_configuration( "KeyFile", key_file);
  // load old & cur key
  fp = fopen( key_file, "r" );
  if( fp == NULL ){
    strcpy( cur_key, get_random_str( 8 ) );
    new_key_generation();
  } else {
    fread( old_key, 1, 8, fp );
    fread( cur_key, 1, 8, fp );
    fread( fut_key, 1, 8, fp );
    fclose( fp );
    old_key[8] = cur_key[8] = fut_key[8] = 0;
  }

// init DB
  if( get_configuration( "server_db_host", db_host ) <= 0 ) *db_host = 0;
  if( get_configuration( "server_db_user", account ) <= 0 ) strcpy( account, "jejak" );
  if( get_configuration( "server_db_pass", password ) <= 0 ) *password = 0;
  if( get_configuration( "server_db_name", db_name ) <= 0 ) strcpy( db_name, "EntryServer" );

  mysql_sock = init_mysql( db_host, db_name, account, password );
  if( mysql_sock == NULL ){
    log( LOG_FATAL, "db connection failure %s %s %s %s", db_host, account, password, db_name );
    exit(1);
  }
}


void prepare_exit( void )
{
// close DB
  mysql_close( mysql_sock );
  free( mysql_sock );
}


void process_page( DESCRIPTOR *d, QUERY_STRING *q )
{
  MYSQL_RES *result;
  MYSQL_ROW row;

// verify domain
  sprintf( m_buf, "SELECT count(*) FROM AllowIP WHERE ip = '%s'", GET_DHOST(d) );
  mysql_query( mysql_sock, "LOCK TABLES AllowIP READ" );
  mysql_query( mysql_sock, m_buf );
  result = mysql_store_result( mysql_sock );
  row = mysql_fetch_row( result );
  if( atoi(row[0]) < 1 ){
    log( LOG_WARNING, "illegal connection try from %s", GET_DHOST(d) );
    mysql_free_result( result );
    mysql_query( mysql_sock, "UNLOCK TABLES" );
    end_of_transmission( d );
    return;
  }
  mysql_free_result( result );
  mysql_query( mysql_sock, "UNLOCK TABLES" );

  send_output_to_client( d, "FUTKEY=%s&CURKEY=%s&OLDKEY=%s", fut_key, cur_key, old_key );
}

int new_key_generation( void )
{
  FILE *fp;

  strcpy( old_key, cur_key );
  strcpy( cur_key, fut_key );
  strcpy( fut_key, get_random_str( 8 ) );

  fp = fopen( key_file, "w" );
  if( fp == NULL ){
    log( LOG_DEBUG, "key file generation failed %s", key_file );
    return 0;
  }
  fwrite( old_key, 1, 8, fp );
  fwrite( cur_key, 1, 8, fp );
  fwrite( fut_key, 1, 8, fp );
  fclose( fp );

  return 0;
}


