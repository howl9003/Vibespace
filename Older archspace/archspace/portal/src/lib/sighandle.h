#ifndef DBBS_SIGNAL_H
#define DBBS_SIGNAL_H

#include "include.h"
#include "utils.h"
#include <signal.h>
#include <sys/signal.h>

void signal_prepare(
  void (*my_signal_exits)(int signum),
  void (*my_stop_server)(int signum)
);
void signal_exits(int sig_num);
void sigint_ignore(int sig_num );
void stop_server(int sig_num);
void my_signal_masking( sigset_t *s );

#endif
