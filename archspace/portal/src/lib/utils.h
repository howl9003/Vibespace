#ifndef BBS_UTILS_H
#define BBS_UTILS_H

#include <stdarg.h>
#include <stdio.h>

#include "include.h"
#include "define.h"
#include "macro.h"

#define log	arch_log

void log(int, ...);
int gcore( int pid, char *corefile );
void nonblock( int s );
int mem_usage();
char *mystrsep(char **stringp, char *delim);

#define LOG_FATAL     0
#define LOG_IGNORE    1
#define LOG_WARNING   2
#define LOG_DEBUG     3
#define LOG_ERROR     4
#define LOG_ADMIN     5

// #define LOGFILE       "log"
// #define ADMIN_LOGFILE       "/bbs/Log/adminlog"

#endif

