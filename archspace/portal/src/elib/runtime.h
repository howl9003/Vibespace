
struct runtime_trigger {
  char name[100];
  int tick_interval, last_tick;
  int (*handler)();
  struct runtime_trigger *next;
};

void init_runtime_trigger( char *name, int tick, int (*func)() );
void init_all_runtime_trigger();
void run_rt_trigger( int loop_per_sec );

