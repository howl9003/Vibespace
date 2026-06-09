#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageInspect::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	CQueryList &
		IDString = CONNECTION->id_string();
	char *
		IsAdmin = IDString.get_value("IS_ADMIN");

	if (!IsAdmin)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}
	if (strcmp(IsAdmin, "YES"))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}

	if (mPortal.get_id() != 0)
	{
		mPortal.set_id(0);
		mPortal.set_name(NULL);
		mPortal.set_password(NULL);
		mPortal.set_email(NULL);
		mPortal.set_first_name(NULL);
		mPortal.set_last_name(NULL);
		mPortal.set_icq(0);
		mPortal.set_gender(NULL);
		mPortal.set_age(0);
		mPortal.set_country(NULL);
		mPortal.set_how_know_us(NULL);
		mPortal.set_created_time(0);
		mPortal.set_last_login(0);
		mPortal.set_is_admin(false);
	}

	ITEM("PLAYER_SELECT_MESSAGE",
			GETTEXT("Please enter a name of the player who you want to investigate."));

	ITEM("STRING_ACCOUNT_ID", GETTEXT("Account ID"));
	ITEM("STRING_ACCOUNT_NAME", GETTEXT("Account Name"));
	ITEM("STRING_PLAYER_ID", GETTEXT("Player ID"));
	ITEM("STRING_PLAYER_NAME", GETTEXT("Player Name"));
	ITEM("STRING_PLAYER_MAIL_RECEIVER_ID", GETTEXT("Player Mail Receiver ID"));
	ITEM("STRING_PLAYER_MAIL_SENDER_ID", GETTEXT("Player Mail Sender ID"));

	ITEM("STRING_COUNCIL_ID", GETTEXT("Council ID"));
	ITEM("STRING_COUNCIL_NAME", GETTEXT("Council Name"));
	ITEM("STRING_COUNCIL_MAIL_RECEIVER_ID", GETTEXT("Council Mail Receiver ID"));
	ITEM("STRING_COUNCIL_MAIL_SENDER_ID", GETTEXT("Council Mail Sender ID"));

	ITEM("STRING_ACTION_LOG", GETTEXT("Action Log"));
	ITEM("STRING_NEW_ACCOUNT_LOG", GETTEXT("New Account Log"));
	ITEM("STRING_NEW_PLAYER_LOG", GETTEXT("New Player Log"));
	ITEM("STRING_DEAD_PLAYER_LOG", GETTEXT("Dead Player Log"));

	ITEM("STRING_SEE_LOG_MESSAGE",
			GETTEXT("To search any kind of action, choose a kind and date."));

	ITEM("STRING_YEAR", GETTEXT("Year"));
	ITEM("STRING_MONTH", GETTEXT("Month"));
	ITEM("STRING_DAY", GETTEXT("Day"));

	ITEM("STRING_TO_COUNCIL_CONTROL", GETTEXT("To Council Control"));
	ITEM("STRING_TO_BLACK_MARKET_CONTROL", GETTEXT("To Black Market Control"));

//	system_log("end page handler %s", get_name());

	return output("admin/inspect.html");
}
