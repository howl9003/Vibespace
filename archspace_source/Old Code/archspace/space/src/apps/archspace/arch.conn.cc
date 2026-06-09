#include "common.h"
#include "util.h"
#include "archspace.h"
#include <cstdlib>
#include "define.h"
#include "banner.h"
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <libintl.h>
#include "player.h"
#include "game.h"
#include "council.h"
#include "admin.h"

TZone gArchspaceConnectionZone =
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CArchspaceConnection),
	0,
	0,
	NULL,
	"Zone CArchspaceConnection"
};

CArchspaceConnection::CArchspaceConnection(
				CApplication *aApplication):
	CCryptConnection()
{
	mPortalID = -1;
	mGameID = -1;
}

CArchspaceConnection::~CArchspaceConnection()
{
	close();
}

bool decrypt_success(const char *aString, int aLength)
{
	const static char 
		Ascii[] = "1234567890=&%;,.+-_ "
				"ABCDEFGHIJKLMNOPQRSTUVWXYZ"
				"abcdefghijklmnopqrstuvwxyz";

	if( aString == NULL ) return false;
	for(int i=0; i<aLength; i++)
		if (!strchr(Ascii, aString[i]))
			return false;
	return true;
}

CString&
CArchspaceConnection::decrypt_id_string(const char *aString)
{
	static CString 
		Buffer;

	int 
		Length = (strlen(aString)-16)/2;

	Buffer.clear();
	decrypt(CRYPT_CURRENT_KEY, aString, Buffer);

	if (Buffer.length() != 0)
	{
		if (!decrypt_success((char*)Buffer, Length)) 
		{
			Buffer.clear();
			decrypt(CRYPT_OLD_KEY, aString, Buffer);
			if (!decrypt_success((char*)Buffer, Length))
			{
				Buffer.clear();
				decrypt(CRYPT_FUTURE_KEY, aString, Buffer);
				if (!decrypt_success((char*)Buffer, Length))
				{
					Buffer.clear();
				}
			} 
		}
	}

	return Buffer;	
}

void
CArchspaceConnection::message_page(const char *aMessage)
{
	CQueryList
		Conversion;
	Conversion.set_value("MESSAGE", aMessage);

	char *
		TopBanner = BANNER->get_top_banner_by_country_menu(mCookies.get_value("COUNTRY"));
	char *
		BottomBanner = BANNER->get_bottom_banner_by_country_menu(mCookies.get_value("COUNTRY"));

	if (TopBanner == NULL || BANNER->is_top_banner_available() == false)
	{
		Conversion.set_value("ADLINE", " ");
	}
	else
	{
		Conversion.set_value("ADLINE", TopBanner);
	}

	if (BottomBanner == NULL || BANNER->is_bottom_banner_available() == false)
	{
		Conversion.set_value("ADLINE_BOTTOM", " ");
	}
	else
	{
		Conversion.set_value("ADLINE_BOTTOM", BottomBanner);
	}

	CString
		Output;
	CPage::get_html_station()->get_html(Output, "message.html", &Conversion);
	set_content(Output);
	send_terminate();
}

void
CArchspaceConnection::portal_login_message_page(const char *aMessage)
{
	CQueryList
		Conversion;
	Conversion.set_value("MESSAGE", aMessage);

	CString
		Output;
	CPage::get_html_station()->get_html(Output, "login_again.html", &Conversion);
	set_content(Output);
	send_terminate();
}

void
CArchspaceConnection::page(const char *aMessage)
{
	CString
		Output = aMessage;

	set_content(Output);
	send_terminate();
}

