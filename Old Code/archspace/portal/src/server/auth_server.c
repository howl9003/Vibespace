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

void initialize( void )
{
  get_configuration( "auth_server", program_name );
  strcpy( server_name, "auth_server_con" );

  init_runtime_trigger( "receive new key", 600, receive_key);
  receive_key();
}


void prepare_exit( void )
{
}

void process_page( DESCRIPTOR *d, QUERY_STRING *q )
{
  QUERY_STRING *mq;
  char *msg_str, *q_name, *q_id, *q_ip, *q_reg, *q_enter_time, *q_last_update_time, *q_is_admin, *q_msg, *host_ip;
  int len;
  char buf[BUF_LEN], crypted_buf[BUF_LEN], tmp[BUF_LEN];

  msg_str = search_query_string( q, "ID_STRING" );
  host_ip = search_query_string( q, "HOST" );
  if( msg_str == NULL || host_ip == NULL ){
    log( LOG_FATAL, "wrong message format" );
    end_of_transmission( d );
    return;
  }

  len = (strlen(msg_str)-16)/2;

  my_decrypt( cur_key, msg_str, buf );
//  if( decrypt_success( buf, len ) == 0 ) my_decrypt( old_key, msg_str, buf );
  if( decrypt_success( buf ) == 0 ) my_decrypt( old_key, msg_str, buf );
//  if( decrypt_success( buf, len ) == 0 ) my_decrypt( fut_key, msg_str, buf );
  if( decrypt_success( buf ) == 0 ) my_decrypt( fut_key, msg_str, buf );

  // too old key
//  if( decrypt_success( buf, len ) == 0 ){
  if( decrypt_success( buf ) == 0 ){
    send_output_to_client_raw( d, "ID=-1*" );
	log(LOG_IGNORE, "wrong [%s]%s", msg_str, host_ip);
    return;
  }
  mq = parse_string( buf );

  q_name = search_query_string( mq, "NAME" );
  q_id = search_query_string( mq, "ID" );
  q_ip = search_query_string( mq, "HOST" );
  q_reg = search_query_string( mq, "REG" );
  q_enter_time = search_query_string( mq, "ENTER_TIME" );
  q_last_update_time = search_query_string( mq, "LAST_UPDATE_TIME" );
  q_is_admin = search_query_string( mq, "IS_ADMIN" );
  q_msg = search_query_string( mq, "MSG" );

  if( q_name == NULL || q_id == NULL || q_enter_time == NULL || q_last_update_time == NULL || q_is_admin == NULL ){
    log( LOG_FATAL, "wrong message format %s - %s", buf, msg_str );
    send_output_to_client_raw( d, "ID=-1*" );
//  } 
//else if( strcmp( host_ip, q_ip) ){
//    log( LOG_FATAL, "cracking attempt from %s %s", host_ip, buf );
//    send_output_to_client_raw( d, "ID=-1*" );
  } else {
    urlencode( q_name, tmp );
// rflag value handling in Rusers database table
    if(q_reg) 
      sprintf( buf, "ID=%s&NAME=%s&HOST=%s&REG=%s&ENTER_TIME=%s&LAST_UPDATE_TIME=%ld", q_id, tmp, /*q_ip*/host_ip, q_reg, q_enter_time, (long)time(0) );
    else 
      sprintf( buf, "ID=%s&NAME=%s&HOST=%s&ENTER_TIME=%s&LAST_UPDATE_TIME=%ld", q_id, tmp, /*q_ip*/host_ip, q_enter_time, (long)time(0) );
    if( q_msg )
    {
      urlencode( q_msg, tmp );
      sprintf( buf+strlen(buf), "&MSG=%s", tmp );
    }
    my_encrypt( cur_key, buf, crypted_buf );
    
    urlencode( q_name, tmp );
    sprintf( buf, "ID=%s&NAME=%s&IS_ADMIN=%s&ID_STRING=%s*", q_id, tmp, q_is_admin, crypted_buf );

    send_output_to_client_raw( d, buf );
  }

  junk_query_string_list( mq );
}

