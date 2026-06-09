#define BSD_COMP
#include "httpd.h"
#include "http_config.h"
#include "http_core.h"
#include "http_log.h"
#include "http_protocol.h"
#include <unistd.h>
#include <stdlib.h>
#include <stdio.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <sys/signal.h>
#include <stdarg.h>
#include <errno.h>
#include <time.h>

#include "as.h"

extern module MODULE_VAR_EXPORT as_module;

// extern void Dump( request_rec* aRequest, unsigned char *data, int len );

static int
	Counter = 0;

void 
log(request_rec* aRequest, const char *aFormat, ...)
{
	va_list 
		Args;
	long 
		Current;
	char 
		*TimeString;

	va_start(Args, aFormat);
	Current = time(0);
	TimeString = (char*) asctime(localtime(&Current));

	*(TimeString + strlen(TimeString) - 6) = '\0';
	fprintf(aRequest->server->error_log, "%s :: ", TimeString+4);
	vfprintf(aRequest->server->error_log, aFormat, Args);
	fprintf(aRequest->server->error_log, "\n");
	va_end(Args);

	fflush(aRequest->server->error_log);
}

int 
nonblock(int aSocket)
{
	if (fcntl(aSocket, F_SETFL, O_NDELAY) == -1)
		return -1;

	return 0;
}

int 
make_connection(char *aServer, int aPort, request_rec *aRequest)
{
	int 
		Socket = -1;
	
	struct sockaddr_in
		ServerAddress;

	struct in_addr
		Address;

	struct hostent
		*HostEntry = 0;

	int 
		Result;

//	log(aRequest, "try to connect %s:%d", aServer, aPort);

	if ((Address.s_addr = inet_addr(aServer)) == -1)
	{
		if (*aServer == '\0')
			HostEntry = gethostbyname("localhost");
		else 
			HostEntry = gethostbyname(aServer);

		memcpy(&Address.s_addr, HostEntry->h_addr, HostEntry->h_length);
	}

	memset((char*)&ServerAddress, 0, sizeof(ServerAddress));
	ServerAddress.sin_family = AF_INET;
	ServerAddress.sin_addr.s_addr = Address.s_addr;
	ServerAddress.sin_port = htons(aPort);

	ap_hard_timeout("make_connection", aRequest);
	ap_reset_timeout(aRequest);

	if ((Socket = socket(AF_INET, SOCK_STREAM, 0)) < 0)
	{
		log(aRequest, "Could not create socket");
	} else if ((Result = connect(Socket, 
				(struct sockaddr*)&ServerAddress,
				sizeof(ServerAddress))) < 0)
	{
		close(Socket);
		Socket = -1;

		log(aRequest, "Could not connect this server(%s:%d)", 
				aServer, aPort);
	} else {
		if (nonblock(Socket)) 
		{
			log(aRequest, "Could not set socket to nonblock mode.");
			Socket = -1;
		}
	}

	ap_kill_timeout(aRequest);
//	log(aRequest, "Done.");

	return Socket;
}


int 
send_packet(int aSocket, PSPacket aPacket)
{
	int 
		Done;

	do 
	{
		Done = write(aSocket,
			(char*)aPacket+aPacket->sent,
			aPacket->size-aPacket->sent);
		if (Done < 0)
		{
			if (errno == EAGAIN)
				return 1;
			else 
				return -1;
		} else
			aPacket->sent += Done;
	} while(aPacket->sent < aPacket->size);

	return 0;
}

