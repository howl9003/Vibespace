#include <libintl.h>
#include "../../pages.h"
#include <cstdlib>

bool
CPageCouncilSendDiplomaticMessageWrite::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	ARE_YOU_SPEAKER(aPlayer);

	ITEM("MENU_TITLE", GETTEXT("Council Diplomacy - Send Message"));

	QUERY("COUNCIL_ID", CouncilIDString);
	int
		CouncilID = as_atoi(CouncilIDString);
	CCouncil *
		PlayerCouncil = aPlayer->get_council();
	CCouncil *
		Council = PlayerCouncil->relation_council(CouncilID);

	CHECK(!Council, 
			GETTEXT("We don't share any clusters with this council."));

	ITEM("STRING_SENDER", GETTEXT("Sender"));
	ITEM("STRING_TO", GETTEXT("To"));
	ITEM("STRING_MESSAGE_TYPE", GETTEXT("Message Type"));
	ITEM("STRING_COMMENT", GETTEXT("Comment"));

	ITEM("FROM", PlayerCouncil->get_name()); 
	ITEM("TO", format("<A HREF=\"inspect_other_council_result.as?"
						"COUNCIL_ID=%d\">%s</A>",
							CouncilID, Council->get_nick()));

	ITEM("MESSAGE_TYPE",
			PlayerCouncil->council_message_select_html(Council));

	ITEM("COUNCIL_ID", format(
			"<INPUT TYPE=hidden NAME=COUNCIL_ID VALUE=%d>\n",
				CouncilID));

//	system_log("end page handler %s", get_name());

	return output("council/send_diplomatic_message_write.html");
}

