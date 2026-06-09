#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPagePlayerConfirm::handler(CPlayer *aPlayer)
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

	QUERY("BY_WHAT", ByWhatString);
	if (!ByWhatString)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You didn't specify the way how to search a player."));
		return output("admin/admin_error.html");
	}

	CPlayer *
		Player = NULL;

	if (!strcmp(ByWhatString, "PLAYER_ID"))
	{
		QUERY("PLAYER_ID", PlayerIDString);

		int
			PlayerID = as_atoi(PlayerIDString);
		Player = PLAYER_TABLE->get_by_game_id(PlayerID);
	}
	else if (!strcmp(ByWhatString, "PLAYER_NAME"))
	{
		QUERY("PLAYER_NAME", PlayerNameString);

		if (!PlayerNameString)
		{
			ITEM("ERROR_MESSAGE",
					GETTEXT("You didn't enter a player name."));
			return output("admin/admin_error.html");
		}

		Player = PLAYER_TABLE->get_by_name(PlayerNameString);
	}
	else
	{
		ITEM("ERROR_MESSAGE", GETTEXT("The way how to search a player was wrong."));
		return output("admin/admin_error.html");
	}

	if (Player == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("That account doesn't exist."));
		return output("admin/admin_error.html");
	}
	else if (Player->get_game_id() == EMPIRE_GAME_ID)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You can't inspect the Empire."));
		return output("admin/admin_error.html");
	}

	ITEM("CONFIRM_MESSAGE",
			(char *)format(GETTEXT("Are you sure this player %1$s?"),
							Player->get_nick()));

	ITEM("PORTAL_ID", Player->get_portal_id());

//	system_log("end page handler %s", get_name());

	return output("admin/player_confirm.html");
}
