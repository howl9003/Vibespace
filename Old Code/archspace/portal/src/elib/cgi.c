#include "server.h"
#include "cgi.h"

#include "message.h"
#include "utility.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>


char *split_word( char *out, char *in, char stop )
{
  while( *in == ' ' ) in++;
  while( *in != stop && *in ){
    *out++ = *in++;
  }
  
  *out = 0;
  if( *in ) return in+1;
  else return NULL;
}

unsigned char x2c( char *x )
{
  unsigned char c;

  c = ( x[0] >= 'A' ? ( ( x[0] & 0xdf ) - 'A' ) + 10 : ( x[0] - '0' ) );
  c *= 16;
  c += ( x[1] >= 'A' ? ( ( x[1] & 0xdf ) - 'A' ) + 10 : ( x[1] - '0' ) );

  return c;
}

void unescape_url( char *url )
{
  int i, j;

  for( i = 0, j = 0; url[j]; i++, j++ ){
    if( (url[i] = url[j]) == '%' ){
      (unsigned char)url[i] = x2c( url+j+1 );
      j += 2;
    } else if( url[j] == '+' ){
      url[i] = ' ';
    }
  }
  url[i] = 0;
}

char *search_query_string( QUERY_STRING *qlist, char *name )
{
  QUERY_STRING *q;

  q = qlist;
  while( q ){
    if( strcmp( q->name, name ) == 0 ) break;
    q = q->next;
  }

  if( q == NULL ) return NULL;
  return q->value;
}

QUERY_STRING *garbage_query_string;

QUERY_STRING *new_query_string( char *name, char *value )
{
  QUERY_STRING *r;

  if( garbage_query_string == NULL ){
    CREATE( r, QUERY_STRING, 1 );
  } else {
    r = garbage_query_string;
    garbage_query_string = garbage_query_string->next;
  }

  strcpy( r->name, name );
  strcpy( r->value, value );

  return r;
}

void junk_query_string_list( QUERY_STRING *q )
{
  QUERY_STRING *t;

  if( q == NULL ) return;
  while( q ){
    t = q->next;
    q->next = garbage_query_string;
    garbage_query_string = q;
    q = t;
  }
}

void urlencode( char *src, char *dest )
{
  char *hex;

  while( *src ){
    if( my_isalnum(*src) || *src == '-' || *src == '_' || *src == '.' ) *dest++ = *src;
    else if( *src == ' ' ) *dest++ = '+';
    else {
      hex = char_to_hex( (unsigned char) *src );
      *dest++ = '%';
      *dest++ = hex[0];
      *dest++ = hex[1];
    }
    src++;
  }
  *dest = 0;
}

QUERY_STRING *parse_string( char *str )
{
  QUERY_STRING q, *t;
  char *qs, *ts, *tb, buf[BUF_LEN], buf2[BUF_LEN];

  qs = str;
  q.next = NULL;

  ts = split_word( buf, qs, '&' );
  while(1) {
    unescape_url( buf );
    tb = split_word( buf2, buf, '=' );
    if( tb ){
      t = new_query_string( buf2, tb );
      t->next = q.next;
      q.next = t;
    } else break;
    if( ts == NULL ) break;
    ts = split_word( buf, ts, '&' );
  } 

  return q.next;
}

