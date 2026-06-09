#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPageCouncilMessage::handler(CPlayer *aPlayer)
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

	if (!strcmp(ByWhatString, "COUNCIL_MAIL_RECEIVER_ID"))
	{
		QUERY("COUNCIL_MAIL_RECEIVER_ID", CouncilMailReceiverIDString);

		int
			CouncilID = as_atoi(CouncilMailReceiverIDString);
		CCouncil *
			Council = COUNCIL_TABLE->get_by_id(CouncilID);
		if (Council == NULL)
		{
			ITEM("ERROR_MESSAGE",
					GETTEXT("That council doesn't exist."));
			return output("admin/council_message_error.html");
		}

		Title.format(GETTEXT("The messages received by the player %1$s"),
						Council->get_nick());

		List = Council->get_message_list_html(0, true);

		if (List.length() == 0)
		{
			List = "<TR>\n";
			List += "<TD CLASS=\"maintext\" ALIGN=CENTER COLSPAN=3>\n";
			List.format(GETTEXT("No council messages that the council %1$s has sent exist."),
						Council->get_nick());
			List += "</TD>\n";
			List += "</TR>\n";
		}
	}
	else if (!strcmp(ByWhatString, "COUNCIL_MAIL_SENDER_ID"))
	{
		QUERY("COUNCIL_MAIL_SENDER_ID", CouncilMailSenderIDString);

		int
			CouncilID = as_atoi(CouncilMailSenderIDString);
		CCouncil *
			Council = COUNCIL_TABLE->get_by_id(CouncilID);
		if (Council == NULL)
		{
			ITEM("ERROR_MESSAGE",
					GETTEXT("That council doesn't exist."));
			return output("admin/council_message_error.html");
		}

		Title.format(GETTEXT("The messages sent by the council %1$s"),
						Council->get_nick());

		CString
			List = CCouncil::get_message_list_by_sender_html(Council, true);

		if (List.length() == 0)
		{
			List = "<TR>\n";
			List += "<TD CLASS=\"maintext\" ALIGN=CENTER COLSPAN=3>\n";
			List.format(GETTEXT("No council messages that the council %1$s has sent exist."),
						Council->get_nick());
			List += "</TD>\n";
			List += "</TR>\n";
		}
	}
	else
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("The way how to search a council was wrong."));
		return output("admin/admin_error.html");
	}

	ITEM("TITLE", (char *)Title);
	ITEM("MESSAGE_LIST", (char *)List);

//	system_log("end page handler %s", get_name());

	return output("admin/council_message.html");
}

