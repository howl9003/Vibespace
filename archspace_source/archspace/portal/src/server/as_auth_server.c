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

int as_decrypt_success( char *str )
{
  if( strncmp( str, "COUNCIL_ID=", 11 ) == 0 || strstr( str, "&COUNCIL_ID=" ) ) return 1;
  return 0;
}
void initialize( void )
{
  get_configuration( "as_auth_server", program_name );
  strcpy( server_name, "as_auth_server_con" );

  init_runtime_trigger( "receive new key", 600, receive_key);
  receive_key();
}


void prepare_exit( void )
{
}

void process_page( DESCRIPTOR *d, QUERY_STRING *q )
{
  QUERY_STRING *mq;
  char *as_string, *q_game_id, *q_game_name, *q_council_id, *q_council_name, *q_is_speaker, *q_has_speaker;
  int len;
  char buf[BUF_LEN], crypted_buf[BUF_LEN];
  char q_new_game_name[BUF_LEN], q_new_council_name[BUF_LEN];

  as_string = search_query_string( q, "AS_STRING" );
  if( as_string == NULL )
  {
    log( LOG_FATAL, "wrong message format" );
    end_of_transmission( d );
    return;
  }

  len = (strlen(as_string)-16)/2;

  my_decrypt( cur_key, as_string, buf );

  if( as_decrypt_success( buf ) == 0 ) my_decrypt( old_key, as_string, buf );
  if( as_decrypt_success( buf ) == 0 ) my_decrypt( fut_key, as_string, buf );

  // too old key
  if( as_decrypt_success( buf ) == 0 ){
    send_output_to_client_raw( d, "GAME_ID=-1*" );
	log(LOG_IGNORE, "wrong [%s]", as_string);
    return;
  }
  mq = parse_string( buf );

  q_game_id = search_query_string( mq, "GAME_ID" );
  q_game_name = search_query_string( mq, "GAME_NAME" );
  q_council_id = search_query_string( mq, "COUNCIL_ID" );
  q_council_name = search_query_string( mq, "COUNCIL_NAME" );
  q_is_speaker = search_query_string( mq, "IS_SPEAKER" );
  q_has_speaker = search_query_string( mq, "HAS_SPEAKER" );

  if( q_game_id == NULL || q_game_name == NULL || q_council_id == NULL || q_council_name == NULL || q_is_speaker == NULL || q_has_speaker == NULL ){
    log( LOG_FATAL, "wrong message format %s - %s", buf, as_string );
    send_output_to_client_raw( d, "GAME_ID=-1*" );
  } else {
    urlencode( q_game_name, q_new_game_name );
    urlencode( q_council_name, q_new_council_name );

// rflag value handling in Rusers database table
    sprintf( buf, "GAME_ID=%s&GAME_NAME=%s&COUNCIL_ID=%s&COUNCIL_NAME=%s&IS_SPEAKER=%s&HAS_SPEAKER=%s*", q_game_id, q_new_game_name, q_council_id, q_new_council_name, q_is_speaker, q_has_speaker );

    my_encrypt( cur_key, buf, crypted_buf );
    
    sprintf( buf, "GAME_ID=%s&GAME_NAME=%s&COUNCIL_ID=%s&COUNCIL_NAME=%s&IS_SPEAKER=%s&HAS_SPEAKER=%s&AS_STRING=%s*", q_game_id, q_new_game_name, q_council_id, q_new_council_name, q_is_speaker, q_has_speaker, crypted_buf );

    send_output_to_client_raw( d, buf );
  }

  junk_query_string_list( mq );
}

