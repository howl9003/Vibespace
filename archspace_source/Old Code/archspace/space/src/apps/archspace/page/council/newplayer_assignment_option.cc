#include <libintl.h>
#include "../../pages.h"

bool
CPageNewplayerAssignmentOption::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	ARE_YOU_SPEAKER(aPlayer);

	ITEM("MENU_TITLE", GETTEXT("New Player Assignment Option"));

	QUERY("NEW_PLAYER", NewPlayer);

	bool NewAssign = false;
	if (!NewPlayer) 
		NewAssign = false;
	else {
		if (!strcmp(NewPlayer, "ON")) 
			NewAssign = true;
	}

	if (NewAssign)
	{
		ITEM("CURRENT_STATUS_MESSAGE",
				 GETTEXT("New player assignment option is now on.<BR>"
							"Your council accepts new player assignment."));
	} else {
		ITEM("CURRENT_STATUS_MESSAGE",
				 GETTEXT("New player assignment option is now off.<BR>"
							"Your council rejects new player assignment."));
	}

	aPlayer->get_council()->set_auto_assign(NewAssign);

//	system_log("end page handler %s", get_name());

	return output("council/newplayer_assignment_option.html");

}