PSPacket
receive_packet(int aSocket, request_rec *aRequest)
{
	int 
		Done = 0,
		Begin = 0,
		Count = 0;

	unsigned short int
		Size;

	static unsigned char
		Buffer[MAX_PACKET_SIZE];

	PSPacket 
		Packet;

	Packet = ap_pcalloc(aRequest->pool, sizeof(SPacket));
	if (!Packet) 
	{
		log(aRequest, "Could not allocate memory");	
		return NULL;
	}

	Begin = read(aSocket, Buffer, sizeof(unsigned short int));

	if (Begin < (signed)sizeof(unsigned short int)) 
	{
		log(aRequest, "Could not get begin size");
		return NULL;
	}

	Size = ((int) Buffer[0]) + ((int) Buffer[1]) * 256;

	if (Size > MAX_PACKET_SIZE) 
	{
		log(aRequest, "Packet size over MAX_PACKET_SIZE");	
		return NULL;
	}

//	log( aRequest, "Reading size: %d", Size );
	while(Begin < Size && Count++ < 20)
	{
		Done = read(aSocket, Buffer+Begin, Size-Begin);
//		log(aRequest, "Reading Begin:%d, Size:%d, Done: %d", Begin, Size, Done);
		if (Done < 0) 
		{
//			log(aRequest, "Reading error %d[%d, %d], %d.%s", 
//					Done, 
//					Begin, Size, 
//					errno, strerror(errno));
			if (errno != EAGAIN) return NULL;
			Done = 0;
			usleep(1000);
		}
		Begin += Done;
	}

//	Dump( aRequest, Buffer, Begin );
	memcpy(Packet, Buffer, Begin);

	Packet->size = Size;
	Packet->sent = 0;
	Packet->read = 0;
	Packet->next = NULL;

	return Packet;	
}

PSPacket
make_packet(request_rec *aRequest, int aType)
{
	static unsigned short int 
		ServerID = 0;

	PSPacket
		Packet;

	SArchspaceConfig
		*Config;

	if (ServerID == 0)
	{
		Config = (SArchspaceConfig*)
				ap_get_module_config(aRequest->server->module_config, 
						&as_module);

		ServerID = 0x0400+Config->server_serial;
	}

	Packet = (PSPacket)ap_pcalloc(aRequest->pool, sizeof(SPacket));
	if (!Packet) 
	{
		log(aRequest, "Could not allocate memory for Packet");
		return NULL;
	}

	Packet->header.header_struct.type = (unsigned short int)aType;
	Packet->header.header_struct.server = ServerID;
	Packet->header.header_struct.counter = Counter++;
	Packet->size = MESSAGE_HEADER_SIZE;
	Packet->header.header_byte[0] = MESSAGE_HEADER_SIZE % 256;
	Packet->header.header_byte[1] = MESSAGE_HEADER_SIZE / 256;

	Packet->sent = 0;
	Packet->next = NULL;

//	log(aRequest, "make_packet type %x, server id %d, counter %d",
//			aType, ServerID, Counter-1);

	return Packet;
}

int 
set_item_to_packet(PSPacket aPacket, int aType, 
		void *aData, int aDataSize)
{
	int
		Length;
	unsigned char
		ItemHeader;
	unsigned char 
		ItemSize[2];
	int 
		ByteOfLength = 0;

	if (aPacket->size < MESSAGE_HEADER_SIZE) 
		return -1;

	if (aDataSize <= 0) return -1;

	if (aDataSize < 256)
	{
		ByteOfLength = 1;
		ItemSize[0] = (unsigned char)aDataSize;
	} else if (aDataSize < MAX_MESSAGE_DATA_SIZE) {
		ByteOfLength = 2;
		ItemSize[0] = aDataSize%256;
		ItemSize[1] = aDataSize/256;
	} else return -1;	
	
	if (aPacket->size + sizeof(ItemHeader)
			+ ByteOfLength + aDataSize > MAX_MESSAGE_DATA_SIZE)
		return -1;

	ItemHeader = (unsigned char)((aType & 0x3F) << 2) + ByteOfLength;

	if (aType == MESSAGE_ITEM_LIST)
	{
		memcpy(aPacket->data+aPacket->size-
				MESSAGE_HEADER_SIZE, 
				&ItemHeader, sizeof(ItemHeader));
		memcpy(aPacket->data+aPacket->size+
				sizeof(ItemHeader) - MESSAGE_HEADER_SIZE, 
				ItemSize, ByteOfLength);

		aPacket->size += sizeof(ItemHeader) + ByteOfLength;
		aPacket->header.header_byte[0] = aPacket->size % 256;
		aPacket->header.header_byte[1] = aPacket->size / 256;

		return 0;
	} 

	if (!aData) return -1;

	memcpy(aPacket->data+aPacket->size-
			MESSAGE_HEADER_SIZE, 
			&ItemHeader, sizeof(ItemHeader));
	memcpy(aPacket->data+aPacket->size+
			sizeof(ItemHeader) - MESSAGE_HEADER_SIZE, 
			ItemSize, ByteOfLength);
	memcpy(aPacket->data+aPacket->size+
			sizeof(ItemHeader) + ByteOfLength - MESSAGE_HEADER_SIZE, 
			aData, aDataSize);
	
	aPacket->size += sizeof(ItemHeader) + ByteOfLength + aDataSize;
	aPacket->header.header_byte[0] = aPacket->size % 256;
	aPacket->header.header_byte[1] = aPacket->size / 256;

	return 0;
}

