#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPagePlayerMessageRead::handler(CPlayer *aPlayer)
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
		PlayerID = as_atoi(PlayerGameIDString);
	CPlayer *
		Player = PLAYER_TABLE->get_by_game_id(PlayerID);
	if (Player == NULL)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("There is no such a player with ID #%1$s."),
								dec2unit(PlayerID)));
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

	ITEM("STRING_NO_", GETTEXT("No."));
	ITEM("STRING_SENDER", GETTEXT("Sender"));
	ITEM("STRING_DATE", GETTEXT("Date"));
	ITEM("STRING_MESSAGE_TYPE", GETTEXT("Message Type"));
	ITEM("STRING_REPLY_STATUS", GETTEXT("Reply Status"));
	ITEM("STRING_COMMENT", GETTEXT("Comment"));

	ITEM("NO_", Message->get_id());
	if (Message->get_sender() == NULL)
	{
		ITEM("FROM", GETTEXT("N/A"));
	}
	else
	{
		ITEM("FROM", Message->get_sender()->get_nick());
	}
	ITEM("DATE", Message->get_time_string());
	ITEM("MESSAGE_TYPE", Message->get_type_description());
	ITEM("REPLY_STATUS", Message->get_status_description());

	CString
		Comment;
	Comment = Message->get_content();
	Comment.htmlspecialchars();
	Comment.nl2br();
	ITEM("COMMENT", (char *)Comment);

//	system_log("end page handler %s", get_name());

	return output("admin/player_message_read.html");
}

