/***********************************************
 delete council forum
 by thedaz 2001/03/15

 compile: gcc -o delete_council_forum delete_council_forum.c -I/usr/local/include -lmysqlclient -L/usr/local/lib/mysql
***********************************************/

#include <stdio.h>
#include <stdlib.h>
#include <mysql/mysql.h>

int delete_council_forum(char *cCouncil)
{
  int iCouncil = 0;
  char query[256];

  MYSQL db;

  iCouncil = atoi(cCouncil);

  mysql_init(&db);

  if (!mysql_real_connect(&db, "localhost", "space", "rlaclrnr", "CouncilForum", 0, NULL, 0))
  {
    fprintf(stderr, "Failed to connect to database: Error: %s\n", mysql_error(&db));
    return -1;
  }

  sprintf(query, "DELETE FROM catagories WHERE cat_id=%d", iCouncil);

  if (mysql_query(&db, query) == -1)
  {
    fprintf(stderr, "Failed to write database: Error: %s\n", mysql_error(&db));
    return -1;
  }

  sprintf(query, "DELETE FROM forums WHERE cat_id=%d", iCouncil);

  if (mysql_query(&db, query) == -1)
  {
    fprintf(stderr, "Failed to write database: Error: %s\n", mysql_error(&db));
    return -1;
  }

  mysql_close(&db);

  return 1;
}

int main(int argc, char *argv[])
{
  if (argc != 2)
  {
    fprintf(stderr, "Error: delete council forum failed\n");
    exit(1);
  }

  delete_council_forum(argv[1]);

  return 1;
}
