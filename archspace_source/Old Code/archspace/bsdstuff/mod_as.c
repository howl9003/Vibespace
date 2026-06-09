#include "httpd.h"
#include "http_config.h"
#include "http_core.h"
#include "http_log.h"
#include "http_main.h"
#include "http_protocol.h"
#include "http_request.h"

#include "as.h"

static int archspace_handler(request_rec *aRequest);
static void *archspace_create_dir_config(pool *aPool, char *aPath);
static void *archspace_create_config(pool *aPool, server_rec *aServer);
static const char *get_web_server_id(cmd_parms *aParms, 
		void *aConfig, char *aTo);
static const char *get_game_server_name(cmd_parms *aParms, 
		void *aConfig, char *aTo);
static const char *get_game_server_port(cmd_parms *aParms, 
		void *aConfig, char *aTo);
static const char *get_redirect_url(cmd_parms *aParms, 
		void *aConfig, char *aTo);
static const char *get_game_server_down_message(cmd_parms *aParms,
		void *aConfig, char *aTo);
static const char *get_game_server_maintenance_message(cmd_parms *aParms,
		void *aCOnfig, char *aTo);

static handler_rec ArchspaceHandlers[] =
{
	{ "archspace-handler", archspace_handler },
	{ "archspace-script", archspace_handler },
	{ "application/x-archspace", archspace_handler },
	{ NULL }
};

static command_rec ArchspaceCommands[] =
{
	{
		"ArchspaceWebServerID",
		get_web_server_id,
		NULL,
		RSRC_CONF,
		TAKE1,
		"Web server serial",
	},
	{
		"ArchspaceGameServerName",
		get_game_server_name,
		NULL,
		RSRC_CONF,
		TAKE1,
		"Archspace Game Server Name",
	},
	{
		"ArchspaceGameServerPort",
		get_game_server_port,
		NULL,
		RSRC_CONF,
		TAKE1,
		"Archspace Game Server Port",
	},
	{
		"ArchspaceRedirectURL",
		get_redirect_url,
		NULL,
		RSRC_CONF,
		TAKE1,
		"Archspace Redirect URL",
	},
	{
		"ArchspaceGameServerDownMessage",
		get_game_server_down_message,
		NULL,
		RSRC_CONF,
		TAKE1,
		"Archspace Game Server Down Message",
	},
	{
		"ArchspaceGameServerMaintenanceMessage",
		get_game_server_maintenance_message,
		NULL,
		RSRC_CONF,
		TAKE1,
		"Archspace Game Server Maintenance Message",
	},


	{NULL}
};

module MODULE_VAR_EXPORT as_module =
{
	STANDARD_MODULE_STUFF,
	NULL,
	NULL,
	NULL,
	archspace_create_config,
	NULL,
	ArchspaceCommands,
	ArchspaceHandlers,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL,
	NULL
};


static int 
util_read(request_rec *aRequest, const char **aRequestBuffer)
{
	int 
		RC;

	if ((RC = ap_setup_client_block(aRequest, REQUEST_CHUNKED_ERROR)) 
															!= OK)
		return RC;
	
	if (ap_should_client_block(aRequest))
	{
		char 
			ArgsBuffer[HUGE_STRING_LEN];
		int 
			Size, 
			ReadLength, 
			Pos = 0;
		long 
			Length = aRequest->remaining;

		*aRequestBuffer = ap_pcalloc(aRequest->pool, Length+1);
		if (aRequestBuffer == NULL)
		{
			fprintf(aRequest->server->error_log,
				"util_read() memory allocation error.\n");
			exit(-1);
		}
		ap_hard_timeout("util_read", aRequest);


		while((ReadLength =
				ap_get_client_block(aRequest, ArgsBuffer,
							sizeof(ArgsBuffer))) > 0)
		{
			ap_reset_timeout(aRequest);

			if ((Pos + ReadLength) > Length)
			{
				Size = Length - Pos;
			} else {
				Size = ReadLength;
			}
			memcpy((char*)*aRequestBuffer + Pos, ArgsBuffer, Size);
			Pos += Size;
		}
		ap_kill_timeout(aRequest);
	}
	return RC;
}

