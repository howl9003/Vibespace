#include "txt.h"
#include "memory.h"
#include "utils.h"

static TXT_BLOCK *garbage_txt_block;

TXT_BLOCK *get_new_txt()
{
  TXT_BLOCK *t;

  if( garbage_txt_block ){
    t = garbage_txt_block;
    garbage_txt_block = GET_TNEXT(t);
  } else {
    CREATE( t, TXT_BLOCK, 1 );
  }

  return t;
}

TXT_BLOCK *str_to_txt( char *str )
{
  TXT_BLOCK *t;

  t = get_new_txt();

  GET_TSIZE(t) = strlen(str);
  GET_TSIZE(t) = (GET_TSIZE(t)>LINE_LENGTH)?LINE_LENGTH:GET_TSIZE(t);
  strncpy( GET_TTEXT(t), str, GET_TSIZE(t) );
  GET_TTEXT(t)[GET_TSIZE(t)] = 0;
  GET_TNEXT(t) = NULL;

  return t;
}

TXT_BLOCK *str_to_txt_list( char *str )
{
  TXT_BLOCK *t, *ret, *oldt;
  int size, i;

  size = strlen( str );

  ret = str_to_txt( str );
  size -= GET_TSIZE(ret);
  oldt = ret;
  i = 1;

  while( size > 0 ){
    t = str_to_txt( str+LINE_LENGTH*i );
    GET_TNEXT(oldt) = t;
    oldt = t;
    size -= GET_TSIZE(t);
    i++;
  }

  return ret;
}

void junk_txt( TXT_BLOCK *t )
{
  GET_TNEXT(t) = garbage_txt_block;
  garbage_txt_block = t;
}

void junk_txt_list( TXT_BLOCK *t )
{
  TXT_BLOCK *n;

  for( ; t; t = n ){
    n = GET_TNEXT(t);
    junk_txt( t );
  }
}