int 
get_item_from_packet(PSPacket aPacket, 
		int *aType, void **aData, int *aDataSize)
{
	unsigned char 
		Type;
	unsigned char
		ByteOfLength;
	short int
		Count;

	*aType = -1;
	*aData = NULL;
	*aDataSize = 0;

	if (aPacket->read >= aPacket->size
			-MESSAGE_HEADER_SIZE) return -1;

	Type = aPacket->data[aPacket->read] >> 2;
	ByteOfLength = aPacket->data[aPacket->read] & 0x03;

	switch(ByteOfLength)
	{
		case 0:
			return -1;
		case 1:
			Count = aPacket->data[aPacket->read+1];
			break;
		case 2:
			Count = aPacket->data[aPacket->read+1] + 
					+aPacket->data[aPacket->read+2] * 256;
			break;
		default:
			return -1;
	}

	*aType = Type;
	*aDataSize = Count;

	if (Type == MESSAGE_ITEM_LIST)
	{
		aPacket->read += sizeof(Type)+ByteOfLength;
		*aData = NULL;
	} else {
		*aData = (void*)&aPacket->data[aPacket->read
				+sizeof(Type)+ByteOfLength];
		aPacket->read += sizeof(Type)+ByteOfLength+Count;
	}

	return 0;
}

PSPacket
make_string_packet(request_rec *aRequest, int aType, char *aString)
{
	int 
		Done = 0,
		Size,
		Length;

	PSPacket 
		Return = NULL,
		Temp,
		Packet;
	
	unsigned char 
		Count = 0;

	if (!aString)
		return NULL;

	Length = strlen(aString);	

	if (!Length) 
		return NULL;

	while(Done < Length)
	{
		Size = ((Length - Done) > STRING_DATA_BLOCK) ? 
				STRING_DATA_BLOCK : Length - Done;

		Packet = make_packet(aRequest, aType);

		if (set_item_to_packet(Packet, MESSAGE_ITEM_UINT1, &Count, 
				sizeof(unsigned char)))
			return NULL;
		if (set_item_to_packet(Packet, MESSAGE_ITEM_ASCII, 
				(char*)aString+Done, Size))
			return NULL;

		Done += Size;
		Count++;

		if (!Return)
			Return = Packet;
		else {
			Temp = Return;

			while(Temp->next) 
				Temp = Temp->next;
			Temp->next = Packet;
		}
	}
	return Return;
}

PSPacket
make_url_send(request_rec *aRequest)
{
	PSPacket
		Packet;

	char 
		*URL,
		*Ext;
	int 
		Len;

	URL = ap_pstrdup(aRequest->pool, aRequest->uri);

/*	Len = strlen(URL);
	if (Len > 7)
	{
		Ext = (char*)&URL[Len-6];
		if (!strcmp(Ext, ".entry"))
		{
			Ext[1] = 'a';
			Ext[2] = 's';
			Ext[3] = '\0';
		}
	}*/

//	log(aRequest, "in make_url_send() URI:%s", URL);

	return make_string_packet(aRequest, MT_URL_SEND, URL);
}