#define DEFAULT_ENCTYPE "application/x-www-form-urlencoded" 
static int 
read_post(request_rec *aRequest, const char **aPost) 
{ 
	const char 
		*Data; 
	const char 
		*Key, 
		*Value, 
		*Type; 
	int 
		RC = OK; 

	if (aRequest->method_number != M_POST) 
	{ 
		*aPost = NULL; 
		return RC; 
	} 

	Type = ap_table_get(aRequest->headers_in, "Content-Type"); 
	
	if (strcasecmp(Type, DEFAULT_ENCTYPE) != 0) 
	{ 
		return DECLINED; 
	} 
	if ((RC = util_read(aRequest, &Data)) != OK) 
	{ 
		return RC; 
	} 
	*aPost = Data; 
	return RC; 
} 

static void*
archspace_create_config(pool *aPool, server_rec *aServer)
{
	SArchspaceConfig 
		*ArchspaceConfig;
	ArchspaceConfig = (SArchspaceConfig*)
				ap_pcalloc(aPool, sizeof(SArchspaceConfig));

	ArchspaceConfig->server_serial = -1;
	ArchspaceConfig->game_server_name = NULL;
	ArchspaceConfig->game_server_port = 0;

	fprintf(aServer->error_log, "Archspace Module start (v0.5)\n");	

	return (void*)ArchspaceConfig;
}

static const char*
get_web_server_id(cmd_parms *aParams, void *aConfig, char *aTo)
{
	SArchspaceConfig 
		*Config = 
			(SArchspaceConfig*)ap_get_module_config(
				aParams->server->module_config, &as_module);

	fprintf(aParams->server->error_log, 
			"get_web_server_id %d\n", atoi(aTo));
	Config->server_serial = atoi(aTo);

	return NULL;
}

static const char*
get_game_server_name(cmd_parms *aParams, void *aConfig, char *aTo)
{
	SArchspaceConfig 
		*Config = 
			(SArchspaceConfig*)ap_get_module_config(
					aParams->server->module_config, &as_module);

	fprintf(aParams->server->error_log, 
			"game_server_name %s\n", aTo);

	Config->game_server_name = (char*)ap_pstrdup(aParams->pool, aTo);

	return NULL;
}

static const char*
get_game_server_port(cmd_parms *aParams, void *aConfig, char *aTo)
{
	SArchspaceConfig 
		*Config = 
			(SArchspaceConfig*)ap_get_module_config(
					aParams->server->module_config, &as_module);

	fprintf(aParams->server->error_log, 
			"game_server_port %s\n", aTo);

	Config->game_server_port = atoi(aTo);

	return NULL;
}

static const char*
get_redirect_url(cmd_parms *aParams, void *aConfig, char *aTo)
{
	SArchspaceConfig 
		*Config = 
			(SArchspaceConfig*)ap_get_module_config(
					aParams->server->module_config, &as_module);

	fprintf(aParams->server->error_log, 
			"redirect url %s\n", aTo);

	if (!aTo)
		Config->redirect_url = "/index.html";
	else
		Config->redirect_url = (char*)ap_pstrdup(aParams->pool, aTo);

	return NULL;
}

static const char*
get_game_server_down_message(cmd_parms *aParams, void *aConfig, char *aTo)
{
	static char *
		DefaultMessage = "The server has just gone down. This may have been caused by your actions. Please report this to the <A HREF=\"mailto:bug@arch30.archspace.com\">Archspace Customer Support Team</A>.";
	char
		Buffer[1024 + 1];
	static char *
		Message;
	SArchspaceConfig 
		*Config = 
			(SArchspaceConfig*)ap_get_module_config(
					aParams->server->module_config, &as_module);

	fprintf(aParams->server->error_log, 
			"game server down message %s\n", aTo);

	if (!aTo)
	{
		Message = (char *)malloc(strlen(DefaultMessage) + 1);
		strcpy(Message, DefaultMessage);
		Config->game_server_down_message = Message;
	}
	else
	{
		FILE *
			File;
		File = fopen((char*)ap_pstrdup(aParams->pool, aTo), "r");

		if (File == NULL)
		{
			Message = (char *)malloc(strlen(DefaultMessage) + 1);
			strcpy(Message, DefaultMessage);
			Config->game_server_down_message = Message;
		}
		else
		{
			Message = NULL;
			while (fgets(Buffer, 1024, File) > 0)
			{
				if (Buffer[0] == NULL) break;

				if (Message == NULL)
				{
					Message = (char *)malloc(strlen(Buffer) + 1);
					strcpy(Message, Buffer);
				}
				else
				{
					Message = (char *)realloc(Message, strlen(Message) + strlen(Buffer) + 1);
					strcat(Message, Buffer);
				}
			}
			Config->game_server_down_message = Message;
		}
	}

	return NULL;
}

