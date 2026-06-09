#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPagePlayerMessageDeleteResult::handler(CPlayer *aPlayer)
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
		return output("admin/admin_error.html");
	}
	if (strcmp(Admin, "YES"))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}

	QUERY("PLAYER_GAME_ID", PlayerGameIDString);
	int
		PlayerGameID = as_atoi(PlayerGameIDString);
	CPlayer *
		Player = PLAYER_TABLE->get_by_game_id(PlayerGameID);
	if (Player == NULL)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("There is no such a player with ID #%1$s."),
								dec2unit(PlayerGameID)));
		return output("admin/admin_error.html");

	}

	QUERY("MESSAGE_ID", MessageIDString);
	int
		MessageID = as_atoi(MessageIDString);

	CDiplomaticMessageBox *
		MessageBox = Player->get_diplomatic_message_box();
	CDiplomaticMessage *
		Message = MessageBox->get_by_id(MessageID);

	if (Message == NULL)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("This player doesn't have that message(#%1$d)."),
								MessageID));
		return output("admin/admin_error.html");
	}

	Message->type(QUERY_DELETE);
	STORE_CENTER->store(*Message);
	MessageBox->remove_diplomatic_message(MessageID);

	ITEM("RESULT_MESSAGE",
			(char *)format(GETTEXT("The message(#%1$d) has been removed successfully."),
							MessageID));

//	system_log("end page handler %s", get_name());

	return output("admin/player_message_delete_result.html");
}

