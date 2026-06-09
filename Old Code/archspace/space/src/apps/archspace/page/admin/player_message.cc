#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPagePlayerMessage::handler(CPlayer *aPlayer)
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

	QUERY("BY_WHAT", ByWhatString);
	if (!ByWhatString)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You didn't specify the way how to search messages."));
		return output("admin/admin_error.html");
	}

	static CString
		Title,
		List;
	Title.clear();
	List.clear();

	if (!strcmp(ByWhatString, "PLAYER_MAIL_RECEIVER_ID"))
	{
		QUERY("PLAYER_MAIL_RECEIVER_ID", PlayerMailReceiverIDString);

		int
			PlayerID = as_atoi(PlayerMailReceiverIDString);
		CPlayer *
			Player = PLAYER_TABLE->get_by_game_id(PlayerID);
		if (Player == NULL)
		{
			ITEM("ERROR_MESSAGE",
					GETTEXT("That player doesn't exist."));
			return output("admin/admin_error.html");
		}

		Title.format(GETTEXT("The messages received by the player %1$s"),
						Player->get_nick());

		List = Player->get_message_list_html(0, true);

		if (List.length() == 0)
		{
			List = "<TR>\n";
			List += "<TD CLASS=\"maintext\" ALIGN=CENTER COLSPAN=3>\n";
			List.format(GETTEXT("No diplomatic messages that the player %1$s has sent exist."),
						Player->get_nick());
			List += "</TD>\n";
			List += "</TR>\n";
		}
	}
	else if (!strcmp(ByWhatString, "PLAYER_MAIL_SENDER_ID"))
	{
		QUERY("PLAYER_MAIL_SENDER_ID", PlayerMailSenderIDString);

		int
			PlayerID = as_atoi(PlayerMailSenderIDString);
		CPlayer *
			Player = PLAYER_TABLE->get_by_game_id(PlayerID);
		if (Player == NULL)
		{
			ITEM("ERROR_MESSAGE",
					GETTEXT("That player doesn't exist."));
			return output("admin/admin_error.html");
		}

		Title.format(GETTEXT("The messages sent by the player %1$s"),
						Player->get_nick());

		CString
			List = CPlayer::get_message_list_by_sender_html(Player, true);

		if (List.length() == 0)
		{
			List = "<TR>\n";
			List += "<TD CLASS=\"maintext\" ALIGN=CENTER COLSPAN=3>\n";
			List.format(GETTEXT("No diplomatic messages that the player %1$s has sent exist."),
						Player->get_nick());
			List += "</TD>\n";
			List += "</TR>\n";
		}
	}
	else
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("The way how to search a player was wrong."));
		return output("admin/admin_error.html");
	}

	ITEM("TITLE", (char *)Title);
	ITEM("MESSAGE_LIST", (char *)List);

//	system_log("end page handler %s", get_name());

	return output("admin/player_message.html");
}

