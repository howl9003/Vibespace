#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPagePlayerSendMessage::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	CQueryList &
		IDString = CONNECTION->id_string();
	char *
		Admin = IDString.get_value("IS_ADMIN");

	if (!Admin)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}
	if (strcmp(Admin, "YES"))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}

	CPlayer *
		Player = PLAYER_TABLE->get_by_portal_id(mPortal.get_id());
	if (Player == NULL)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("The person whose portal ID is %s doesn't have a character."),
						dec2unit(mPortal.get_id())));
		return output("admin/admin_error.html");
	}

	ITEM("STRING_SENDER", GETTEXT("Sender"));
	ITEM("SENDER", GETTEXT("GM"));

	ITEM("STRING_TO", GETTEXT("To"));
	ITEM("TO", Player->get_nick());

	ITEM("STRING_COMMENT", GETTEXT("Comment"));

//	system_log("end page handler %s", get_name());

	return output("admin/player_send_message.html");
}
