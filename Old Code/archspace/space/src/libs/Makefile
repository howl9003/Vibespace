CC=g++
CFLAGS=-g -Wall 
INCLUDES=-I/usr/local/include/g++ -I/usr/local/include -I../include
SUBDIRS=util net frame cgi runtime database key
MAKE=make
DOC=/usr/local/bin/doc++

DOCPREFIX=$(HOME)/src/libs/include
DOCPREFIX2=$(HOME)/src/apps/archspace
DOCFILES= $(DOCPREFIX)/common.h \
		$(DOCPREFIX)/util.h \
		$(DOCPREFIX)/net.h \
		$(DOCPREFIX)/frame.h \
		$(DOCPREFIX)/message.h \
		$(DOCPREFIX)/debug.h \
		$(DOCPREFIX)/define.h \
		$(DOCPREFIX)/types.h \
		$(DOCPREFIX2)/main.h \
		$(DOCPREFIX2)/cgi.h
DOCTARGETDIR=/space/www/docs

CCFILES=util/debug.cc util/string.cc util/list.cc \
		util/list.sort.cc util/node.cc util/collection.cc \
		util/list.string.cc util/etc.cc util/inifile.cc \
		util/mysql.cc util/commandset.cc util/script.cc \
		util/lex.cc util/list.integer.cc util/list.integer.unsigned.cc \
		util/math.cc util/memory.cc \
		net/server.cc net/socket.cc net/message.cc net/packet.cc \
		net/fifo.cc net/client.cc net/server.loop.cc net/iplist.cc \
		cgi/cgiserver.cc cgi/connection.cc cgi/html.cc cgi/htmls.cc \
		cgi/pages.cc cgi/page.cc cgi/query.cc cgi/query.list.cc \
		cgi/filehtml.cc \
		frame/application.cc \
		runtime/trigger.cc runtime/triggers.cc \
		database/database.cc database/sql.cc database/store.cc \
		database/store.ct.cc \
		key/key.cc key/crypt.conn.cc 


CCOBJS=$(CCFILES:.cc=.o)

all: subdirs libarchspace.a

libarchspace.a: $(CCFILES) 
	ar r libarchspace.a $(CCOBJS)
	ranlib libarchspace.a

subdirs:
	@for i in $(SUBDIRS); do \
		echo "--> $(SDP)$$i"; \
		(	cd $$i && $(MAKE) ) || exit 1; \
		echo "<-- $(SDP)$$i"; \
	done

clean:
	rm -f libarchspace.a *.o
	@for i in $(SUBDIRS); do \
		echo "--> $(SDP)$$i"; \
		(	cd $$i && $(MAKE) clean ) || exit 1; \
		echo "<-- $(SDP)$$i"; \
	done

doc:$(DOCFILES)
	$(DOC) -H -a -G -f -v -d $(DOCTARGETDIR) $(DOCFILES) 
	chmod -R g+w $(DOCTARGETDIR)/* 

