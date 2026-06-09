#include <sys/types.h>
#include <sys/socket.h>
#include <sys/wait.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>
#include <sys/time.h>
#include <fcntl.h>
#include <signal.h>
#include <setjmp.h>
#include <unistd.h>

#ifdef SOLARIS
#include <termio.h>
#endif

#include <arpa/telnet.h>

#include "select.h"
#include "utils.h"

int prepare_fd( int s, DESCRIPTOR *dlist, fd_set *input_set, fd_set *output_set, fd_set *exc_set )
{
  register DESCRIPTOR *point;
  register CONNECTION *c;
  int max_desc;

  assert(s);

  max_desc = s;

  FD_ZERO(input_set);
  FD_ZERO(output_set);
  FD_ZERO(exc_set);
  FD_SET(s, input_set);

  for (point = dlist; point; point = GET_DNEXT(point)) {
    FD_SET(GET_DMFD(point), input_set);
    FD_SET(GET_DMFD(point), exc_set);
    FD_SET(GET_DMFD(point), output_set);
    if( GET_DMFD(point) > max_desc ) max_desc = GET_DMFD(point); 
    for( c = GET_DCL(point); c; c = GET_CNEXT(c) ){
      FD_SET(GET_CFD(c), input_set);
      FD_SET(GET_CFD(c), exc_set);
      FD_SET(GET_CFD(c), output_set);
      if( GET_CFD(c) > max_desc ) max_desc = GET_CFD(c);
    }
  }
  return max_desc;
}

void my_select( int max_fd, fd_set *input_set, fd_set *output_set, fd_set *exc_set )
{
  static struct timeval null_time;

  if(select(max_fd+1,input_set,output_set,exc_set,&null_time)<0) {
    log(LOG_FATAL, "Select poll");
    exit(0);
  }
}

void calc_remain_time( struct timeval *timeout, int how_many )
{
  static struct timeval last_time, now, timespent;
  static struct timeval opt_time;
 
  if( opt_time.tv_usec == 0 ){
    gettimeofday(&last_time, (struct timezone *) 0);
  }
 
  opt_time.tv_usec = MICROSEC/how_many;
  gettimeofday(&now, (struct timezone *) 0);
  timespent = timediff(&now, &last_time);
  *timeout = timediff(&opt_time, &timespent);
  last_time.tv_usec += MICROSEC/how_many;

  while(last_time.tv_usec >= MICROSEC) {
    last_time.tv_usec -= MICROSEC;
    last_time.tv_sec++;
  }
}

struct timeval timediff(struct timeval *a, struct timeval *b)
{
  struct timeval rslt, tmp;

  tmp = *a;

  rslt.tv_usec = tmp.tv_usec - b->tv_usec;
  while(rslt.tv_usec < 0) {
    rslt.tv_usec += 1000000;
    --(tmp.tv_sec);
  }
  if ((rslt.tv_sec = tmp.tv_sec - b->tv_sec) < 0) {
    rslt.tv_usec = 0;
    rslt.tv_sec =0;
  }
  return(rslt);
}

void my_sleep(struct timeval *timeout)
{
  if(select(0,(fd_set *) 0,(fd_set *) 0,(fd_set *) 0,timeout)<0) {
    log(LOG_FATAL, "Select 0 sleep");
  }
}

int raise_tick(int tic)
{
  tic++;
  if( tic > 100000 ) tic = 0;
  return tic;
}

static int dbbs_descriptor_number;

int new_descriptor( DESCRIPTOR **dlist, int mother_sock, fd_set *ifs, int max_dd )
{
  int desc, len;
  DESCRIPTOR *newd;
  struct sockaddr_in ad;

  if( !FD_ISSET(mother_sock, ifs) ) return FALSE;
  if( (desc = my_accept(mother_sock)) == ABNORMAL ) return FALSE;

  if( dbbs_descriptor_number >= max_dd ){
    close( desc );
    return FALSE;
  }
  dbbs_descriptor_number++;

  newd = get_new_descriptor();
  GET_DMFD( newd ) = desc;
  GET_UTET(&GET_DUT(newd)) = time(0);
  GET_DSTATE( newd ) = D_BEGIN;

  len = sizeof(ad);
  if( getpeername( desc, (struct sockaddr *)&ad, &len ) == 0 )
    strcpy( GET_DHOST( newd ), inet_ntoa( ad.sin_addr ) );
  else
    GET_DHOST( newd )[0] = 0;

  insert_descriptor_in_list( newd, dlist );

  return desc;
}
  
int my_accept( int mother_s )
{
  struct sockaddr_in sa;
  int size, de;

  size = sizeof(sa);
  getsockname( mother_s, (struct sockaddr *) &sa, &size );
  if( (de = accept(mother_s, (struct sockaddr *)&sa, &size )) < 0 ){
    return ABNORMAL;
  }
  nonblock(de);
  return de;
}

void destruct_descriptor( DESCRIPTOR *e, DESCRIPTOR **dlist )
{
  MSG_DATA *m;

  close_connection( GET_DMFD(e) );
  while( (m = get_q( &GET_DMOQ(e) )) ) junk_msg_data( m );
  while( (m = get_q( &GET_DMIQ(e) )) ) junk_msg_data( m );

  while( GET_DCL(e) ) delete_connection( e, GET_DCL(e) );
  delete_descriptor_in_list( e, dlist );
  junk_descriptor( e );

  dbbs_descriptor_number--;
}
