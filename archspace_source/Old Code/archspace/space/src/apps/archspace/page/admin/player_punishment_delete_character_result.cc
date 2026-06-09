#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPagePlayerPunishmentDeleteCharacterResult::handler(CPlayer *aPlayer)
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

	CString
		PlayerNick;
	PlayerNick = Player->get_nick();

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
		Player->set_dead(
				(char *)format(GETTEXT("%1$s has been set dead by GM's punishment."),
								(char *)PlayerNick));

		ITEM("RESULT_MESSAGE",
				(char *)format(GETTEXT("%1$s has been set dead."), (char *)PlayerNick));

		ADMIN_TOOL->add_punishment_log(
				(char *)format("The player %s has been set dead by GM's punishment.",
								(char *)PlayerNick));

		return output("admin/player_punishment_delete_character_result.html");
	}
	else if (!strcmp(ByWhatString, "DELETE_THIS_PLAYER"))
	{
		Player->remove_from_database();
		Player->remove_from_memory();
		Player->remove_news_files();
		PLAYER_TABLE->remove_player(Player->get_game_id());

		Player->set_dead(
				(char *)format(GETTEXT("%1$s has been deleted by GM's punishment."),
								(char *)PlayerNick));

		ITEM("RESULT_MESSAGE",
				(char *)format(GETTEXT("%1$s has been deleted."), (char *)PlayerNick));

		ADMIN_TOOL->add_punishment_log(
				(char *)format("The player %s has been deleted by GM's punishment.",
								(char *)PlayerNick));

		return output("admin/player_punishment_delete_character_result.html");
	}
	else if (!strcmp(ByWhatString, "DELETE_PORTAL_DATA"))
	{
		return output("admin/player_punishment_delete_character_result.html");
	}
	else
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You have selected a wrong command."));
		return output("admin/player_punishment_error.html");
	}

	//	system_log("end page handler %s", get_name());
}