PSPacket
make_method_send(request_rec *aRequest)
{
	char
		*Method;

	Method = (char *)aRequest->method;

//	log(aRequest, "make_method_send %s", Method);

	return make_string_packet(aRequest, MT_METHOD_SEND, Method);
}


PSPacket
make_referer_send(request_rec *aRequest)
{
	char
		*Referer;

	Referer = (char*)ap_table_get(aRequest->headers_in, "Referer");

	if (!Referer)
	{
//		log(aRequest, "Referer is NULL");
		return NULL;
	}

//	log(aRequest, "make_referer_send %s", Referer);

	return make_string_packet(aRequest, MT_REFERER_SEND, Referer);
}


PSPacket
make_cookie_send(request_rec *aRequest)
{
	char
		*Cookie;

	Cookie = (char*)ap_table_get(aRequest->headers_in, "Cookie");

	if (!Cookie)
	{
//		log(aRequest, "Cookie is NULL");
		return NULL;
	}

//	log(aRequest, "make_cookie_send %s", Cookie);

	return make_string_packet(aRequest, MT_COOKIE_SEND, Cookie);
}


PSPacket
make_encoding_send(request_rec *aRequest)
{
	char
		*Encoding;

	Encoding = (char*)ap_table_get(aRequest->headers_in, 
			"Accept-encoding");

	if (!Encoding)
	{
//		log(aRequest, "Encoding is NULL");
		return NULL;
	}

//	log(aRequest, "make_encoding__send %s", Encoding);

	return make_string_packet(aRequest, MT_ACCEPT_ENCODING_SEND, 
			Encoding);
}

PSPacket
make_language_send(request_rec *aRequest)
{
	char
		*Language;

	Language = (char*)ap_table_get(aRequest->headers_in, 
			"Accept-language");

	if (!Language)
	{
//		log(aRequest, "Language is NULL");
		return NULL;
	}

//	log(aRequest, "make_language_send %s", Language);

	return make_string_packet(aRequest, MT_ACCEPT_LANGUAGE_SEND, 
			Language);
}


PSPacket
make_agent_send(request_rec *aRequest)
{
	char
		*Agent;

	Agent = (char*)ap_table_get(aRequest->headers_in, 
			"User-agent");

	if (!Agent)
	{
//		log(aRequest, "Agent is NULL");
		return NULL;
	}

//	log(aRequest, "make_agent_send %s", Agent);

	return make_string_packet(aRequest, MT_USER_AGENT_SEND, Agent);;
}

PSPacket
make_host_name_send(request_rec *aRequest)
{
	char *
		HostName = (char *)aRequest->hostname;

	if (HostName != NULL)
	{
		return make_string_packet(aRequest, MT_HOST_NAME_SEND, HostName);
	}
	else
	{
		return NULL;
	}
}


PSPacket
make_connection_send(request_rec *aRequest)
{
	char
		*Connection;

	Connection = (char*)aRequest->connection->remote_ip;

	if (!Connection)
	{
//		log(aRequest, "User IP address is NULL");
		return NULL;
	}

//	log(aRequest, "make_connection_send %s", Connection);

	return make_string_packet(aRequest, MT_CONNECTION_INFO_SEND, 
			Connection);;
}

PSPacket
make_query_send(request_rec *aRequest, char *aData)
{
//	log(aRequest, "make_post_send %s", aData);

	return make_string_packet(aRequest, MT_QUERY_SEND, aData);
}

PSPacket
make_getpage_request(request_rec *aRequest)
{
	return make_packet(aRequest, MT_GET_PAGE_REQUEST);
}

PSPacket 
link_packet(PSPacket aFirst, PSPacket aNext)
{
	PSPacket 
		Temp;

	Temp = aFirst;
	while(Temp->next) 
		Temp = Temp->next;
	Temp->next = aNext;

	return aFirst;
}

