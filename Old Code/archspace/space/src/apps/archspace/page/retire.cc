#include <libintl.h>
#include "../pages.h"
#include "../archspace.h"
#include "../game.h"

bool
CPageRetire::handle(CConnection &aConnection)
{
//	system_log("start page handler %s", get_name());

	if (!CPageCommon::handle(aConnection)) return false;

	int
		PortalID = CONNECTION->get_portal_id();
	CPlayer *
		Player = PLAYER_TABLE->get_by_portal_id(PortalID);
	if (Player == NULL)
	{
		ITEM("EROR_MESSAGE",
				GETTEXT("You don't have any character. Click <A HREF=/archspace/create.as>here</A> to create a new character."));
		return output("error.html");
	}

	ITEM("CONFIRM_MESSAGE",
			GETTEXT("Are you sure that you will retire?"));

//	system_log("end page handler %s", get_name());

	return output("retire.html");
}
