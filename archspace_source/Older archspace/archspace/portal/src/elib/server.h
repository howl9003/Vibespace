#include "comm_high.h"
#include "comm_low.h"
#include "comm_mid.h"
#include "select.h"
#include "define.h"
#include "utils.h"
#include "include.h"
#include "descriptor.h"
#include "macro.h"
#include "sighandle.h"
#include "message.h"

#define PACKET_CLOSE_CONNECTION 100

#define DESC_BUF_LEN 10240

#define EP_HEADER_SIZE 2

#define ERR_NONE                   0
#define ERR_ILLEGAL_PACKET_SIZE    -1
#define ERR_PACKET_RECEIVE_FAIL    -2
#define ERR_ILLEGAL_PACKET         -3

extern int debug;

struct descriptor_buf {
  char input_buf[DESC_BUF_LEN];
  int input_size;
  int ready;
  struct descriptor_buf *next;
};

#define GET_DLBUF(d)  ((struct descriptor_buf *)GET_DDATA(d))

void flush_desc_buf( DESCRIPTOR *d );

#define BUF_LEN 4000
void send_output_to_client(DESCRIPTOR *, ...);
void send_output_to_client_raw( DESCRIPTOR *d, const char *output );
void end_of_transmission( DESCRIPTOR *d );

extern char program_name[], server_name[];

