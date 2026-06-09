#include "server.h"
#include <time.h>
#include <locale.h>
#include <unistd.h>
#include "utility.h"

#include "rlimit.h"
#include "runtime.h"
#include "cgi.h"

#include <sys/types.h>
#include <sys/stat.h>

void process_exception_all( DESCRIPTOR **dlist, fd_set *ist, fd_set *ost, fd_set *est );
void process_io_all( DESCRIPTOR **dlist, fd_set *ist, fd_set *ost, fd_set *est );
void process_page_all( DESCRIPTOR **dlist );
void process_page( DESCRIPTOR *d, QUERY_STRING *q );
void my_destruct_descriptor( DESCRIPTOR *t, DESCRIPTOR **dlist );
void my_new_descriptor( DESCRIPTOR **dlist, int s, fd_set *input_set, int max_user );
void my_signal_exits(int signum), my_stop_server(int signum);
void initialize( void ), prepare_exit( void );
void process_begin( DESCRIPTOR *d );
QUERY_STRING *parse_input( DESCRIPTOR *d );
void load_configuration( void );
int main_loop( int entry_server_fd );
void add_message_input( DESCRIPTOR *d, MSG_DATA *m );

int ext_max_desc;
int ext_desc_resource;
int ext_shutdown = 0;
int ext_max_user = 1000;
int debug = 0;
int entry_server_fd;

int alloced_mem;

char program_name[100], server_name[100];

DESCRIPTOR *ext_dlist;

int
main( int argc, char **argv )
{
  int dummy, coresize;
  int seed;

  seed = time(NULL) * 1000 + getpid();
  srand(seed);
  srand48(seed);

  if( argc != 2 ){
    printf( "Usage : %s config_file_name\n", argv[0] );
    exit(0);
  }

  if( set_config_file( argv[1] ) == 0 ){
    printf( "config file %s does not exist.", argv[1] );
    exit(1);
  }

  initialize();
  log( LOG_IGNORE, "server name = %s, connection name = %s", program_name, server_name );

  set_max_resource( &ext_desc_resource, &coresize, &dummy );
  log( LOG_IGNORE, "max descriptor = %d, max coresize = %d ", ext_desc_resource, coresize );

  signal_prepare( my_signal_exits, my_stop_server );
  entry_server_fd = open_mother_connection( server_name );
  if( entry_server_fd == ABNORMAL ){
    log( LOG_FATAL, "configuration %s is illegal\n", server_name );
    exit(0);
  } else if( entry_server_fd == FALSE ){
    log( LOG_FATAL, "port %s in use\n", server_name );
    exit(0);
  } else {
    log( LOG_IGNORE, "booting successful! %d", entry_server_fd );
  }

  main_loop( entry_server_fd );

  return 0;
}

#define LOOP_PER_SEC 7

int main_loop( int entry_server_fd )
{
  fd_set input_set, output_set, exc_set;
  struct timeval timeout;
  int tick = 0;
  sigset_t mask, tmp_mask;

  my_signal_masking( &mask );
  ext_max_desc = entry_server_fd;

  while( ext_shutdown == 0 ) {
    ext_max_desc = prepare_fd( entry_server_fd, ext_dlist, &input_set, &output_set, &exc_set );
    calc_remain_time( &timeout, LOOP_PER_SEC ); /* loop 50 times per 1 sec */

    sigprocmask( SIG_SETMASK, &mask, &tmp_mask );
    my_select( ext_max_desc, &input_set, &output_set, &exc_set );
    my_sleep(&timeout);
    sigprocmask( SIG_SETMASK, &tmp_mask, NULL );

    process_exception_all( &ext_dlist, &input_set, &output_set, &exc_set );
    process_io_all( &ext_dlist, &input_set, &output_set, &exc_set );
    process_page_all( &ext_dlist );

    if( FD_ISSET( entry_server_fd, &input_set ) )
      my_new_descriptor(&ext_dlist, entry_server_fd, &input_set, ext_max_user);

    tick = raise_tick(tick);
    if( tick%(LOOP_PER_SEC/2) == 0 )
      run_rt_trigger( LOOP_PER_SEC );

  }
  prepare_exit();

  return 0;
}

void process_exception_all( DESCRIPTOR **dlist, fd_set *ist, fd_set *ost, fd_set *est )
{
  register DESCRIPTOR *t, *n;

  for( t = *dlist; t; t = n ){
    n = GET_DNEXT(t);
    if( FD_ISSET(GET_DMFD(t),est) ){
      FD_CLR(GET_DMFD(t),ist);
      FD_CLR(GET_DMFD(t),ost);
      my_destruct_descriptor( t, dlist );
      continue;
    }
  }
}

void process_io_all( DESCRIPTOR **dlist, fd_set *ist, fd_set *ost, fd_set *est )
{
  register DESCRIPTOR *t, *n;

  for( t = *dlist; t; t = n ){
    n = GET_DNEXT(t);
    if( FD_ISSET(GET_DMFD(t),ist) ){
      if( process_mother_connection_input_just( t ) == ABNORMAL ){
        my_destruct_descriptor( t, dlist );
        continue;
      }
    }
    if( FD_ISSET(GET_DMFD(t),ost) ){
      if( process_mother_connection_output_just( t ) == ABNORMAL ){
        my_destruct_descriptor( t, dlist );
        continue;
      }
    }
  }
}

