#include <sys/types.h>
#include <sys/socket.h>
#include <sys/wait.h>
#include <sys/stat.h>
#include <netinet/in.h>
#include <netdb.h>
#include <sys/time.h>
#include <fcntl.h>
#include <signal.h>
#include <setjmp.h>
#include <unistd.h>
#include <arpa/inet.h>

#ifdef SOLARIS
#include <termio.h>
#endif

#include <arpa/telnet.h>

#include <sys/un.h>

#include "comm_low.h"
#include "message.h"
#include "utils.h"

#define UNIXSOCK_PATH "/tmp/archmage_sock"

int open_mother_connection( char *my_name )
{
  char sock_port[LINE_LENGTH], *tmp;
  int s, count = 0;
  struct sockaddr_un sb;
  struct sockaddr_in sa;
  char *opt;
  struct linger ld;

  if(get_configuration(my_name, sock_port) <= 0){
    log( LOG_FATAL, "get configuration %s failed in open_mother_connection", my_name);
    return ABNORMAL;
  }
  log( LOG_DEBUG, "config %s = %s", my_name, sock_port );

  if( *sock_port == '/' ){
	unlink(sock_port);

	sb.sun_family = AF_UNIX;
	strcpy(sb.sun_path, sock_port);

	s = socket(AF_UNIX, SOCK_STREAM, 0);
	if( s < 0 ){
	  log( LOG_FATAL, "init socket failed : %s", sock_port );
	  return ABNORMAL;
	}
	if( setsockopt(s, SOL_SOCKET, SO_REUSEADDR, (char *)&opt, sizeof(opt)) < 0 ){
	  log( LOG_FATAL, "setsockopt REUSEADDR failed" );
	  return ABNORMAL;
	}
	ld.l_onoff = 0;
	ld.l_linger = 0;
	if( setsockopt(s, SOL_SOCKET, SO_LINGER, (char *)&ld, sizeof(ld)) < 0 ){
	  log( LOG_FATAL, "setsockopt LINGER failed" );
	  return ABNORMAL;
	}
	while(TRUE){
	  if( bind(s, (struct sockaddr *)&sb, sizeof(sb)) < 0 ){
		log( LOG_IGNORE, "Bind again ( %2d )", count);
		if( count++ > 30 ){
		  close( s );
		  return FALSE;
		}
	  } else break;
	  sleep(6);
	}
	chmod(sock_port, 0777);
  } else {
	int port_num;
	char hostname[100];
	struct hostent *hp;

	tmp = sock_port;
	strsep( &tmp, " \t" );
	if( tmp == NULL ){
	  log( LOG_FATAL, "illegal port %s", sock_port );
	  return ABNORMAL;
	}
	port_num = atoi(tmp);
	strcpy( hostname, sock_port );

    memset(&sa, 0, sizeof(struct sockaddr_in));

    // 호스트 이름을 얻어냄�
	hp=gethostbyname(hostname);
	if(hp==NULL) {
	  log( LOG_FATAL, "get host by name fail %s", hostname );
	  return FALSE;
	}

	// 서버 소켓 생성
	sa.sin_family=hp->h_addrtype;
	sa.sin_port=htons(port_num);
	s = socket( AF_INET, SOCK_STREAM, 0 );
	if(s<0) {
	  log( LOG_FATAL, "init socket fail %d", port_num );
	  return FALSE;
	}

	// REUSEADDR 옵션 세팅
	if(setsockopt(s, SOL_SOCKET, SO_REUSEADDR, (char *)&opt, sizeof(opt))<0) {
	  log( LOG_FATAL, "setsockopt : reuseaddr" );
	  return FALSE;
	}

	// LINGER옵션 세팅
	ld.l_onoff=0;
	ld.l_linger=0;
	if(setsockopt(s, SOL_SOCKET, SO_LINGER, (char *)&ld, sizeof(ld))<0) {
	  log( LOG_FATAL, "setsockopt : linger" );
	  return FALSE;
	}

	// 서버 소켓을 바인딩 함
	while(1) {
	  if(bind(s, (struct sockaddr *) &sa, sizeof(sa)) < 0) {
		if(count++ > 10) {
		  close(s);
    	  log( LOG_FATAL, "bind 10 times failed" );
		  return FALSE;
		}
	  } else break;
	  sleep(3);
	}
  }

  nonblock( s );
  listen( s, 100 );
  return s;
}

