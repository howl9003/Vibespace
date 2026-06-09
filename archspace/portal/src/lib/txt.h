#ifndef BBS_TXT_H
#define BBS_TXT_H

#include "define.h"
#include "include.h"

typedef struct txt_block TXT_BLOCK;

struct txt_block {
  int size;
  char text[LINE_LENGTH+1];
  TXT_BLOCK *next;
};

#define GET_TSIZE(t)     ((t)->size)
#define GET_TTEXT(t)     ((t)->text)
#define GET_TNEXT(t)     ((t)->next)

TXT_BLOCK *get_new_txt();
TXT_BLOCK *str_to_txt( char *str );
TXT_BLOCK *str_to_txt_list( char *str );
void junk_txt( TXT_BLOCK *t );
void junk_txt_list( TXT_BLOCK *t );

#endif
