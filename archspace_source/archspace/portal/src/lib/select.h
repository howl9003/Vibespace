#ifndef DBBS_SELECT_H
#define DBBS_SELECT_H

#include <sys/time.h>

#include "include.h"
#include "define.h"
#include "descriptor.h"

int prepare_fd( int s, DESCRIPTOR *dlist, fd_set *input_set, fd_set *output_set, fd_set *exc_set );
void my_select( int max_fd, fd_set *input_set, fd_set *output_set, fd_set *exc_set );
void calc_remain_time( struct timeval *timeout, int how_many );
struct timeval timediff( struct timeval *a, struct timeval *b );
void my_sleep( struct timeval *timeout );
int raise_tick(int tic);

int new_descriptor( DESCRIPTOR **dlist, int mother_sock, fd_set *ifs, int max_dd );
void destruct_descriptor( DESCRIPTOR *e, DESCRIPTOR **dlist );
int my_accept( int mother_s );

#ifndef MICROSEC
#define MICROSEC 1000000
#endif

#endif