void process_page_all( DESCRIPTOR **dlist )
{
  register DESCRIPTOR *t, *n;
  MSG_DATA *md;
  QUERY_STRING *input;

  for( t = *dlist; t; t = n ){
    n = GET_DNEXT(t);
    while( (md = get_q(&GET_DMIQ(t))) ){
      add_message_input( t, md );
      junk_msg_data( md );
    }
    if( GET_DLBUF(t)->ready ){
      input = parse_input( t );
      process_page( t, input );
      end_of_transmission( t );
      flush_desc_buf( t );
      junk_query_string_list( input );
      GET_DLBUF(t)->ready = 0;
    }
  }
}

struct descriptor_buf *garbage_descriptor_buf;

void my_destruct_descriptor( DESCRIPTOR *t, DESCRIPTOR **dlist )
{
//  log( LOG_DEBUG, "close connection %d %s", GET_DMFD(*dlist), GET_DHOST(*dlist) );
  GET_DLBUF(t)->next = garbage_descriptor_buf;
  garbage_descriptor_buf = GET_DLBUF(t);

  destruct_descriptor( t, dlist );
}

void my_new_descriptor( DESCRIPTOR **dlist, int s, fd_set *input_set, int max_user )
{
  while( new_descriptor( dlist, s, input_set, max_user ) ){
  if( garbage_descriptor_buf ){
    GET_DDATA(*dlist) = garbage_descriptor_buf;
    garbage_descriptor_buf = garbage_descriptor_buf->next;
  } else
    CREATE( GET_DDATA(*dlist), struct descriptor_buf, 1 );
    GET_DID(*dlist) = 0;
    GET_DLBUF(*dlist)->input_size = 0;
    GET_DLBUF(*dlist)->ready = 0;
  }
//  log( LOG_DEBUG, "new connection %d %s", GET_DMFD(*dlist), GET_DHOST(*dlist) );
}

void my_signal_exits(int signum)
{
  signal_exits(signum);
}

void my_stop_server(int signum)
{
  stop_server(signum);
}

QUERY_STRING *parse_input( DESCRIPTOR *d )
{
  QUERY_STRING *t;

  t = parse_string( GET_DLBUF(d)->input_buf );

  GET_DLBUF(d)->input_buf[0] = 0;
  GET_DLBUF(d)->input_size = 0;
  return t;
}

void flush_desc_buf( DESCRIPTOR *d )
{
  char buf[MSG_SIZE];
  int c = 0;

  if( GET_DLBUF(d)->input_size == 0 ) return;

  if( GET_DLBUF(d)->input_size > MSG_SIZE/2 ){
    memcpy( buf, GET_DLBUF(d)->input_buf, MSG_SIZE/2 );
    c += MSG_SIZE/2;
    buf[MSG_SIZE/2] = 0;
    send_ans( &(GET_DMCON(d)), MSG_SIZE/2, 0, buf );
  }
  memcpy( buf, GET_DLBUF(d)->input_buf+c, GET_DLBUF(d)->input_size-c );
  buf[GET_DLBUF(d)->input_size-c] = 0;
  send_ans( &(GET_DMCON(d)), GET_DLBUF(d)->input_size-c, 0, buf );

  GET_DLBUF(d)->input_size = 0;
}

void add_message_input( DESCRIPTOR *d, MSG_DATA *m )
{
  char *wave;

  if( strlen( GET_MMSG(m) ) + GET_DLBUF(d)->input_size >= DESC_BUF_LEN ){
    GET_DLBUF(d)->ready = 1;
    return;
  }
  wave = memchr( GET_MMSG(m), '~', GET_MMSIZE(m) );
  if( memchr( GET_MMSG(m), 0, GET_MMSIZE(m) ) || wave ){
    GET_DLBUF(d)->ready = 1;
    if( wave ) *wave = 0;
  }
  strncpy( GET_DLBUF(d)->input_buf + GET_DLBUF(d)->input_size, GET_MMSG(m), GET_MMSIZE(m) );
  GET_DLBUF(d)->input_size += GET_MMSIZE(m);
  GET_DLBUF(d)->input_buf[GET_DLBUF(d)->input_size] = 0;
}

static char c_buf[BUF_LEN];

void send_output_to_client(d, va_alist)
DESCRIPTOR *d;
va_dcl
{
  va_list args;
  char *fmt;

  va_start(args);
  fmt = va_arg(args, char *);
  vsprintf( c_buf, fmt, args );
  send_output_to_client_raw( d, c_buf );
  va_end(args);
}

void send_output_to_client_raw( DESCRIPTOR *d, const char *output )
{
  int len;

  len = strlen(output);
  if( len + GET_DLBUF(d)->input_size >= 3000 ) flush_desc_buf(d);
  strcpy( GET_DLBUF(d)->input_buf+GET_DLBUF(d)->input_size, output );
  GET_DLBUF(d)->input_size += len;

//  log( LOG_DEBUG, "%s", output );
}

void end_of_transmission( DESCRIPTOR *d )
{
  GET_DLBUF(d)->input_buf[GET_DLBUF(d)->input_size++] = 0;
}

