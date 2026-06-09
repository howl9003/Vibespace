/***********************************************
 create council forum
 by thedaz 2001/03/09

 compile: gcc -o create_council_forum create_council_forum.c -I/usr/local/include -lmysqlclient -L/usr/local/lib/mysql
***********************************************/

#include <stdio.h>
#include <stdlib.h>
#include <mysql/mysql.h>

int create_council_forum(char *cCouncil, char *cCouncilName)
{
  int iCouncil = 0;
  char query[256];
  char str[256];

  MYSQL db;

  iCouncil = atoi(cCouncil);

  mysql_init(&db);

  if (!mysql_real_connect(&db, "localhost", "space", "rlaclrnr", "CouncilForum", 0, NULL, 0))
  {
    fprintf(stderr, "Failed to connect to database: Error: %s\n", mysql_error(&db));
    return -1;
  }

  sprintf(query, "INSERT INTO catagories (cat_id, cat_title, cat_order) VALUES ('%d', '%s', '1')", iCouncil, cCouncilName);

  if (mysql_query(&db, query) == -1)
  {
    fprintf(stderr, "Failed to write database: Error: %s\n", mysql_error(&db));
    return -1;
  }

  // sprintf(str, "Successed to write cat table (council) - %d %s", iCouncil, cCouncilName);
  // SLOG(str); 

  sprintf(query, "INSERT INTO forums (forum_name, forum_desc, forum_access, cat_id) VALUES ('%s', '%s', '2', '%d')", "Council Forum", "blah~", iCouncil);

  if (mysql_query(&db, query) == -1)
  { 
    fprintf(stderr, "Failed to write database: Error: %s\n", mysql_error(&db));
    return -1;
  }

  // sprintf(str, "Successed to write forums table (council) - %d %s", iCouncil, cCouncilName);
  // SLOG(str); 

  mysql_close(&db);

  return 1;
}

int main(int argc, char *argv[])
{
  if (argc != 3)
  {
    fprintf(stderr, "Error: new council forum failed\n");
    exit(1);
  }

  create_council_forum(argv[1], argv[2]);

  return 1;
}
