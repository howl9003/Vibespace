#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageBroadcastResult::handler(CPlayer *aPlayer)
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

	QUERY("BROADCAST_TYPE", BroadcastTypeString);
	if (BroadcastTypeString == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't specify how to broadcast a message."));

		return output("admin/admin_error.html");
	}

	if (strcmp(BroadcastTypeString, "MESSAGE") && strcmp(BroadcastTypeString, "MAIL"))
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You specified a wrong way to broadcast a message."));

		return output("admin/admin_error.html");
	}

	QUERY("BROADCAST_MESSAGE", BroadcastMessageString);
	if (BroadcastMessageString == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't input a broadcast message."));

		return output("admin/admin_error.html");
	}

	if (!strcmp(BroadcastTypeString, "MESSAGE"))
	{
		for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
		{
			CPlayer *
				Player = (CPlayer *)PLAYER_TABLE->get(i);
			if (Player->get_game_id() == EMPIRE_GAME_ID) continue;

			CDiplomaticMessage *
				Message = new CDiplomaticMessage();
			Message->initialize(CDiplomaticMessage::TYPE_NORMAL, EMPIRE, Player, BroadcastMessageString);
			Player->add_diplomatic_message(Message);

			Message->CStore::type(QUERY_INSERT);
			STORE_CENTER->store(*Message);
		}

		ITEM("RESULT_MESSAGE", GETTEXT("You sent the broadcast message to all players."));
	}
	else if (!strcmp(BroadcastTypeString, "MESSAGE"))
	{
		ITEM("RESULT_MESSAGE", GETTEXT("You sent the broadcast message to all players."));
	}

	ITEM("BROADCAST_MESSAGE", BroadcastMessageString);

//	system_log("end page handler %s", get_name());

	return output("admin/broadcast_result.html");
}
