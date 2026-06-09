/*
 * mysqllib.h
 *
 * by Abraxas
 *
 * Prototype definitions for mySQL libraries (for Abe's routine)
 */


#ifndef _MYSQLLIB_H
#define _MYSQLLIB_H

#include <stdio.h>
#include <sys/types.h>
#include <sys/socket.h>

#include <mysql.h>

/* Prototype Definitions - Global functions */
MYSQL *init_mysql( char *host, char *db_name, char *account, char *password );  // Initialize mySQL Library

// Extern Variables

extern char m_buf[];

#endif _MYSQLLIB_H
