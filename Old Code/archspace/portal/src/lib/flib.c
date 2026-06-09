/****************************************************************************

  file I/O library.
  this library will be used mainly on the configuration file handling.

  each function has integer return value that indicates success or failure.
  return value TRUE(1) means success, FALSE(0) means failure, 
  ABNORMAL(-1) means abnormal termination like eof.

****************************************************************************/

#include "include.h"
#include "flib.h"
#include "define.h"
#include "utils.h"

#include <unistd.h>

/*******************

  return value : when eof found, return -1;
                 on normal termination, return read_byte;
  normal termination means that endofline is found or maxbyte is read

                                                  **********************/
int get_lines( FILE *fp, char *line, char endofline, int maxbyte )
{
  int ch, i = 0;

  ch = getc( fp );
  while( ch != endofline && i < maxbyte ){
    if( ch == EOF ){
      *line = 0;
      return ABNORMAL;
    }
    *line++ = ch;
    i++;
    ch = getc( fp );
  }
  *line = 0;

  return i;
}

int split_line( char *line, char *head, char *tail, char seperator )
{
  while( *line == seperator ) line++;

  for( ; *line && *line != seperator; )
    *head++ = *line++;

  *head = 0;

  if( *line ){
    while( *line == seperator ) line++;
    strcpy( tail, line );
    return TRUE;  /* split successful */
  } else
    return FALSE; /* split failed */
}

int backward_till( FILE *fp, char till )
{
  long where = -1, pivot, i;
  int ch;

  pivot = ftell( fp );
  if( pivot == -1 ) return ABNORMAL;

  rewind( fp );  
  for( i = 0; i < pivot; i++ ){
    ch = getc( fp );
    if( ch == till ) where = ftell( fp );
  }

  fseek( fp, pivot, SEEK_SET );

  if( where == -1 ) return FALSE;
  return TRUE;
}
 
int skip_line( FILE *fp, char endofline )
{
  int ch;

  do {
    ch = getc(fp);
    if( ch == EOF ) return ABNORMAL;
  } while( ch != endofline );
  return TRUE;
}

int find_line( FILE *fp, char *find_this, char *result_line, char seperator )
{
  int len;
  char buf[BUF_LENGTH], dummy[LINE_LENGTH];

  len = strlen( find_this );

  do {
    get_lines( fp, buf, '\n', BUF_LENGTH-1 );
  } while( feof(fp) == 0 && !( strncmp( buf, find_this, len ) == 0 && buf[len] == seperator ) );
 
  if( strncmp( buf, find_this, len ) == 0 )
    return split_line( buf, dummy, result_line, seperator );
  else
    return FALSE;
}

// There's no separator. - Abe
//
// Can have spaces, and comments, blah.

int find_line_php( FILE *fp, char *find_this, char *result_line)
{
  int flag = 0, ch, len, tlen;
  char buf[BUF_LENGTH], *t;

  len = strlen(find_this)-1;
  while((ch = fgetc(fp)) != EOF && flag == 0) {
    if(ch == '#') {			// Comment begin with '#'
  	  fgets(buf, BUF_LENGTH, fp);
	  continue;
    }
    if(ch == ' ' || ch == '\t' || ch == '\n' || ch == ';')
	  continue;			// White space. and semicolon
    if(ch == '$') {
      fscanf(fp, "%s", buf);
      if(strcmp(find_this, buf)) {	// if NOT equal..
        fgets(buf, BUF_LENGTH, fp);	// Truncate
        continue;			// and continue.
      }
// scanf sucks! scanf always stops whenever it meets ' '
//	  fscanf(fp, " = %s", buf);	// We got a winner. matched.
      fgets( buf, BUF_LENGTH, fp );
      t = buf;
      while( *t == ' ' || *t == '\t' || *t == '=' || *t == '\"' ) t++;

      tlen = strlen(buf)-1;
      if( buf[tlen] == '\n' || buf[tlen] == '\r' ){
        buf[tlen] = 0;
        tlen--;
      }
      if( buf[tlen] == ';' ){
        buf[tlen] = 0;
        tlen--;
      }
      if( buf[tlen] == '\"' ) buf[tlen] = 0;
      
      strcpy(result_line, t);
	  return TRUE;
    } else {
      ungetc(ch, fp);
	  fgets( buf, BUF_LENGTH, fp );
	  if( strncmp( buf, find_this, len ) == 0 ){
		t = buf;
		strsep( &t, " \t" );
		if( t ){
		  strcpy( result_line, t );
		  return TRUE;
		}
      }
	}
  }
  return FALSE;
}

static char config_file_name[100] = "archmage_config";

int set_config_file( char *filename )
{
  if( access( filename, F_OK ) == -1 ) return 0;
  strcpy( config_file_name, filename );
  return 1;
}

FILE *open_config_file( int mode )
{
  FILE *fp;

  switch( mode ){
    case CONFIG_READ   :
      fp = fopen( config_file_name, "r" );
      break;
    case CONFIG_CREATE :
      fp = fopen( config_file_name, "w+" );
      break;
    case CONFIG_APPEND :
      fp = fopen( config_file_name, "a+" );
      break;
    default            :
      log(LOG_WARNING, "config file %s open mode illegal : %d", config_file_name, mode );
      return NULL;
  }
  if( fp == NULL )
    log( LOG_WARNING, "config file %s open failed at mode %d", config_file_name, mode );
  return fp;
}

void close_config_file( FILE *fp )
{
  fclose( fp );
}

int get_configuration( char *config_name, char *config_result )
{
  FILE *fp;
  int res;

  fp = open_config_file( CONFIG_READ );
  if( fp == NULL ){
    *config_result = 0;
    return ABNORMAL;
  }
  res = find_line_php( fp, config_name, config_result);
  close_config_file( fp );

//  log( LOG_DEBUG, "get configuration %s = %s", config_name, config_result );

  return res;
}

int count_char( FILE *fp, char ch )
{
  int i = 0;
  char buf[BUF_LENGTH];
  
  do {
    get_lines( fp, buf, '\n', BUF_LENGTH-1 );
    if( buf[0] == ch ) i++;
  } while( feof(fp) == 0 );

  return i;
}
