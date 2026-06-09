/*
 * mysqllib.c - mySQL custom library functions
 *
 * by Abraxas - June 14
 *
 * - must be compiled with mysql.lib
 */

#include "utils.h"
#include "utility.h"

#include "mysqllib.h"
#include <unistd.h>
#include <stdlib.h>

char m_buf[10000];

/*
 * Function: init_mysql
 * 	Initialize and Randomize
 *
 * Arguments: NONE
 *
 * Return Values:
 *	S_OK:	Everything is okay
 *	S_ERR:	Something's wrong
 */
MYSQL *init_mysql( char *host, char *db_name, char *account, char *password )
{
	int count;
	MYSQL *mysql_sock;

	CREATE( mysql_sock, MYSQL, 1 );
	
	count = 0;
	while( count < 3 ){
	  if( mysql_real_connect(mysql_sock, host, account, password, db_name, 3306, NULL, 0) ) break;
	  count++;
	  sleep(2);
	}

	if( count == 3 ){
		log( LOG_FATAL, "Failed to connect to game DB : Error: %s", mysql_error(mysql_sock));
		free(mysql_sock);
		return NULL;
	}
	return mysql_sock;
}