static const char*
get_game_server_maintenance_message(cmd_parms *aParams, void *aConfig, char *aTo)
{
	static char *
		DefaultMessage = "The server is currently down for maintainence.";
	char
		Buffer[1024 + 1];
	static char *
		Message;

	SArchspaceConfig 
		*Config = 
			(SArchspaceConfig*)ap_get_module_config(
					aParams->server->module_config, &as_module);

	fprintf(aParams->server->error_log, 
			"game server maintenance message %s\n", aTo);

	if (!aTo)
	{
		Message = (char *)malloc(strlen(DefaultMessage) + 1);
		strcpy(Message, DefaultMessage);
		Config->game_server_maintenance_message = Message;
	}
	else
	{
		FILE *
			File;
		File = fopen((char*)ap_pstrdup(aParams->pool, aTo), "r");

		if (File == NULL)
		{
			Message = (char *)malloc(strlen(DefaultMessage) + 1);
			strcpy(Message, DefaultMessage);
			Config->game_server_maintenance_message = Message;
		}
		else
		{
			Message = NULL;
			while (fgets(Buffer, 1024, File) > 0)
			{
				if (Buffer[0] == NULL) break;

				if (Message == NULL)
				{
					Message = (char *)malloc(strlen(Buffer) + 1);
					strcpy(Message, Buffer);
				}
				else
				{
					Message = (char *)realloc(Message, strlen(Message) + strlen(Buffer) + 1);
					strcat(Message, Buffer);
				}
			}
			Config->game_server_maintenance_message = Message;
		}
	}

	return NULL;
}

int 
error_message(request_rec *aRequest, const char *aMessage)
{
	aRequest->content_type = "text/html";
	ap_table_set(aRequest->headers_out, "Pragma", "no-cache");
	ap_table_set(aRequest->headers_out, "Cache-Control", "no-cache");
	ap_table_set(aRequest->headers_out, "Expires", "0");
	ap_send_http_header(aRequest);

	ap_rprintf(aRequest,
		"<html>\n"
		"<head>\n"
		"<title>*********ARCHSPACE************</title>\n"
		"<meta http-equiv=\"Content-Type\" content=\"text/html; charset=euc-kr\">\n"
		"<script language=\"JavaScript\">\n"
		"<!--\n"
		"function MM_openBrWindow(theURL,winName,features) { //v2.0"
		"  window.open(theURL,winName,features);\n"
		"}\n"
		"//-->\n"
		"</script>\n"
		"<LINK REL=\"stylesheet\" HREF=\"/archspace.css\">"
		"</head>\n"

		"<body bgcolor=\"#000000\" style=\"margin:0;\" marginwidth=\"0\" marginheight=\"0\" link=\"#999999\" vlink=\"#999999\" alink=\"#999999\" onLoad=\"\">\n"
		"<TABLE WIDTH=\"610\" BORDER=\"0\" CELLSPACING=\"0\" CELLPADDING=\"0\">\n"

		"<TR>\n"
		"<TD>\n"
		"<IMG SRC=\"/Ad/scookie.phtml?ctxt=AdInfoS&cval=0\" WIDTH=5 HEIGHT=5 ALT=\"\">\n"
		"<CENTER>\n"
		"<A HREF=\"http://arch30.archspace.com/main.phtml\">\n"
		"<IMG SRC=\"http://image.archspace.com/image/as_game/banner.gif\" ISMAP BORDER=0 ></A>\n"
		"</CENTER>\n"
		"</TD>\n"
		"</TR>\n"

		"<TR>\n"
		"<TD ALIGN=\"center\">&nbsp; </TD>\n"
		"</TR>\n"

		"<TR>\n"
		"<TD><P>&nbsp;</P></TD>\n"
		"</TR>\n"

		"<TR>\n"
		"<TD CLASS=\"maintext\" ALIGN=\"center\"></TD>\n"
		"</TR>\n"

		"<TR>\n"
		"<TD ALIGN=\"center\" HEIGHT=\"18\"><IMG SRC=\"http://image.archspace.com/image/as_game/message/message.gif\" WIDTH=\"175\" HEIGHT=\"131\">\n"
		"</TD>\n"
		"</TR>\n"

		"<TR>\n"
		"<TD ALIGN=\"center\" HEIGHT=\"18\">&nbsp;</TD>\n"
		"</TR>\n"

		"<TR ALIGN=\"CENTER\" VALIGN=\"TOP\">\n"
		"<TD HEIGHT=\"150\">\n"
		"<P CLASS=\"maintext\">%s</P>\n"
		"</TD>\n"
		"</TR>\n"

		"</TABLE>\n"
		"<P>&nbsp;</P>\n"
		"</body>\n"
		"</html>",
			aMessage);
	return OK;
}

