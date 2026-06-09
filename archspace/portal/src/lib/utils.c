#include <sys/types.h>
#include <unistd.h>
#include <sys/stat.h>
#include "utils.h"
#include "flib.h"

#ifdef FreeBSD
#include <stdio.h>
#include <fcntl.h>
#include <paths.h>
#include <kvm.h>
#include <sys/param.h>
#include <sys/sysctl.h>
#include <sys/user.h>
#endif

int do_log = 1;

void log(int type, ...)
{
  va_list args;
  long ct;
  char *tmstr, *fmt;
  FILE *fp;
  static char logfile[100], admin_logfile[100];

  if( *logfile == 0 ){
    if( get_configuration( "logfile", logfile ) <= 0 )
      strcpy( logfile, "log" );
  }
  if( *admin_logfile == 0 ){
    if( get_configuration( "admin_logfile", admin_logfile ) <= 0 )
      strcpy( admin_logfile, "/bbs/Log/adminlog" );
  }

  if( type == LOG_ADMIN )
  {
    fp = fopen( admin_logfile, "a" );
    chmod(admin_logfile, S_IRUSR | S_IWUSR | S_IRGRP | S_IROTH | S_IWOTH);
  }
  else
  {
    fp = fopen( logfile, "a" );
    chmod(logfile, S_IRUSR | S_IWUSR | S_IRGRP | S_IROTH | S_IWOTH);
  }

  va_start(args, type);
  fmt = va_arg(args, char *);
  ct = time(0);
  tmstr = (char *) asctime(localtime(&ct));
  *(tmstr + strlen(tmstr) - 1) = '\0';
  fprintf( fp, "%s :: ", tmstr+4 );
  vfprintf( fp, fmt, args );
  fprintf( fp, "\n" );
  va_end(args);

  fclose(fp);
}

int gcore( int pid, char *corefile )
{
  //char buf[BUF_LENGTH];
 
  //sprintf( buf, "/bin/gcore -o %s %d", corefile, pid );
  //system( buf );

  return 0;
}

void nonblock(int s)
{
  if(fcntl(s, F_SETFL, O_NDELAY) == -1) {
    log( LOG_FATAL, "nonblock failed %d", s );
    exit(1);
  }
}

int mem_usage()
{
  int   my_mem_usage = 0;
#ifdef LINUX
  FILE *fp;
  int   mypid = getpid();
  char  buf[256];

  sprintf(buf, "/proc/%d/stat", mypid);

  if((fp = fopen(buf, "r")) != NULL) {
    fscanf(fp, "%*d %*s %*c %*d %*d %*d %*d %*d %*u %*u %*u %*u %*u %*d %*d %*d %*d %*d %*d %*u %*u %*d %u", &my_mem_usage);
    fclose(fp);
  }
#else
#ifdef FreeBSD
  char errbuf[512];
  int nentries;
  int pid;
  kvm_t	*kd;
  struct kinfo_proc *kp;

  pid = getpid();
  if((kd = kvm_openfiles(_PATH_DEVNULL, _PATH_DEVNULL, _PATH_DEVNULL, O_RDONLY, errbuf)) == NULL)
     return -1;

  if((kp = kvm_getprocs(kd, KERN_PROC_PID, pid, &nentries)) == 0)
     return -2;

  my_mem_usage = (int)(kp->kp_eproc.e_vm.vm_map.size);
log(LOG_DEBUG, "mem_usage: text=%d, data=%d, stack=%d, resource=%d",
    kp->kp_eproc.e_vm.vm_tsize,
    kp->kp_eproc.e_vm.vm_dsize,
    kp->kp_eproc.e_vm.vm_ssize,
    kp->kp_eproc.e_vm.vm_rssize);

  kvm_close(kd);
#endif
#endif

  return my_mem_usage;
}

#ifdef SOLARIS
char *strsep(char **stringp, char *delim)
{
	register char *s;
	register const char *spanp;
	register int c, sc;
	char *tok;

	if ((s = *stringp) == NULL)
		return (NULL);
	for (tok = s;;) {
		c = *s++;
		spanp = delim;
		do {
			if ((sc = *spanp++) == c) {
				if (c == 0)
					s = NULL;
				else
					s[-1] = 0;
				*stringp = s;
				return (tok);
			}
		} while (sc != 0);
	}
	/* NOTREACHED */
}
#endif
