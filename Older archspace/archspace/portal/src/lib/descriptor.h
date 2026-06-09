#ifndef BBS_DESCRIPTOR_H
#define BBS_DESCRIPTOR_H

#include "include.h"
#include "message.h"
#include "define.h"
#include "comm_low.h"
#include "txt.h"

typedef struct descriptor_data DESCRIPTOR;
typedef struct connection_data CONNECTION;
typedef struct used_time_data USED_TIME;

typedef enum { STATUS_NONE, STATUS_ASK, STATUS_ACK, STATUS_WAIT, STATUS_END, STATUS_ERR } connection_status;

struct connection_data {
  int fd;
  char name[LINE_LENGTH+1];
  connection_status status;
  MSG_Q iq;
  MSG_Q oq;
  CONNECTION *next;
};

#define GET_CFD(c)     ((c)->fd)
#define GET_CNAME(c)   ((c)->name)
#define GET_CSTATUS(c) ((c)->status)
#define GET_CIQ(c)     ((c)->iq)
#define GET_COQ(c)     ((c)->oq)
#define GET_CNEXT(c)   ((c)->next)

struct used_time_data {
  time_t enter_time;
  time_t current_time;
  time_t total_time;
  time_t month_time;
  time_t week_time;
  time_t today_time;
  long connections;
};

#define GET_UTET(t)     ((t)->enter_time)
#define GET_UTCT(t)     ((t)->current_time)
#define GET_UTTT(t)     ((t)->total_time)
#define GET_UTMT(t)     ((t)->month_time)
#define GET_UTWT(t)     ((t)->week_time)
#define GET_UTTD(t)     ((t)->today_time)
#define GET_UTCON(t)    ((t)->connections)

typedef enum { D_BEGIN = 0, D_GET_ID, D_IN_MENU, D_DISCONNECT, D_ERR } descriptor_state;

struct descriptor_data {
  int id;
  char host[LINE_LENGTH];
  char passwd[PASSWD_LENGTH+1];
  CONNECTION mother_con;
  USED_TIME t;
  TXT_BLOCK *tlist;
  char buf[BUF_LENGTH];
  DESCRIPTOR *next;
  int idle;
  int imode;
  descriptor_state state;
  void *data;
/*  MENU *menu;
  int (*menu_func)();
  EDITOR *editor; */
};

#define GET_DID(d)        ((d)->id)
#define GET_DHOST(d)      ((d)->host)
#define GET_DPASSWD(d)    ((d)->passwd)
#define GET_DMCON(d)      ((d)->mother_con)
#define GET_DMFD(d)       ((d)->mother_con.fd)
#define GET_DMNAME(d)     ((d)->mother_con.name)
#define GET_DMSTATUS(d)   ((d)->mother_con.status)
#define GET_DMIQ(d)       ((d)->mother_con.iq)
#define GET_DMOQ(d)       ((d)->mother_con.oq)
#define GET_DUT(d)        ((d)->t)
#define GET_DCL(d)        ((d)->mother_con.next)
#define GET_DTXT(d)       ((d)->tlist)
#define GET_DBUF(d)       ((d)->buf)
#define GET_DNEXT(d)      ((d)->next)
#define GET_DIDLE(d)      ((d)->idle)
#define GET_DIMODE(d)     ((d)->imode)
#define GET_DSTATE(d)     ((d)->state)
#define GET_DDATA(d)      ((d)->data)
#define GET_DMENU(d)      ((d)->menu)
#define GET_DEDIT(d)      ((d)->editor)


CONNECTION *new_connection( int fd, char *name );
void junk_connection( CONNECTION *c );
void add_connection( DESCRIPTOR *d, CONNECTION *c );
int delete_connection( DESCRIPTOR *d, CONNECTION *c );

DESCRIPTOR *get_new_descriptor( void );
void junk_descriptor( DESCRIPTOR *d );

int insert_descriptor_in_list( DESCRIPTOR *e, DESCRIPTOR **dlist );
int delete_descriptor_in_list( DESCRIPTOR *e, DESCRIPTOR **dlist );
int find_descriptor_in_list( DESCRIPTOR *e, DESCRIPTOR *dlist );

#endif
