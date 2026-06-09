#include "descriptor.h"
#include "memory.h"
#include "utils.h"

static CONNECTION *garbage_connection_data;
static DESCRIPTOR *garbage_descriptor_data;

CONNECTION *new_connection( int fd, char *name )
{
  CONNECTION *ret;

  if( garbage_connection_data ){
    ret = garbage_connection_data;
    garbage_connection_data = ret->next;
  } else CREATE( ret, CONNECTION, 1 );

  strncpy( GET_CNAME(ret), name, LINE_LENGTH );
  GET_CFD(ret) = fd;
  GET_CSTATUS(ret) = STATUS_NONE;
  GET_CNEXT(ret) = NULL;
  init_q( &GET_CIQ(ret) );
  init_q( &GET_COQ(ret) );

  return ret;
}

void junk_connection( CONNECTION *c )
{
  MSG_DATA *m;

  while( (m = get_q( &(c->iq) )) ) junk_msg_data( m );
  while( (m = get_q( &(c->oq) )) ) junk_msg_data( m );

  close_connection( GET_CFD(c) );
  GET_CNEXT(c) = garbage_connection_data;
  garbage_connection_data = c;
}

void add_connection( DESCRIPTOR *d, CONNECTION *c )
{
  GET_CNEXT(c) = GET_DCL(d);
  GET_DCL(d) = c;
}

int delete_connection( DESCRIPTOR *d, CONNECTION *c )
{
  CONNECTION *t;

  if( c == GET_DCL(d) ){
    GET_DCL(d) = GET_CNEXT(c);
  } else {
    for( t = GET_DCL(d); GET_CNEXT(t) && GET_CNEXT(t) != c; t = GET_CNEXT(t) )
	;
    if( t == NULL ){
      log( LOG_FATAL, "connection link illegal : %s %d", GET_CNAME(c), GET_CFD(c) );
      return ABNORMAL;
    }
    GET_CNEXT(t) = GET_CNEXT(c);
  }
  junk_connection( c );
  return TRUE;
}

DESCRIPTOR *get_new_descriptor( void )
{
  DESCRIPTOR *ret;

  if( garbage_descriptor_data ){
    ret = garbage_descriptor_data;
    garbage_descriptor_data = ret->next;
  } else {
    CREATE( ret, DESCRIPTOR, 1 );
  }

  memset( ret, 0, sizeof(DESCRIPTOR) );

  return ret;
}

void junk_descriptor( DESCRIPTOR *d )
{
  d->next = garbage_descriptor_data;
  garbage_descriptor_data = d;
}

int find_descriptor_in_list( DESCRIPTOR *d, DESCRIPTOR *dlist )
{
  register DESCRIPTOR *t;

  for( t = dlist; t && t != d; t = GET_DNEXT(t) )
	;

  if( t ) return TRUE;
  else    return FALSE;
}

int insert_descriptor_in_list( DESCRIPTOR *e, DESCRIPTOR **dlist )
{
  if( find_descriptor_in_list( e, *dlist ) == TRUE ) return ABNORMAL;

  GET_DNEXT(e) = *dlist;
  *dlist = e;

  return TRUE;
}

int delete_descriptor_in_list( DESCRIPTOR *e, DESCRIPTOR **dlist )
{
  register DESCRIPTOR *t;

  if( find_descriptor_in_list( e, *dlist ) == FALSE ) return ABNORMAL;

  if( *dlist == e ){
    *dlist = GET_DNEXT(e);
  } else {
    for( t = *dlist; GET_DNEXT(t) && GET_DNEXT(t) != e; t = GET_DNEXT(t) )
	;
    GET_DNEXT(t) = GET_DNEXT(e);
  }

  return TRUE;
}

