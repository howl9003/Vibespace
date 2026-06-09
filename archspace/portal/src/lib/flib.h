#ifndef BBS_FLIB_H
#define BBS_FLIB_H

#include <stdio.h>

/****************************************************************************

  file I/O library.
  this library will be used mainly on the configuration file handling.

  each function has integer return value that indicates success or failure.
  return value TRUE(1) means success, FALSE(0) means failure, 
  ABNORMAL(-1) means abnormal termination like eof.

****************************************************************************/

/* file에서 endofline까지 최대 maxbyte를 읽어 line에 저장 */
int get_lines( FILE *fp, char *line, char endofline, int maxbyte );

/* line을 seperator를 중심으로 head와 tail에 나누어 저장한다.  */
int split_line( char *line, char *head, char *tail, char seperator );

/* till이 나올때까지 fp를 뒤로 옮긴다.  */
int backward_till( FILE *fp, char till );

/* till이 나올때까지 fp를 앞으로 옮긴다. */
int forward_till( FILE *fp, char till );

/* endofline까지 한 line을 뛰어넘는다.  */
int skip_line( FILE *fp, char endofline );

/* find_this로 시작하는 line을 찾아 result_line에 저장한다. */
int find_line( FILE *fp, char *find_this, char *result_line, char seperator );
int find_line_php( FILE *fp, char *find_this, char *result_line );

// #define CONFIG_FILE "archmage_config"

#define CONFIG_READ   1
#define CONFIG_CREATE 2
#define CONFIG_APPEND 4

int set_config_file( char *filename );

/* mode에 따라 config file을 open합니다. */
FILE *open_config_file( int mode );

/* config file을 닫습니다. */
void close_config_file( FILE *fp );

/* config file에서 원하는 configuration을 찾아옵니다. */
int get_configuration( char *config_name, char *config_result );

/* file에서 ch로 시작하는 line 갯수를 찾아 리턴한다. */
int count_char( FILE *fp, char ch );

#endif
