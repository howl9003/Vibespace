CC = gcc
#CFLAGS = -I/space/mysql/include/mysql -I../lib -Wall -O6 -mcpu=pentiumpro
#CFLAGS = -g -I/usr/local/include/mysql -I../lib -Wall
CFLAGS = -I/space/mysql/include/mysql -I/space/bsdport/include -I../lib -Wall -O2
#LIBS = -L/space/mysql/lib/mysql =L/space/bsdport/lib -lc -L../lib -ldbbs -lmysqlclient -lm -lkvm -lcrypt -static -lcipher
#LIBS = -L/space/mysql/lib/mysql =L/space/bsdport/lib -lc -L../lib -ldbbs -lmysqlclient -lm -lkvm -lcrypt -static -lbsdport

CFILES = runtime.c cgi.c utility.c server.c mysqllib.c crypt.c

OTHERS = Makefile aaa.cgi

COBJS = $(CFILES:.c=.o)
.c.o:    ;    $(CC) $(CFLAGS) -c $*.c

.SUFFIXES: .c .o

all : elib

elib : $(COBJS)
	ar r libelib.a $(COBJS)
	ranlib libelib.a

server : $(COBJS) server.c
	$(CC) -o server server.c $(COBJS) $(CFLAGS) $(LIBS)

client : $(COBJS) client.c
	$(CC) -o client client.c $(COBJS) $(CFLAGS) $(LIBS)


clean :
	rm -f *.o tags
	ctags *.c *.h

tags : 
	ctags *.c *.h

