#include "sighandle.h"
#include <unistd.h>

void signal_prepare(
  void (*my_signal_exits)(int signum),
  void (*my_stop_server)(int signum)
)
{
    void (*intern_exits)(int signum);
    void (*intern_stop)(int signum);

    if( my_signal_exits == NULL )
      intern_exits = signal_exits;
    else
      intern_exits = my_signal_exits;
    if( my_stop_server == NULL )
      intern_stop = stop_server;
      //intern_stop = SIG_IGN;
    else
      intern_stop = my_stop_server;

    signal(SIGTERM, intern_stop );
//    signal(SIGPIPE, intern_exits );
    signal(SIGPIPE, SIG_IGN);
//    signal(SIGSEGV, intern_exits );
    signal(SIGBUS, intern_exits );
    signal(SIGINT, SIG_IGN );
    signal(SIGHUP, intern_exits );
    signal(SIGQUIT, intern_exits );
    signal(SIGILL, intern_exits );
    signal(SIGTRAP, intern_exits );
    signal(SIGIOT, intern_exits );
    signal(SIGFPE, SIG_IGN );
    signal(SIGALRM, SIG_IGN );
    signal(SIGUSR1, SIG_IGN );
    signal(SIGUSR2, SIG_IGN );
    signal(SIGTSTP, SIG_IGN );
    signal(SIGTTIN, SIG_IGN );
    signal(SIGTTOU, SIG_IGN );
    signal(SIGIO, intern_exits );
    signal(SIGXCPU, intern_exits );
    signal(SIGXFSZ, intern_exits);
    signal(SIGVTALRM, intern_exits);
//    signal(SIGPROF, intern_exits);
    signal(SIGWINCH, SIG_IGN);
    signal(SIGURG, SIG_IGN);
//  signal(SIGEMT,intern_exits);
//  signal(SIGSYS,intern_exits);
}

void signal_ignore(int sig_num)
{
  log( LOG_IGNORE, "ignore signal : %d", sig_num );
}

extern int ext_shutdown;

void shutdown_server(int sig_num)
{
  ext_shutdown = 1;
}

void stop_server(int sig_num)
{
//  log( LOG_FATAL, "siganl stop server by %d", sig_num );
  exit(0);
}

void signal_exits(int sig_num)
{
  extern char *program_name;

  log( LOG_FATAL, "signal down server by %d", sig_num );
  gcore( getpid(), program_name );
  exit(1);
}

void my_signal_masking( sigset_t *s )
{
  sigemptyset( s );
  sigaddset( s, SIGSEGV );
  sigaddset( s, SIGTERM );
/*
  sigaddset( s, SIGUSR1 );
  sigaddset( s, SIGUSR2 );
  sigaddset( s, SIGINT  );
  sigaddset( s, SIGBUS  );
  sigaddset( s, SIGTSTP );
  sigaddset( s, SIGURG  );
  sigaddset( s, SIGXCPU );
  sigaddset( s, SIGHUP  );
  sigaddset( s, SIGPIPE );
  sigaddset( s, SIGALRM );
  sigaddset( s, SIGFPE  );
*/
}
