CC=g++
CFLAGS=-g -Wall
LIBDIRS=-L/usr/local/lib
LIBS=-static -lstdc++ 
INCLUDES=-I/usr/include/g++ -I/usr/local/include -I/space/mysql/include/mysql -I../include
DEFINES=-DDEBUG

VPATH=$(HOME)src/libs/include

HEADERFILES=
CCFILES= application.cc

CCOBJS=$(CCFILES:.cc=.o)

.cc.o:
	$(CC) -c $< $(CFLAGS) $(INCLUDES) $(DEFINES)

lib:$(CCOBJS)


clean:
	rm -f *.o 
