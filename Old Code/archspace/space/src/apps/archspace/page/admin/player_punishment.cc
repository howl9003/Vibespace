#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPagePlayerPunishment::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name());

	CQueryList &
		IDString = CONNECTION->id_string();
	char *
		Admin = IDString.get_value("IS_ADMIN");

	if (!Admin)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/player_error.html");
	}
	if (strcmp(Admin, "YES"))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/player_error.html");
	}

	CPlayer *
		Player = PLAYER_TABLE->get_by_portal_id(mPortal.get_id());
	if (!Player)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("This account doesn't have any characters."));
		return output("admin/player_error.html");

	}

	ITEM("STRING_IMPERIAL_RETRIBUTION", GETTEXT("Imperial Retribution"));
	ITEM("STRING_REMOVE_IMPERIAL_RETRIBUTION", GETTEXT("Remove Imperial Retribution"));

	ITEM("STRING_SKIP_TURN_PUNISHMENT", GETTEXT("Skip Turn Punishment"));
	ITEM("STRING_TURN_S_", GETTEXT("Turn(s)"));

	ITEM("STRING_TO_MAKE_THIS_PLAYER_DEAD", GETTEXT("To Make This Player Dead"));
	ITEM("STRING_DELETE_THIS_PLAYER", GETTEXT("Delete This Player"));
	ITEM("STRING_DELETE_PORTAL_DATA", GETTEXT("Delete Portal Data"));

//	system_log("end page handler %s", get_name());

	return output("admin/player_punishment.html");
}

