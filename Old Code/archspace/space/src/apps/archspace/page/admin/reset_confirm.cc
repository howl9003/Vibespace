#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageResetConfirm::handler(CPlayer *aPlayer)
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

	QUERY("CONQUEROR_NAME", ConquerorNameString);
	if (ConquerorNameString == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't specify the conqueror's name."));
		return output("admin/admin_error.html");
	}

	QUERY("CONQUEROR_COUNCIL_NAME", ConquerorCouncilNameString);
	if (ConquerorCouncilNameString == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't specify a name of the conqueror's council."));
		return output("admin/admin_error.html");
	}

	ITEM("CONFIRM_MESSAGE",
			(char *)format(GETTEXT("Are you sure the server has to be reset? The Empire will be conquered by %1$s and the players will no more able to play the game."),
							ConquerorNameString));

	ITEM("CONQUEROR_NAME", ConquerorNameString);
	ITEM("CONQUEROR_COUNCIL_NAME", ConquerorCouncilNameString);

//	system_log("end page handler %s", get_name());

	return output("admin/reset_confirm.html");
}
