#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageBlackMarketControl::handler(CPlayer *aPlayer)
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

	ITEM("STRING_CREATE_NEW_TECH", GETTEXT("Create New Tech"));
	ITEM("STRING_CREATE_NEW_FLEET", GETTEXT("Create New Fleet"));
	ITEM("STRING_CREATE_NEW_ADMIRAL", GETTEXT("Create New Admiral"));
	ITEM("STRING_CREATE_NEW_PROJECT", GETTEXT("Create New Project"));
	ITEM("STRING_CREATE_NEW_PLANET", GETTEXT("Create New Planet"));

	ITEM("STRING_TO_ADMIN_TOOL_MAIN", GETTEXT("To Admin Tool Main"));

//	system_log("end page handler %s", get_name());

	return output("admin/black_market_control.html");
}