PSPacket
link_string_packet(PSPacket *aFirst, PSPacket aPacket
		/*, request_rec *r*/)
{
	PSPacket
		Temp, 
		Temp2;

	unsigned char
		Count;

	if ((aPacket->data[0] != ((011<<2) + 1)) ||
			(aPacket->data[1] != 1)) return NULL;

	if (!*aFirst)
		return (*aFirst = aPacket);
	
/*	log(r, "First");
	Temp = *aFirst;
	while(Temp)
	{
		log(r, "D1[%02X] D2[%02X] D3[%02X] D4[%02X] D5[%02X] D6[%02X]",
			Temp->data[0], Temp->data[1], Temp->data[2], 
			Temp->data[3], Temp->data[4], Temp->data[5]);
		Temp = Temp->next;
	}
	log(r, "Packet");
	log(r, "D1[%02X] D2[%02X] D3[%02X] D4[%02X] D5[%02X] D6[%02X]",
			aPacket->data[0], aPacket->data[1], aPacket->data[2], 
			aPacket->data[3], aPacket->data[4], aPacket->data[5]);
	log(r, "Check %02X", ((011<<2) + 1));*/

	Count = aPacket->data[2];

	if ((*aFirst)->data[2] > Count)
	{
		Temp = *aFirst;
		*aFirst = aPacket;
		aPacket->next = Temp;
		return *aFirst;
	}

	Temp = *aFirst;
	while(Temp->next && Temp->next->data[2] < Count) Temp = Temp->next;

	if (!Temp->next)
	{
		Temp->next = aPacket;
	} else {
		Temp2 = Temp->next;
		Temp->next = aPacket;
		aPacket->next = Temp2;
	} 
	return *aFirst;
}

int
send_packet_to_gameserver(request_rec *aRequest, 
		int aSocket, PSPacket aSend)
{
	PSPacket
		Packet = aSend;

	if (!aSend)
		return -1;

	while(Packet)
	{
//		log( aRequest, "send packet (size: %d/%04X)", Packet->size, Packet->size );
//		Dump( aRequest, (char *) Packet, Packet->size );
		if (send_packet(aSocket, Packet) < 0) return -1;
		Packet = Packet->next;
	}

//	log(aRequest, "to send packet is completed");
		
	return 0;
}

int 
receive_packet_from_gameserver(request_rec *aRequest, int aSocket, 
		PSPacket *aHeader, PSPacket *aCookie, PSPacket *aContent)
{
	struct timeval 
		Timeout;
	fd_set 
		Checks;
	int 
		Count = 0,
		Terminate = 0,	
		NRead,
		Res;

	PSPacket 
		Packet;

	PSPacket
		Header = NULL,
		Cookie = NULL,
		Content = NULL;

	Timeout.tv_sec = 1;
	Timeout.tv_usec = 0;

	ap_hard_timeout("receive_packet_from_gameserver", aRequest);
	
	while(!Terminate)
	{
		FD_ZERO(&Checks);
		FD_SET(aSocket, &Checks);
		Res = select(aSocket+1, &Checks, 
				(fd_set*)NULL, (fd_set*)NULL, &Timeout); 
		Count++;
		if (Res < 0) 
		{
			log(aRequest, "error in select()");
			ap_kill_timeout(aRequest);
			return -1;
		}
		if (Count > 60) return -1;
		if (Res == 0) continue;
		if (!FD_ISSET(aSocket, &Checks)) continue;

		ap_reset_timeout(aRequest);
		ioctl(aSocket, FIONREAD, &NRead);
		if (NRead == 0)
		{
			log(aRequest, "close by server");
			ap_kill_timeout(aRequest);
			return 0;
		}  
		Packet = receive_packet(aSocket, aRequest);
		if (!Packet) 
		{
			log(aRequest, "Could not get packet from server");
			ap_kill_timeout(aRequest);
			return -3;
		}
//		log(aRequest, "get packet type %02X size %d",
//				Packet->header.header_struct.type,
//				Packet->size);
		switch (Packet->header.header_struct.type)
		{
			case MT_HEADER_SEND:
				if (Header == NULL)
					Header = Packet;
				else link_packet(Header, Packet);
				break;
			case MT_SET_COOKIE_SEND:
//				log(aRequest, "set-cookie %d", 
//						Packet->size);
				link_string_packet(&Cookie, Packet);
				break;
			case MT_CONTENT_SEND:
//				log(aRequest, "add content");
				link_string_packet(&Content, Packet);
				break;
			case MT_TERMINATE_REQUEST:
//				log(aRequest, "terminate");
				Terminate = 1;
				break;
		}
	}

	*aHeader = Header;
	*aCookie = Cookie;
	*aContent = Content;

	ap_kill_timeout(aRequest);
	return 0;
}
	
