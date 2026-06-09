/****************************************************************************

  message handling library
  this library contains operation on msg_data and msg_queue.

****************************************************************************/

#include "include.h"
#include "message.h"
#include "memory.h"
#include "utils.h"

static MSG_DATA *garbage_msg_data;

int msg_data_num;

MSG_DATA *new_msg_data( int fd, sh_int size, byte msg_type, byte msg_status, char *msg )
{
  MSG_DATA *ret;

  if( garbage_msg_data ){
    ret = garbage_msg_data;
    garbage_msg_data = GET_MNEXT(garbage_msg_data);
  } else {
    CREATE( ret, MSG_DATA, 1 );
    msg_data_num++;
  }

  size = (size>MSG_SIZE)?MSG_SIZE:size;

  GET_MFD(ret) = fd;
  GET_MSIZE(ret) = size+HEADER_SIZE;
  GET_MSENT(ret) = 0;
  GET_MTYPE(ret) = msg_type;
  GET_MSTATUS(ret) = msg_status;

  memcpy( GET_MMSG(ret), msg, size );
//  BYTE ORDER
  *((unsigned char *) GET_MPSIZE(ret) + 0) = size % 255;
  *((unsigned char *) GET_MPSIZE(ret) + 1) = size / 256;
//  memcpy( GET_MPSIZE(ret), (char *)&GET_MSIZE(ret), 2 );
  *GET_MPTYPE(ret) = msg_type;
  *GET_MPSTATUS(ret) = msg_status;

  return ret;
}

MSG_DATA *packet_to_msg( int fd, int size, char *packet )
{
  MSG_DATA *ret;

  if( garbage_msg_data ){
    ret = garbage_msg_data;
    garbage_msg_data = GET_MNEXT(garbage_msg_data);
  } else {
    CREATE( ret, MSG_DATA, 1 );
  }

  GET_MFD(ret) = fd;
  memcpy( GET_MPACKET(ret), packet, size );
  GET_MSIZE(ret) = size;
  GET_MSENT(ret) = 0;
  GET_MTYPE(ret) = packet[OFF_MSG_TYPE];
  GET_MSTATUS(ret) = packet[OFF_MSG_STATUS];

  return ret;
}

void junk_msg_data( MSG_DATA *md )
{
  assert( md );

  GET_MNEXT(md) = garbage_msg_data;
  garbage_msg_data = md;
}

void init_q( MSG_Q *mq )
{
  assert( mq );

  GET_MQHEAD(mq) = NULL;
  GET_MQTAIL(mq) = NULL;
}

MSG_DATA *get_q( MSG_Q *mq )
{
  MSG_DATA *ret;

  assert( mq );

  if( GET_MQHEAD(mq) == NULL ) return NULL;

  ret = GET_MQHEAD(mq);
  GET_MQHEAD(mq) = GET_MNEXT(ret);  

  if( GET_MQHEAD(mq) == NULL ) GET_MQTAIL(mq) = NULL;

  return ret;
}

void put_q( MSG_Q *mq, MSG_DATA *md )
{
  if( GET_MQHEAD(mq) == NULL ){
    GET_MNEXT(md) = NULL;
    GET_MQHEAD(mq) = GET_MQTAIL(mq) = md;
  } else {
    GET_MNEXT(md) = NULL;
    GET_MNEXT(GET_MQTAIL(mq)) = md;
    GET_MQTAIL(mq) = md;
  } 
}

void unget_q( MSG_Q *mq, MSG_DATA *md )
{
  if( GET_MQHEAD(mq) == NULL ){
    GET_MNEXT(md) = NULL;
    GET_MQHEAD(mq) = GET_MQTAIL(mq) = md;
  } else {
    GET_MNEXT(md) = GET_MQHEAD(mq);
    GET_MQHEAD(mq) = md;
  } 
}
  
void refresh_q( MSG_Q *mq )
{
  MSG_DATA *tmp;

  for( tmp = get_q(mq); tmp; tmp = get_q(mq) )
    junk_msg_data( tmp );
}


