/***************

  communication high layer 

  this layer is communication high layer. this layer handles connection and
  descriptor i/o.

  these functions are not complete. there is missing error handling routines.
  how to communicate with other servers with error and how to let user and
  descriptor know when error on connection level occured are not yet imple-
  mented. any good idea will be welcomed.

                                             ***********************/


/*********************

  process all descriptor & connection pseudo code

process_all_descriptor_and_connection_io
{
  for each descriptor {

    if this descriptor is in input_set
      if( process_mother_connection_input( this descriptor ) == ABNORMAL )
        do my descriptor destructor

    if this descriptor is in output_set
      if( process_mother_connection_output( this descriptor ) == ABNORMAL )
        do my descriptor destructor

    for each connection in this descriptor

      if this connection is in input_set
        if( process_connection_input( this connection ) == ABNORMAL )
          do my connection destructor

      if this connection is in output_set
        if( process_connection_output( this connection ) == ABNORMAL )
          do my connection destructor

  }
}

ATTENTION :
my descriptor destructor should contain library descriptor destructor that
does the minimal handling of destruct.
my connection destructor is same also.

                                              ************************/

#include "comm_high.h"
#include "memory.h"
#include "utils.h"


int process_connection_input( CONNECTION *c )
{
  MSG_DATA *m;
  int error_code;

  while( (m = read_msg( GET_CFD(c), &error_code )) ) {
    put_q( &GET_CIQ(c), m );
  }
  if( error_code ) return ABNORMAL;
  return TRUE;
}

int process_connection_output( CONNECTION *c )
{
  MSG_DATA *m;

  while( (m = get_q( &GET_COQ(c) )) ){
    if( GET_MTYPE(m) == MSG_DISCONNECT || write_msg(m) == ABNORMAL )
      return ABNORMAL;
  }
  return TRUE;
}

int process_mother_connection_input( DESCRIPTOR *d )
{
  MSG_DATA *m;
  int error_code;

/*
  while( (m = read_msg( GET_DMFD(d), &error_code )) ){
    put_q( &GET_DMIQ(d), m );
    if( error_code ) return ABNORMAL;
  } */
  if( (m = read_msg( GET_DMFD(d), &error_code )) ){
    put_q( &GET_DMIQ(d), m );
    if( error_code ) return ABNORMAL;
  }
  if( error_code ) return ABNORMAL;
  return TRUE;
}

int process_mother_connection_output( DESCRIPTOR *d )
{
  MSG_DATA *m;
  extern int errno;
  int r;

  if( (m = get_q( &GET_DMOQ(d) )) ){
    if( GET_MTYPE(m) == MSG_DISCONNECT ){
      junk_msg_data(m);
      return ABNORMAL;
    }
    r = write_msg(m);
    switch( r ){
      case RETRY :
        unget_q( &GET_DMOQ(d), m );
        break;
      case ABNORMAL :
        log( LOG_DEBUG, "mother connection output err %d", errno );
        return ABNORMAL;
    }
  }
  return TRUE;
}

int process_connection_input_just( CONNECTION *c )
{
  MSG_DATA *m;
  char msg[MSG_SIZE];
  int size;

  while( (size = read_just( GET_CFD(c), MSG_SIZE, msg )) > 0 ){
    m = new_msg_data( GET_CFD(c), size, 0, 0, msg );
    put_q( &GET_CIQ(c), m );
  }

  if( size < 0 && errno != EWOULDBLOCK ) return ABNORMAL;
  return TRUE;
}

int process_connection_output_just( CONNECTION *c )
{
  MSG_DATA *m;

  while( (m = get_q( &GET_COQ(c) )) ){
    if( GET_MTYPE(m) == MSG_DISCONNECT || write_just( GET_MFD(m), GET_MMSIZE(m), GET_MMSG(m) ) == ABNORMAL )
      return ABNORMAL;
  }
  return TRUE;
}

int process_mother_connection_input_just( DESCRIPTOR *d )
{
  MSG_DATA *m;
  char msg[MSG_SIZE];
  int size;

  while( (size = read_just( GET_DMFD(d), MSG_SIZE, msg )) > 0 ){
    m = new_msg_data( GET_DMFD(d), size, 0, 0, msg );
    put_q( &GET_DMIQ(d), m );
  }

  if( size == 0 ) return ABNORMAL;
  if( size < 0 && errno != EWOULDBLOCK ) return ABNORMAL;
  return TRUE;
}

int process_mother_connection_output_just( DESCRIPTOR *d )
{
  MSG_DATA *m;

  while( (m = get_q( &GET_DMOQ(d) )) ){
    if( GET_MTYPE(m) == MSG_DISCONNECT || write_just( GET_MFD(m), GET_MMSIZE(m), GET_MMSG(m) ) == ABNORMAL ){
      junk_msg_data(m);
      return ABNORMAL;
    }
    junk_msg_data(m);
  }
  return TRUE;
}