void
set_header(request_rec *aRequest, PSPacket aPacket)
{
	PSPacket
		Packet;
	
	char
		Field[STRING_DATA_BLOCK+1],
		Value[STRING_DATA_BLOCK+1],
		*Data;
	int 
		Type,
		Length;
	
	Packet = aPacket;

	while(Packet)
	{
		if (get_item_from_packet(Packet, &Type, (void**)&Data, &Length))
		{
			log(aRequest, "Could not get item in set_header()");
			return;
		}
		if (Type != MESSAGE_ITEM_ASCII)
		{
			log(aRequest, "Could not get item in set_header()");
			return;
		}
		memcpy(Field, Data, Length);
		Field[Length] = '\0';

		if (get_item_from_packet(Packet, &Type, (void**)&Data, &Length))
		{
			log(aRequest, "Could not get item in set_header()");
			return;
		}
		if (Type != MESSAGE_ITEM_ASCII)
		{
			log(aRequest, "Could not get item in set_header()");
			return;
		}
		memcpy(Value, Data, Length);
		Value[Length] = '\0';

		ap_table_add(aRequest->headers_out, Field, Value);
//		log(aRequest, "set header %s = %s", Field, Value);

		Packet = Packet->next;
	}
}

void 
set_cookie(request_rec *aRequest, PSPacket aPacket)
{
	int 
		Size;
	char
		*Cookie;
	PSPacket
		Packet;
	int 
		ByteOfSize,
		Length;
	const char
		*Data;
	const char 
		*Pair;

	struct tm 
		*Tms;
	time_t 
		Now; 

//	if (aPacket == NULL) 
//		log(aRequest, "Cookie is NULL");
	/* get size */
	Size = 0;
	Packet = aPacket;
	while(Packet) {
		ByteOfSize = Packet->data[3] & 0x03;
		
		if (ByteOfSize == 2)
		{
			Size += Packet->data[4] + Packet->data[5] * 256;
		} else if (ByteOfSize == 1) {
			Size += Packet->data[4];
		} else {
			log(aRequest, "Could not understand packet");
			return;
		}
		Packet = Packet->next;
	}

	Cookie = (char*)ap_pcalloc(aRequest->pool, Size+1);

	Packet = aPacket;
	Length = 0;
	while(Packet)
	{
		ByteOfSize = Packet->data[3] & 0x03;
		
		if (ByteOfSize == 2)
		{
			Size = Packet->data[4] + Packet->data[5] * 256;
			memcpy(Cookie+Length, &Packet->data[6], Size);
			Length += Size;
		} else if (ByteOfSize == 1) {
			Size = Packet->data[4];
			memcpy(Cookie+Length, &Packet->data[5], Size);
			Length += Size;
		} 
		Packet = Packet->next;
	}
	Cookie[Length] = '\0';

	Data = Cookie;
	while(*Data && (Pair=ap_getword(aRequest->pool, &Data, ';')))
	{
		const char 
			*Name, 
			*Value,
			*Cookie;

		if (*Data == ' ') ++Data;
		Name = ap_getword(aRequest->pool, &Pair, '=');
		Value = Pair;

		if (strlen(Value))
		{
			Cookie = ap_psprintf(aRequest->pool, 
						"%s=%s; path=/; domain=archspace.com",
						Name, Value);
		} else {
			Now = time(0)-31536001;
			Tms = gmtime(&Now);

			Cookie = ap_psprintf(aRequest->pool,
				"%s=%s;"
				"expires=%s, %.2d-%s-%.2d %.2d:%.2d:%.2d GMT;"
				"domain=archmage.co.kr",
				Name, Value, ap_day_snames[Tms->tm_wday],
				Tms->tm_mday, ap_month_snames[Tms->tm_mon],
				Tms->tm_year % 100, 
				Tms->tm_hour, Tms->tm_min, Tms->tm_sec);
		}
//		log(aRequest, "cookie %s", Cookie);

		ap_table_add(aRequest->headers_out, "Set-Cookie", Cookie);
	}

//	log(aRequest, "Set-Cookie %s", Cookie);
}