int open_connection( char *server_name )
{
  char unixsock_path[LINE_LENGTH];
  int s, con_result;
  struct sockaddr_un sockaddr;

  if( get_configuration( server_name, unixsock_path ) <= 0 ){
    log( LOG_FATAL, "get configuration %s failed in open_connection", server_name );
    return ABNORMAL;
  }
  
  if( *unixsock_path == '/' ){
	strcpy(sockaddr.sun_path, unixsock_path);

	s = socket( AF_UNIX, SOCK_STREAM, 0 );
	if( s < 0 ){
	  log( LOG_WARNING, "socket init failed in open_connection : %s", unixsock_path );
	  return ABNORMAL;
	}
	sockaddr.sun_family = AF_UNIX;

  /* how long shall i connect? we can't wait more than 0.3 sec. */
	alarm(1);
	con_result = connect( s, (struct sockaddr *)&sockaddr, sizeof(sockaddr) );
	alarm(0);
	if( con_result ){
	  close( s );
	  switch( errno ){
		case ECONNREFUSED :
		  log( LOG_WARNING, "connect server : connection refused %s", unixsock_path );
		  break;
		case ENETUNREACH :
		  log( LOG_WARNING, "connect server : network is unreachable %s", unixsock_path );
		  break;
		case ETIMEDOUT :
		  log( LOG_WARNING, "connect server : connection timed out %s", unixsock_path );
		  break;
		default :
		  log( LOG_WARNING, "connect server : cannot connect %s", unixsock_path );
	  }
	  return ABNORMAL;
	}
  } else {
	char host[100], *tmp;
	int port_num;
	unsigned long inaddr;
	struct hostent *hp;
	struct sockaddr_in srv;
	
	bzero( &srv, sizeof(srv) );
	srv.sin_family = AF_INET;
	
	tmp = unixsock_path;
	strsep( &tmp, " \t" );
	if( tmp == NULL ){
	  log( LOG_FATAL, "illegal port %s", unixsock_path );
	  return ABNORMAL;
	}
	strcpy( host, unixsock_path );
	port_num = atoi( tmp );

	if( port_num <= 100 ){
	  log( LOG_WARNING, "connect server : port number is too small %s %d", host, port_num );
	  return ABNORMAL;
	}
	srv.sin_port = htons(port_num);
	
	if( (inaddr = inet_addr(host)) != -1 ){
	  bcopy( &inaddr, (char *)&srv.sin_addr, sizeof(inaddr) );
	} else {
	  if( (hp = gethostbyname(host)) == NULL ){
		log( LOG_WARNING, "connect server : host name resolve fail %s", host );
		return ABNORMAL;
      }
	  bcopy( hp->h_addr, (char *)&srv.sin_addr, hp->h_length );
	}
	
	s = socket( AF_INET, SOCK_STREAM, 0 );
	if( s < 0 ){
	  log( LOG_WARNING, "connect server : cannot create TCP socket" );
	  return ABNORMAL;
	}
	
	if( connect( s, (struct sockaddr *)&srv, sizeof(srv) ) < 0 ){
	  log( LOG_WARNING, "connect server : cannot connect to server %s %d", host, port_num );
	  return ABNORMAL;
	}
  }
  nonblock(s);
  return s;
}

void close_connection( int fd )
{
  close( fd );
}

int write_msg( MSG_DATA *md )
{
  int done;
  extern int errno;

  do {
    done = write( GET_MFD(md), GET_MPACKET(md)+GET_MSENT(md), GET_MSIZE(md)-GET_MSENT(md) );
    if( done < 0 ){
      if( errno == EAGAIN ){
        return RETRY;
      } else {
        junk_msg_data( md );
        return ABNORMAL;
      }
    }
    else GET_MSENT(md) += done;
  } while( GET_MSENT(md) < GET_MSIZE(md) );

  junk_msg_data( md );
  return TRUE;
}

MSG_DATA *read_msg( int fd, int *error_code )
{
  unsigned char packet[PACKET_SIZE*2];
  int done, begin, count = 0;
  sh_int size;
  MSG_DATA *res;

  *packet = 0;
  *error_code = 0;

  begin = read( fd, packet, HEADER_SIZE );

/*
  if( begin < 0 ) *error_code = 1; else *error_code = 0;
  if( (begin == 0) && errno && (errno != EWOULDBLOCK) ) *error_code = 1;
*/
  if( begin == 0 ){
    *error_code = 1;
    return NULL;
  }
  if( begin < 0 && errno && (errno != EWOULDBLOCK) ){
//    log( LOG_DEBUG, "read msg %d", begin );
    *error_code = 2;
    return NULL;
  }
  if( begin < 2 ){
//    log( LOG_DEBUG, "read msg %d", begin );
    *error_code = 3;
    return NULL;
  }
  size = ((int) packet[0]) + ((int) packet[1]) * 256;
  
  while( begin < size && count++ < 20 ){
    done = read( fd, packet+begin, size-begin );
    if( done < 0 ) return NULL;
    begin += done;
  }
  res = packet_to_msg( fd, begin, packet );
  return res;
}

int write_just( int fd, int size, char *msg)
{
  int begin = 0, done;

  do {
    done = write( fd, msg+begin, size-begin );
    if( done < 0 )   return ABNORMAL;
    else             begin += done;
  } while( begin < size );

  return TRUE;
}

int read_just( int fd, int max, char *msg )
{
  return read( fd, msg, max );
}
