#include "server.h"
#include "runtime.h"
#include "utils.h"
#include "cgi.h"
#include "utility.h"

static struct runtime_trigger *rt_triggers;

void init_runtime_trigger( char *name, int tick, int (*func)() )
{
  struct runtime_trigger *r;

  CREATE( r, struct runtime_trigger, 1 );
  strcpy( r->name, name );
  r->tick_interval = tick;
  r->last_tick = time(0);
  r->handler = func;
  r->next = rt_triggers;
  rt_triggers = r;
}

//  init_runtime_trigger( "sort rank", 900, sort_rank );
//  init_runtime_trigger( "regular shutdown", 10800, regular_shutdown );

void run_rt_trigger( int loop_per_sec )
{
  struct runtime_trigger *r;
  time_t now;

  now = time(0);
  r = rt_triggers;
  while( r ){
    if( r->last_tick+r->tick_interval < now ){
      log( LOG_IGNORE, "run time trigger %s(%d)", r->name, r->tick_interval );
      r->last_tick = now;
      r->handler();
    }
    r = r->next;
  }
}

