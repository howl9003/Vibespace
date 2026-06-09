#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPagePlayerPunishmentDeleteCharacterConfirm::handler(CPlayer *aPlayer)
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

	QUERY("BY_WHAT", ByWhatString);
	if (!ByWhatString)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("By What String is NULL."
						" Maybe you should reload portal information again."));
		return output("admin/player_error.html");
	}

	if (!strcmp(ByWhatString, "TO_MAKE_THIS_PLAYER_DEAD"))
	{
		ITEM("CONFIRM_MESSAGE",
				(char *)format(GETTEXT("Are you sure that you will make the player %1$s dead? You can't undo this operation."), Player->get_nick()));
		ITEM("BY_WHAT", "TO_MAKE_THIS_PLAYER_DEAD");
		return output("admin/player_punishment_delete_character_confirm.html");
	}
	else if (!strcmp(ByWhatString, "DELETE_THIS_PLAYER"))
	{
		ITEM("CONFIRM_MESSAGE",
				(char *)format(GETTEXT("Are you sure that you will delete the player %1$s? You can't undo this operation."), Player->get_nick()));
		ITEM("BY_WHAT", "DELETE_THIS_PLAYER");
		return output("admin/player_punishment_delete_character_confirm.html");
	}
	else if (!strcmp(ByWhatString, "DELETE_PORTAL_DATA"))
	{
		ITEM("CONFIRM_MESSAGE",
				(char *)format(GETTEXT("Are you sure that you will delete the player's portal data as well as the player's character? You can't undo this operation."), Player->get_nick()));
		ITEM("BY_WHAT", "DELETE_PORTAL_DATA");
		return output("admin/player_punishment_delete_character_confirm.html");
	}
	else
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You have selected a wrong command."));
		return output("admin/player_punishment_error.html");
	}

	//	system_log("end page handler %s", get_name());
}

