#include "comm_mid.h"
#include "memory.h"
#include "macro.h"

int connect_server( DESCRIPTOR *d, char *server_name )
{
  int fd;
  CONNECTION *c;

  fd = open_connection( server_name );
  if(fd == ABNORMAL) return ABNORMAL;

  c = new_connection( fd, server_name );
  add_connection( d, c );

  return TRUE;
}

/* disconnect_server does not disconnect actually. it leaves only disconnect
message on out queue so that actual output function can disconnect.    */
void disconnect_server( CONNECTION *c )
{
  MSG_DATA *m;

  m = new_msg_data( GET_CFD(c), 0, MSG_DISCONNECT, PACKET_NONE, "" );
  put_q( &GET_COQ(c), m );
}

int send_anything( CONNECTION *c, sh_int size, byte msg_type, byte msg_status, char *msg )
{
  int packet_num, i;
  MSG_DATA *m;

  packet_num = size/MSG_SIZE;
  for( i = 0; i < packet_num; i++, size -= MSG_SIZE ){
    m = new_msg_data( GET_CFD(c), MSG_SIZE, msg_type, BIT_SET(msg_status,PACKET_CONTINUE), msg+i*MSG_SIZE );
    put_q( &GET_COQ(c), m );
  }
  m = new_msg_data( GET_CFD(c), size, msg_type, BIT_SET(msg_status,PACKET_END), msg+packet_num*MSG_SIZE );
  put_q( &GET_COQ(c), m );
  return packet_num+1;
}

int send_ack( CONNECTION *c, byte msg_status )
{
  return send_anything( c, 0, MSG_ACK, msg_status, "" );
}

int send_ask( CONNECTION *c, sh_int size, byte msg_status, char *msg )
{
  return send_anything( c, size, MSG_ASK, msg_status, msg );
}

int send_ans( CONNECTION *c, sh_int size, byte msg_status, char *msg )
{
  return send_anything( c, size, MSG_ANS, msg_status, msg );
}

int send_ask_what( CONNECTION *c, sh_int size, byte msg_status, char *msg )
{
  return send_anything( c, size, MSG_ASK_WHAT, msg_status, msg );
}

int send_err( CONNECTION *c, byte msg_status )
{
  return send_anything( c, 0, MSG_ERR, msg_status, "" );
}

int receive_anything( CONNECTION *c, sh_int *size, byte *msg_type, byte *msg_status, char *msg )
{
  MSG_DATA *m;

  m = get_q( &GET_CIQ(c) );
  if( m == NULL ) return FALSE;

  *size = GET_MSIZE(m) - HEADER_SIZE;
  *msg_type = GET_MTYPE(m);
  *msg_status = GET_MSTATUS(m);
  memcpy( msg, GET_MMSG(m), *size );
  return TRUE;
}

int receive_ack( CONNECTION *c )
{
  MSG_DATA *m;

  m = get_q( &GET_CIQ(c) );
  if( m == NULL ) return FALSE;

  if( GET_MTYPE(m) != MSG_ACK ) return ABNORMAL;
  return TRUE;
}

int receive_err( CONNECTION *c )
{
  MSG_DATA *m;

  m = get_q( &GET_CIQ(c) );
  if( m == NULL ) return FALSE;

  if( GET_MTYPE(m) != MSG_ERR ) return ABNORMAL;
  return TRUE;
}

int receive_ask( CONNECTION *c, sh_int *size, byte *msg_status, char *msg )
{
  byte msg_type;
  int ret;

  ret = receive_anything( c, size, &msg_type, msg_status, msg );
  if( ret == FALSE ) return ret;

  if( msg_type == MSG_ASK ) return TRUE;
  return ABNORMAL;
}

int receive_ask_what( CONNECTION *c, sh_int *size, byte *msg_status, char *msg )
{
  byte msg_type;
  int ret;

  ret = receive_anything( c, size, &msg_type, msg_status, msg );
  if( ret == FALSE ) return ret;

  if( msg_type == MSG_ASK_WHAT ) return TRUE;
  return ABNORMAL;
}

int receive_ans( CONNECTION *c, sh_int *size, byte *msg_status, char *msg )
{
  byte msg_type;
  int ret;

  ret = receive_anything( c, size, &msg_type, msg_status, msg );
  if( ret == FALSE ) return ret;

  if( msg_type == MSG_ANS ) return TRUE;
  return ABNORMAL;
}

