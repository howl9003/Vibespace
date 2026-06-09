#ifndef COMM_MID_H
#define COMM_MID_H

#include "include.h"
#include "flib.h"
#include "message.h"
#include "descriptor.h"

int connect_server( DESCRIPTOR *d, char *server_name );
void disconnect_server( CONNECTION *c );

int send_anything( CONNECTION *c, sh_int size, byte msg_type, byte msg_status, char *msg );
int send_ack( CONNECTION *c, byte msg_status );
int send_ask( CONNECTION *c, sh_int size, byte msg_status, char *msg );
int send_ans( CONNECTION *c, sh_int size, byte msg_status, char *msg );
int send_ask_what( CONNECTION *c, sh_int size, byte msg_status, char *msg );
int send_err( CONNECTION *c, byte msg_status );

int receive_anything( CONNECTION *c, sh_int *size, byte *msg_type, byte *msg_status, char *msg );
int receive_ack( CONNECTION *c );
int receive_ask( CONNECTION *c, sh_int *size, byte *msg_status, char *msg );
int receive_ask_what( CONNECTION *c, sh_int *size, byte *msg_status, char *msg );
int receive_ans( CONNECTION *c, sh_int *size, byte *msg_status, char *msg );
int receive_err( CONNECTION *c );

#endif

