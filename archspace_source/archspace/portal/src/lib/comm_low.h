#ifndef BBS_COMM_LOW_H
#define BBS_COMM_LOW_H

#include "include.h"
#include "flib.h"
#include "message.h"

int open_mother_connection( char *my_name );
int open_connection( char *server_name );
void close_connection( int fd );

int write_msg( MSG_DATA *md );
MSG_DATA *read_msg( int fd, int *error_code );

int write_just( int fd, int size, char *msg );
int read_just( int fd, int max, char *msg );

#endif

