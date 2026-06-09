#include "rlimit.h"

void set_max_resource( int *max_fd, int *max_core, int *max_cpu )
{
  *max_fd = set_limit_max(RLIMIT_NOFILE);
  *max_core = set_limit_max(RLIMIT_CORE);
  *max_cpu = set_limit_max(RLIMIT_CPU);
}

int set_limit_max( int resource )
{
  struct rlimit rl;

  getrlimit( resource, &rl );
  rl.rlim_cur = rl.rlim_max;
  setrlimit( resource, &rl );
  getrlimit( resource, &rl );

  return rl.rlim_cur;
}

int get_limit( int resource )
{
  struct rlimit rl;

  getrlimit( resource, &rl );
  return rl.rlim_cur;
}

