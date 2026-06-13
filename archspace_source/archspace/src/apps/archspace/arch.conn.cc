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

    	// Modern auth: resolve the session cookie set by the new email/password
    	// auth service (web/auth) against our own `sessions` table, instead of the
    	// legacy phpBB `asbb_sessions` lookup. The account id plays the former
    	// portal-id role.
    	char * PHPSessionID = mCookies.get_value("as_session");
	if (!PHPSessionID)
	{
		portal_login_message_page(GETTEXT("First of all, you must log in."));
		return true;
	}


 	CMySQL
		*MySQL=NULL;
    	while (!(MySQL = MYSQL_POOL->get_connection()))
         	;

 	CString
		Query;
    	Query.clear();
	Query.format( "SELECT account_id "
			"FROM sessions WHERE id='%s' AND expires > UNIX_TIMESTAMP() LIMIT 1",
			PHPSessionID );
	MySQL->query( (char*)Query );
	MySQL->use_result();
	Query.clear();

 	if ( MySQL->has_result() && MySQL->fetch_row())
	{
        	MYSQL_ROW aRow = MySQL->row();
        	char *aPortalID = aRow[0];

        	if (!aPortalID || atoi(aPortalID) <= 0)
	    	{
		   portal_login_message_page(GETTEXT("First of all, you must log in."));
		   MYSQL_POOL->release_connection(MySQL);
		   return true;
	    	}
	    	mPortalID = atoi(aPortalID);
        	MySQL->free_result();
	}
	else
	{
	   portal_login_message_page(GETTEXT("First of all, you must log in."));
           MYSQL_POOL->release_connection(MySQL);
           return true;
    	}
    
    MYSQL_POOL->release_connection(MySQL);	

	CPlayer* Player = PLAYER_TABLE->get_by_portal_id(mPortalID);

    if(Player) mGameID = Player->get_game_id();

	// Turn-news accumulation (Option A): the main page (main.as) is auto-
	// refreshed and renders turn-news read-only, so unseen items keep
	// accumulating across refreshes. We consume (acknowledge) that news only
	// when the player navigates AWAY from the main page to any other content
	// page -- at which point they've had the chance to read it.
	if (Player)
	{
		const char *URI = get_uri();
		if (URI && *URI)
		{
			const char *Base = strrchr(URI, '/');
			Base = Base ? Base + 1 : URI;

			// main.as is where the (read-only) news feed is shown: loading it
			// means the player is now seeing whatever has accumulated. Remember
			// that so we consume it only once they actually leave.
			if (strcmp(Base, "main.as") == 0)
			{
				Player->set_main_news_viewed();
			}
			// menu.as is the auto-refreshing left navbar; death_* are terminal
			// status screens; events.as is the SSE push fingerprint, polled
			// every couple of seconds. NONE of these count as "navigating away"
			// (and consuming on events.as would clear the feed before the
			// player ever sees it), so leave the news untouched on them.
			else if (strcmp(Base, "menu.as")       != 0 &&
			         strcmp(Base, "events.as")     != 0 &&
			         strcmp(Base, "death_main.as") != 0 &&
			         strcmp(Base, "death_menu.as") != 0)
			{
				// A real content page = navigating away. Consume the feed ONLY
				// if the player has loaded main.as since the last acknowledge;
				// otherwise it keeps stacking. Without this guard, clicking
				// through other pages between turns silently acknowledged news
				// the player never saw, so the main page later showed blank.
				if (Player->main_news_viewed())
				{
					Player->acknowledge_news();
					Player->clear_main_news_viewed();
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
