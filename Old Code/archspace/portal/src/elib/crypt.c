#include <bsdport.h>
#include <unistd.h>
#include "server.h"
#include "crypt.h"
#include "utility.h"
#include <string.h>

#define DES_SALT  4096
#define DES_COUNT 8

void my_encrypt( char *key, char *src, char *dest )
{
  int len, i;
  unsigned char tmp[BUF_LEN];

  len = strlen(src)+1;
  mydes_setkey( key );

  for( i = 0; i < len; i += 8 ){
    mydes_cipher( src+i, tmp+i, DES_SALT, DES_COUNT );
  }
  len = i;

  for( i = 0; i < len; i++ ){
    dest[2*i] = tmp[i]/16+'A';
    dest[2*i+1] = tmp[i]%16+'A';
  }
  dest[2*i] = 0;
/*
  int len, i;

  len = strlen(src)+1;
  des_setkey( key );

  for( i = 0; i < len; i += 8 ){
    des_cipher( src+i, dest+i, DES_SALT, DES_COUNT );
  }
  dest[i] = 0;
*/
}

void my_decrypt( char *key, char *src, char *dest )
{
  int len, i;  
  unsigned char tmp[BUF_LEN];
  
  len = strlen(src);
  mydes_setkey( key );
   
  for( i = 0; src[i]; i += 2 ){
    tmp[i/2] = (src[i]-'A')*16+(src[i+1]-'A');
  }
  tmp[i/2] = 0;

  len /= 2;
  for( i = 0; i < len; i += 8 ){
    mydes_cipher( tmp+i, dest+i, DES_SALT, -DES_COUNT );
  }
  dest[i] = 0;
/*
  int len, i;

  len = strlen(src);
  des_setkey( key );

  for( i = 0; i < len; i += 8 ){
    des_cipher( src+i, dest+i, DES_SALT, -DES_COUNT );
  }
  dest[i] = 0;
*/
}

int decrypt_success( char *str )
{
  if( strncmp( str, "ID=", 3 ) == 0 || strstr( str, "&ID=" ) ) return 1;
  return 0;
/*
  while( *str ){
    if( my_isalnum( *str ) == 0 && *str != '-' && *str != '_' && *str != '.' && *str != '+' && *str != '&' && *str != '%' ) return 0;
    str++;
  }

  return 1;
*/
}

int count_bit( char *str )
{
  int c = 0, i;
  unsigned char l;

  while( *str ){
    for( l = 1, i = 0; i < 8; l <<= 1, i++ ){
      if( BIT_CHECK( *str, l ) ) c++;
    }
    str++;
  }

  return c;
}


