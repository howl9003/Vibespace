CC=g++
CFLAGS=-g -Wall
LIBDIRS=-L/usr/local/lib
LIBS=-static -lstdc++
INCLUDES=-I/usr/include/g++ -I/usr/local/include -I/space/mysql/include/mysql -I../include
DEFINES=-DDEBUG

VPATH=$(HOME)src/libs/include

HEADERFILES=
CCFILES=socket.cc server.cc message.cc packet.cc fifo.cc client.cc\
		server.loop.cc iplist.cc

CCOBJS=$(CCFILES:.cc=.o)

.cc.o:
	$(CC) -c $< $(CFLAGS) $(INCLUDES) $(DEFINES)

lib:$(CCOBJS)


clean:
	rm -f *.o 

test: $(CCOBJS) test.cc
	$(CC) -c test.cc $(CFLAGS) $(INCLUDES) $(DEFINES)
	$(CC) -o test test.o $(LIBS) $(LIBDIRS) 