void
set_content(request_rec *aRequest, PSPacket aPacket)
{
	char
		*Content;
	char 
		Buffer[STRING_DATA_BLOCK+1];
	int 
		ByteOfSize,
		Size;

	PSPacket
		Packet;

	Packet = aPacket;

	while(Packet)
	{
		ByteOfSize = Packet->data[3] & 0x03;
/*		log(aRequest, " %d size %d,ByteOfSize %d", 
			Packet->data[3] >> 2, Packet->size, ByteOfSize);
		log(aRequest, "D1[%02X] D2[%02X] D3[%02X] D4[%02X] D5[%02X] D6[%02X]",
			Packet->data[0], Packet->data[1], Packet->data[2], 
			Packet->data[3], Packet->data[4], Packet->data[5]);*/
	
		if (ByteOfSize == 2)
		{
			Size = Packet->data[4] + Packet->data[5]*256;
			memcpy(Buffer, &Packet->data[6], Size);
			Buffer[Size] = '\0';
		} else if (ByteOfSize == 1) {
			Size = Packet->data[4];
			memcpy(Buffer, &Packet->data[5], Size);
			Buffer[Size] = '\0';
		}
//		log(aRequest, "puts %d bytes", Size);
//		log(aRequest, "data %s", Buffer);
		ap_rputs(Buffer, aRequest);
		Packet = Packet->next;
	}
}

void
get_connection_info(request_rec *aRequest, char **aHost, int *aPort)
{
	char
		*Cookie,
		*Temp;
	int
		i,
		Length,
		Len;

	*aHost = NULL;
	*aPort = 0;

	Len	= strlen(aRequest->uri);
	if (Len > 7)
	{
		char
			*Ext;
		Ext	= (char*)&aRequest->uri[Len-6];
//		log(aRequest, "Ext:%s", Ext);
		if (!strcmp(Ext, ".entry")) return;
	}

	Cookie = (char*)ap_table_get(aRequest->headers_in, "Cookie");

	if (!Cookie)
	{
		log(aRequest, "Cookie is NULL");
		return;
	}

	for(i=0; i<strlen(Cookie); i++)
	{
		if (!strncmp((char*)(Cookie+i), "ARCHSPACE_HOST=", 15))
		{
			i += 15;
			Temp = strpbrk((char*)(Cookie+i), ";");
			if (!Temp) 
				Temp = Cookie+strlen(Cookie);
			Length = Temp-Cookie-i;
			*aHost = ap_pcalloc(aRequest->pool, Length+1);
			strncpy(*aHost, Cookie+i, Length);
			*(*aHost+Length) = 0;
			break;
		}
	}

	for(i=0; i<strlen(Cookie); i++)
	{
		if (!strncmp((char*)(Cookie+i), "ARCHSPACE_PORT=", 15))
		{
			sscanf(Cookie+i+15, "%d", aPort);
			break;
		}
	}
}
