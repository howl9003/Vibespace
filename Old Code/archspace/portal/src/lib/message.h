#ifndef BBS_MESSAGE_H
#define BBS_MESSAGE_H

#include "define.h"

#define PACKET_SIZE 8192
#define HEADER_SIZE 4
#define MSG_SIZE (PACKET_SIZE-HEADER_SIZE)

#define OFF_MSG_SIZE 0
#define OFF_MSG_TYPE 2
#define OFF_MSG_STATUS 3

typedef struct msg_queue MSG_Q;
typedef struct msg_data MSG_DATA;

/* message types                                     */
enum { MSG_NONE, MSG_ASK, MSG_ACK, MSG_ASK_WHAT, MSG_ANS, MSG_ERR, MSG_DISCONNECT };

#define PACKET_NONE     0
#define PACKET_CONTINUE 1
#define PACKET_END      2
#define PACKET_YES      4
#define PACKET_NO       8
#define PACKET_WAIT    16

struct msg_queue {
  MSG_DATA *header, *tail;
};

#define GET_MQHEAD(q) ((q)->header)
#define GET_MQTAIL(q) ((q)->tail)

struct msg_data {
  int fd;
  sh_int size;
  sh_int sent;
  byte msg_type, msg_status;
  char packet[PACKET_SIZE+1];
  MSG_DATA *next;
};

#define GET_MFD(m)      ((m)->fd)
#define GET_MSIZE(m)    ((m)->size)
#define GET_MSENT(m)    ((m)->sent)
#define GET_MMSIZE(m)   ((m)->size-HEADER_SIZE)
#define GET_MTYPE(m)    ((m)->msg_type)
#define GET_MSTATUS(m)  ((m)->msg_status)
#define GET_MNEXT(m)    ((m)->next)
#define GET_MPACKET(m)  ((m)->packet)
#define GET_MMSG(m)     ((m)->packet+HEADER_SIZE)
#define GET_MPSIZE(m)   ((m)->packet+OFF_MSG_SIZE)
#define GET_MPTYPE(m)   ((m)->packet+OFF_MSG_TYPE)
#define GET_MPSTATUS(m) ((m)->packet+OFF_MSG_STATUS)

MSG_DATA *new_msg_data( int fd, sh_int size, byte msg_type, byte msg_status, char *msg );
MSG_DATA *packet_to_msg( int fd, int size, char *packet );
void junk_msg_data( MSG_DATA *md );

void init_q( MSG_Q *mq );
MSG_DATA *get_q( MSG_Q *mq );
void put_q( MSG_Q *mq, MSG_DATA *md );
void unget_q( MSG_Q *mq, MSG_DATA *md );
void refresh_q( MSG_Q *mq );

#endif