int 
check_redirect(request_rec *aRequest)
{
	char *Cookie;
	const char *Buffer;
	const char *Pair;

	Cookie = (char*)ap_table_get(aRequest->headers_in, "Cookie");

//	log(aRequest, "YOSHIKI : Cookie:%s", Cookie);
	if (!Cookie) return 1;

	Buffer = (const char*)Cookie;

	while(*Buffer && (Pair = ap_getword(aRequest->pool, &Buffer, ';')))
	{
		const char *Name;

		if (*Buffer == ' ') ++Buffer;
		Name = ap_getword(aRequest->pool, &Pair, '=');

	//	log(aRequest, "Cookie: name:%s, value:%s", Name, Pair);

		if (Name && strlen(Name))
			if (strcmp(Name, "ID_STRING") == 0) return 0;
	}

	return 1;
}

static int 
archspace_handler(request_rec *aRequest)
{
	SArchspaceConfig
		*Config;

	int
		RC,
		Length;

	const char 
		*Buffer;

	PSPacket
		Packet, 
		Header,
		Cookie,
		Content,
		Send;

	char 
		*Host;
	int 
		Port;
	
	int 
		Socket;

	Config = (SArchspaceConfig*)
			ap_get_module_config(aRequest->server->module_config, 
					&as_module);

	if (check_redirect(aRequest))
	{
		aRequest->content_type = "text/html";
		ap_table_add(aRequest->headers_out, "Pragma", "no-cache");
		ap_table_set(aRequest->headers_out, "Cache-Control", "no-cache");
		ap_table_set(aRequest->headers_out, "Expires", "0");
		ap_send_http_header(aRequest);

		ap_rprintf(aRequest, 
			"<html>\n"
			"<head>\n"
			"<title>*********ARCHSPACE************</title>\n"
			"<meta http-equiv=\"Content-Type\" content=\"text/html; charset=euc-kr\">\n"
			"<meta HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=%s\">\n"
			"</head>\n"
			"<body>\n"
			"</body>\n"
			"</html>\n", Config->redirect_url);

		return OK;
	}

	if (strcmp(aRequest->method, "POST") == 0)
	{
		RC = read_post(aRequest, &Buffer);

		if ((RC == OK) && (Buffer != NULL))
		{
			Length = strlen(Buffer);
			if (Length == 0)
			{
				error_message(aRequest,
						"ERROR: Archspace apache module<BR>\n"
						"CASE1:Some trouble occured to get POST"
						" datas.<BR>\n");
				return OK;
			} 
		} else {
			if (RC != OK)
			{
				error_message(aRequest, 
					"ERROR: Archspace apache module<BR>\n"
					"CASE2:Some trouble occured to get POST"
					" datas (%s)<BR>\n");
				return OK;
			}
		}
	} else if (strcmp(aRequest->method, "GET") == 0)
	{
		Buffer = aRequest->parsed_uri.query;
	} else {
		error_message(aRequest,
			"ERROR:Archspace Apache moudle<BR>\n"
			"User access by Unknown method<BR>\n");
		return OK;
	}

	if (Config->server_serial <= 0)
	{
		error_message(aRequest,
			"ERROR:Archspace Apache moudle<BR>\n"
			"please, Admin...<BR>\n"
			"Add line 'ArchspaceWebServerID"
			" ServerNumber' in httpd.conf<BR>\n");
		return OK;
	}

	if (!Config->game_server_name)
	{
		error_message(aRequest,
			"ERROR:Archspace Apache moudle<BR>\n"
			"please, Admin...<BR>\n"
			"Add line 'ArcnspaceEntryServerName"
			" ServerName' in httpd.conf<BR>\n");
		return OK;
	}

	if (!Config->game_server_port)
	{
		error_message(aRequest,
			"ERROR:Archspace Apache moudle<BR>\n"
			"please, Admin...<BR>\n"
			"Add line 'ArcnspaceEntryServerPort"
			" PortNumber' in httpd.conf<BR>\n");
		return OK;
	}

//	get_connection_info(aRequest, &Host, &Port);
//	if (!Host)
		Socket = make_connection(Config->game_server_name,
							Config->game_server_port, aRequest);
//	else
//		Socket = make_connection(Host, Port, aRequest);
	if (Socket <= 0)
	{
		error_message(aRequest,
			Config->game_server_maintenance_message);
		return OK;
	}

//	log(aRequest, "YOSHIKI : connection to Archspace game server");

	/* make data to send */
	Send = make_url_send(aRequest);
	if (!Send)
	{
		error_message(aRequest,
			"ERROR:Archspace Apache module<BR>\n"
			"Could not make URL send packet<BR>\n");
		close(Socket);
		return OK;
	}
	Packet = make_referer_send(aRequest);
	if (Packet) link_packet(Send, Packet);
	Packet = make_method_send(aRequest);
	if (Packet) link_packet(Send, Packet);
	Packet = make_cookie_send(aRequest);
	if (Packet) {
//		log( aRequest, "testlog cookie send" );
		link_packet(Send, Packet);
	}
	Packet = make_encoding_send(aRequest);
	if (Packet) link_packet(Send, Packet);
	Packet = make_language_send(aRequest);
	if (Packet) link_packet(Send, Packet);
	Packet = make_agent_send(aRequest);
	if (Packet) link_packet(Send, Packet);
	Packet = make_host_name_send(aRequest);
	if (Packet) link_packet(Send, Packet);
	Packet = make_connection_send(aRequest);
	if (Packet) {
//		log( aRequest, "testlog connection send" );
		link_packet(Send, Packet);
	}
	Packet = make_query_send(aRequest, (char*)Buffer);
	if (Packet) {
//		log( aRequest, "testlog query send" );
		link_packet(Send, Packet);
	}
	Packet = make_getpage_request(aRequest);
	if (!Packet)
	{
		error_message(aRequest,
			"ERROR:Archspace Apache module<BR>\n"
			"Could not get_page_request packet<BR>\n");
		close(Socket);
		return OK;
	}
	link_packet(Send, Packet);

	if (send_packet_to_gameserver(aRequest, Socket, Send) < 0)
	{
		error_message(aRequest,
			"ERROR:Archspace Apache module<BR>\n"
			"Could not send packet to game server<BR>\n");
		close(Socket);
		return OK;
	}

//	log(aRequest, "Wait to receive");

	if (receive_packet_from_gameserver(aRequest, Socket, 
			&Header, &Cookie, &Content) < 0)
	{
		error_message(aRequest,
			"ERROR:Archspace Apache module<BR>\n"
			"Could not receive packet from game server<BR>\n");
		close(Socket);
		return OK;
	}

	if (!Content)
	{
		error_message(aRequest,
			Config->game_server_down_message);
		close(Socket);
		return OK;
	}

	aRequest->content_type = "text/html";
//	set_header(aRequest, Header);
	set_cookie(aRequest, Cookie);
	ap_table_add(aRequest->headers_out, "Pragma", "no-cache");
	ap_table_set(aRequest->headers_out, "Cache-Control", "no-cache");
	ap_table_set(aRequest->headers_out, "Expires", "0");
	ap_send_http_header(aRequest);
	set_content(aRequest, Content);

//	log(aRequest, "successfully");

	close(Socket);	

	return OK;
}
