#ifndef BBS_COMM_HIGH_H
#define BBS_COMM_HIGH_H

#include "include.h"
#include "comm_low.h"
#include "comm_mid.h"
#include "descriptor.h"
#include "message.h"

int process_connection_input( CONNECTION *c );
int process_connection_output( CONNECTION *c );
int process_mother_connection_input( DESCRIPTOR *d );
int process_mother_connection_output( DESCRIPTOR *d );

int process_connection_input_just( CONNECTION *c );
int process_connection_output_just( CONNECTION *c );
int process_mother_connection_input_just( DESCRIPTOR *d );
int process_mother_connection_output_just( DESCRIPTOR *d );

#endif

