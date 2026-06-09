#include "server.h"
#include "utility.h"
#include "utils.h"

#include <stdarg.h>
#include <unistd.h>
#include <ctype.h>
#include "cgi.h"

int number( int i )
{
  double x;

  x = drand48() * (double)i;

  return (int)x;
}

int dice( int i, int j )
{
  int ret = 0;

  if( i <= 0 || j <= 0 ) return 0;
  for( ; i > 0; i-- ){
    ret += number(j);
  }
  return ret;
}

int sdice( int c )
{
  int r_value;

  r_value = (number(c)+number(c)+number(c)+number(c)+number(c)+number(c))/6;

  return r_value;
}

char *arch_strdup( char *str )
{
  char *ret;

  CREATE( ret, char, strlen(str)+1 );
  strcpy( ret, str );

  return ret;
}

int min( int a, int b )
{
  if( a < b ) return a;
  else return b;
}

int max( int a, int b )
{
  if( a > b ) return a;
  else return b;
}

int my_atoi( char *str )
{
  int r;

  for( r = 0; *str; str++ ){
    if( *str == ',' ) continue;
    if( isdigit( *str ) ) r = r*10 + *str - '0';
  }

  return r;
}

int my_isalnum( char c )
{
  if( c >= '0' && c <= '9' ) return 1;
  if( c >= 'A' && c <= 'Z' ) return 1;
  if( c >= 'a' && c <= 'z' ) return 1;

  return 0;
}

char char_pool[]="ABCDEFHJKLMNPQRSTUVWXYZ12345789";

char *get_random_str(int len)
{
        static char buf[81];

        register int i;

        buf[len] = '\0';

        for(i = 0; i < len; i++)
                buf[i] = char_pool[(rand() / 2) % strlen(char_pool)];

        return buf;
}

char *char_to_hex( unsigned char c )
{
  static char hex[3];
  int t;

  t = c/16;
  if( t < 10 ) hex[0] = '0'+t;
  else hex[0] = 'A'+t-10;

  t = c%16;
  if( t < 10 ) hex[1] = '0'+t;
  else hex[1] = 'A'+t-10;

  return hex;
}

