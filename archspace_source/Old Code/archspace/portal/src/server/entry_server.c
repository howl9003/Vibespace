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

void process_page( DESCRIPTOR *d, QUERY_STRING *q );
void initialize( void ), prepare_exit( void );

char old_key[9], cur_key[9], fut_key[9];

MYSQL *mysql_sock;

void initialize( void )
{
  char db_host[100], account[100], password[100], db_name[100];

  get_configuration( "entry_server", program_name );
  strcpy( server_name, "entry_server_con" );

  init_runtime_trigger( "receive new key", 600, receive_key );
  receive_key();

// init DB
  if( get_configuration( "user_db_host", db_host ) <= 0 ) *db_host = 0;
  if( get_configuration( "user_db_user", account ) <= 0 ) strcpy( account, "jejak" );
  if( get_configuration( "user_db_pass", password ) <= 0 ) *password = 0;
  if( get_configuration( "user_db_name", db_name ) <= 0 ) strcpy( db_name, "EntryServer" );

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
  char *name_str, *password_str, *id_str, *host_ip;
  char buf[BUF_LEN], crypted_buf[BUF_LEN], tmp[BUF_LEN], msg_buf[BUF_LEN];
  MYSQL_RES *result;
  MYSQL_ROW row;
  int login_success, id;
  char *is_admin;
  int rf_exist, rf;

  char new_name[BUF_LEN*2];
  int index = 0, length, i;

  name_str = search_query_string( q, "NAME" );
  password_str = search_query_string( q, "PASSWORD" );
  host_ip = search_query_string( q, "HOST" );
  id_str = search_query_string( q, "ID_STRING" );
 
  if( name_str == NULL || password_str == NULL || host_ip == NULL ){
    log( LOG_FATAL, "wrong message format" );
    end_of_transmission( d );
    return;
  }

  length = strlen(name_str);

  for (i=0 ; i<length ; i++)
  {
    if (name_str[i] == '\"')
    {
      new_name[index++] = '\\';
      new_name[index++] = name_str[i];
    } else if (name_str[i] == '\'')
    {
      new_name[index++] = '\\';
      new_name[index++] = name_str[i];
    } else if (name_str[i] == '\\')
    {
      new_name[index++] = '\\';
      new_name[index++] = name_str[i];
    } else
    {
      new_name[index++] = name_str[i];
    }
  }

  new_name[index] =0;
  mysql_query( mysql_sock, "LOCK TABLES Users READ" );
  sprintf( m_buf, "SELECT id, password, is_admin FROM Users WHERE name = '%s'", new_name);
  mysql_query( mysql_sock, m_buf );
  result = mysql_store_result( mysql_sock );

  if( mysql_affected_rows( mysql_sock ) < 1 || !result ){
    sprintf( msg_buf, "User %s is not registered", name_str );
    login_success = 0;
  } else {
    row = mysql_fetch_row( result );
    if( strcmp( row[1], password_str ) ){
      strcpy( msg_buf, "You entered wrong password." );
      login_success = 0;
    } else {
      id = atoi(row[0]);

      is_admin = row[2];
      if (!row[2])
      {
        is_admin = "NO";
      }
      else if (strcmp(row[2], "YES"))
      {
        is_admin = "NO";
      }

      login_success = 1;
    }
  }

  log(LOG_FATAL, "is_admin = %s", is_admin);

  mysql_free_result( result );
  mysql_query( mysql_sock, "UNLOCK TABLES" );

  if( login_success )
  {
/*
  // read rflag in Rusers DB
  mysql_query( mysql_sock, "LOCK TABLES Rusers READ" );
  sprintf( m_buf, "SELECT rflag FROM Rusers WHERE pid=%d", id );
;
  mysql_query( mysql_sock, m_buf );

  result = mysql_store_result( mysql_sock );  
  if( mysql_affected_rows( mysql_sock ) < 1 ){
    rf_exist = 0;
  } else {
    rf_exist = 1;
    row = mysql_fetch_row( result );
    rf = atoi(row[0]);
  }       

  mysql_free_result( result );
  mysql_query( mysql_sock, "UNLOCK TABLES" );
 */ 
    // detect multi-id
    if( id_str ){
      my_decrypt( cur_key, id_str, buf );
      if( decrypt_success( buf ) == 0 ) my_decrypt( old_key, id_str, buf );
      if( decrypt_success( buf ) == 0 ) my_decrypt( fut_key, id_str, buf );
      if( decrypt_success( buf ) == 0 ){
        QUERY_STRING *mq;
        char *q_name, *q_id, *q_enter_time, *q_ip;
  //      int et;

        mq = parse_string( buf );

        q_name = search_query_string( mq, "NAME" );
        q_id = search_query_string( mq, "ID" );
        q_ip = search_query_string( mq, "HOST" );
        q_enter_time = search_query_string( mq, "ENTER_TIME" );

     //   et = atoi(q_enter_time);
     //  strcpy( tmp, ctime((time_t *)&et) );
    //    log( LOG_IGNORE, "multi-id : %s(#%s) from %s at %s", q_name, q_id, q_ip, tmp );

        junk_query_string_list( mq );
      }
    }
    urlencode( name_str, tmp );
    if(rf_exist)
      sprintf( buf, "ID=%d&NAME=%s&HOST=%s&REG=%d&ENTER_TIME=%ld&LAST_UPDATE_TIME=%ld&IS_ADMIN=%s", id, tmp, host_ip, rf, (long)time(0), (long)time(0), is_admin );
    else 
      sprintf( buf, "ID=%d&NAME=%s&HOST=%s&ENTER_TIME=%ld&LAST_UPDATE_TIME=%ld&IS_ADMIN=%s", id, tmp, host_ip, (long)time(0), (long)time(0), is_admin );
    my_encrypt( cur_key, buf, crypted_buf );
    urlencode( crypted_buf, buf );


    send_output_to_client( d, "ID=%d&NAME=%s&ID_STRING=%s*", id, tmp, buf );
  }
  else
  {
    urlencode( msg_buf, buf );
    send_output_to_client( d, "ID=-1&MSG=%s*", buf );
  }
}

