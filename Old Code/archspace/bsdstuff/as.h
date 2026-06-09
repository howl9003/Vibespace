#if !defined(__ARCHSPACE_APACHE_MODULE_H__)
#define __ARCHSPACE_APACHE_MODULE_H__

#define MT_TERMINATE_REQUEST		0x0000
#define MT_ERROR_SEND				0x0001
#define MT_URL_SEND					0x8001
#define MT_METHOD_SEND				0x8003
#define MT_REFERER_SEND				0x8005
#define MT_COOKIE_SEND				0x8007
#define MT_ACCEPT_ENCODING_SEND		0x8009
#define MT_ACCEPT_LANGUAGE_SEND		0x800B
#define MT_USER_AGENT_SEND			0x800D
#define MT_HOST_NAME_SEND			0x800F
#define MT_CONNECTION_INFO_SEND		0x8011
#define MT_QUERY_SEND				0x8013
#define MT_GET_PAGE_REQUEST			0x8015
#define MT_HEADER_SEND				0x8101
#define MT_SET_COOKIE_SEND			0x8103
#define MT_CONTENT_SEND				0x8105

#define MAX_PACKET_SIZE				4096
#define MESSAGE_HEADER_SIZE			8
#define MAX_MESSAGE_DATA_SIZE		4000

#define MESSAGE_ITEM_LIST       000
#define MESSAGE_ITEM_ASCII      001
#define MESSAGE_ITEM_UINT8      010
#define MESSAGE_ITEM_UINT4      014
#define MESSAGE_ITEM_UINT2      012
#define MESSAGE_ITEM_UINT1      011
#define MESSAGE_ITEM_INT8       020
#define MESSAGE_ITEM_INT4       024
#define MESSAGE_ITEM_INT2       022
#define MESSAGE_ITEM_INT1       021
#define MESSAGE_ITEM_FLOAT8     030
#define MESSAGE_ITEM_FLOAT4     034
#define MESSAGE_ITEM_BOOLEAN    040

#define STRING_DATA_BLOCK			3980

struct request_rec;

void log(request_rec* aRequest, const char *aFormat, ...);
void get_connection_info(request_rec* aRequest, 
		char **aHost, int *aPort);
int make_connection(char *aServer, int aPort, request_rec *aRequest);

typedef struct
{
	char 
		*game_server_name;
	int 
		game_server_port;
	int 
		server_serial;
	char 
		*redirect_url;
	char
		*game_server_down_message;
	char
		*game_server_maintenance_message;
} SArchspaceConfig;

typedef struct
{
	unsigned short int 
		size,
		type,
		server,
		counter;
} SMessageHeader;

typedef struct _tPacket SPacket, *PSPacket;
struct _tPacket
{
	union {
		SMessageHeader 
			header_struct;
		unsigned char 
			header_byte[MESSAGE_HEADER_SIZE];
	} header;
	unsigned char 
		data[MAX_MESSAGE_DATA_SIZE];

	int 
		sent;
	int 
		read;
	unsigned short int
		size;
	PSPacket
		next;
};

int send_packet(int aSocket, PSPacket aPacket);
PSPacket receive_packet(int aSocket, request_rec *aRequest);
PSPacket make_packet(request_rec *aRequest, int aType);

int add_item_to_packet(PSPacket aPacket, int aType, 
		void *aData, int aDataSize);
int get_item_from_packet(PSPacket aPacket, int *aType, 
		void **aData, int *aDataSize);

PSPacket make_url_send(request_rec *aRequest);
PSPacket make_method_send(request_rec *aRequest);
PSPacket make_referer_send(request_rec *aRequest);
PSPacket make_cookie_send(request_rec *aRequest);
PSPacket make_encoding_send(request_rec *aRequest);
PSPacket make_language_send(request_rec *aRequest);
PSPacket make_agent_send(request_rec *aRequest);
PSPacket make_host_name_send(request_rec *aRequest);
PSPacket make_connection_send(request_rec *aRequest);
PSPacket make_query_send(request_rec *aRequest, char *aData);
PSPacket make_getpage_request(request_rec *aRequest);

PSPacket link_packet(PSPacket aFirst, PSPacket aNext);

int send_packet_to_gameserver(request_rec *aRequest, 
		int aSocket, PSPacket aSend);

#endif
