#ifndef BBS_RLIMIT_H
#define BBS_RLIMIT_H

#include "include.h"
#include "define.h"
#include "macro.h"

#include <sys/time.h>
#include <sys/resource.h>

void set_max_resource( int *max_fd, int *max_core, int *max_cpu );
int set_limit_max( int resource );
int get_limit( int resource );

#endif