bool
CArchspaceConnection::make_page()
{
	static time_t t = time(0);
	static int count = 0;

	if( t <= time(0)-60 ){
		system_log( "page handler call %d during %d seconds", count, time(0)-t );
		count = 0;
		t = time(0);
	}

	count++;

	// ip ban
	if (ADMIN_TOOL->get_banned_ip_list()->has(inet_addr(get_connection())))
	{
		SLOG("Ban IP %s", get_connection());
		message_page("Your IP is not allowed");
		return true;
	}

	mCookies.set(mCookie, ';');
//	SLOG("Total Cookie:%d", mCookies.length());
/*	for(int i=0; i<mCookies.length(); i++)
	{
		CQuery *
			Query = (CQuery *)mCookies.get(i);
		SLOG("Cookie %d:[%s]/[%s]", i, Query->get_name(), Query->get_value());
	}
*/

	char *
		IDString = mCookies.get_value("ID_STRING");
//	SLOG("get id string (%s)", IDString);

	if (!IDString)
	{
		SLOG("Could not found ID_STRING");
		portal_login_message_page(GETTEXT("First of all, you must log in the portal."));
		return true;
	}

	CString 
		String;
	String = decrypt_id_string(IDString);
//	SLOG("get decrypt id string (%s)", (char*)String);

	if (String.length() == 0)
	{
		CString 
			TString;
			
		TString = mCookies.get(';');

		set_cookie(TString);

		SLOG("Could not decrypt ID_STRING");
		portal_login_message_page(GETTEXT("We are sorry. Your account has been inactive for a long time. You must log in again."));

		return true;
	}


//	SLOG("Success decrypt ID_STRING(%s)", (char*)String);

	mIDString.set(String, '&');

	// get info from ID_STRING
	char *
		PortalID = mIDString.get_value("ID");
	if (PortalID)
	{
		mPortalID = atoi(PortalID);
	}
	else 
	{
		mPortalID = -1;
	}
	if (mPortalID == 0) mPortalID = -1;

	if (mPortalID < 0) 
	{
		portal_login_message_page(GETTEXT("First of all, you must log in the portal."));
		return true;
	}

	char *
		ASString = mCookies.get_value("AS_STRING");

//	SLOG("get as string (%s)", (char*)ASString);

	if (ASString)
	{
		CString DASString = decrypt_id_string(ASString);

//		SLOG("get decrypt as string:%s", (char*)DASString);
		if (DASString.length())
		{
//			SLOG("Success decrypt AS_STRING(%s)", (char*)DASString);

			mASString.set(DASString, '&');

			char*
				GameID = mASString.get_value("GAME_ID");
//			SLOG("Get game id :%s", GameID);
			if (GameID)
				mGameID = atoi(GameID);
			else 
				mGameID = -1;

			CPlayer *
				Player = PLAYER_TABLE->get_by_game_id(mGameID);
			if (Player)
			{
				if (Player->is_dead() == false)
				{
					mASString.set_value("COUNCIL_ID", 
						(char*)format("%d", Player->get_council()->get_id()));
					mASString.set_value("COUNCIL_NAME", 
						(char*)format("%s", Player->get_council()->get_name()));
				}
			}
		}
	}

	CString *
		Page = mPageStation->get_page(*this);

	if (Page && Page->length() > 0)
	{
		CString
			TString;

		set_content(*Page);

		// encrypt ID_STRING
		String = mIDString.get('&');

//		SLOG("Success encrypt ID_STRING(%s)", (char*)String);

		if (String.length())
		{
			encrypt(CRYPT_CURRENT_KEY, 
					(char*)String, TString);
			mCookies.replace_value("ID_STRING", 
							(char*)TString);
		}

		if (mASString.length())
		{
			CString AString = mASString.get('&');

			if (AString.length())
			{
				encrypt(CRYPT_CURRENT_KEY, 
						(char*)AString, TString);
				mCookies.set_value("AS_STRING", 
								(char*)TString);
			}
		}

		TString = mCookies.get(';');

		set_cookie(TString);

		send_terminate();
	}
	else
	{
		SLOG("ERROR : The requested page was not found!");
		message_page(GETTEXT("The requested page was not found. Please ask Archspace Development Team."));
		return true;
	}

	return true;
}
