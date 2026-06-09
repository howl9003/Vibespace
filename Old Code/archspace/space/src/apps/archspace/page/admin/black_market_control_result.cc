#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPageBlackMarketControlResult::handler(CPlayer *aPlayer)
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

	static CString
		Result;
	Result.clear();

	QUERY("BY_WHAT", ByWhatString);
	if (ByWhatString == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("ByWhatString is NULL."));

		return output("admin/blackmarket_control_error.html");
	}

	if (!strcmp(ByWhatString, "CREATE_NEW_TECH"))
	{
		BLACK_MARKET->create_new_tech();
		ITEM("RESULT_MESSAGE", GETTEXT("A new tech has been created."));
	}
	else if (!strcmp(ByWhatString, "CREATE_NEW_FLEET"))
	{
		BLACK_MARKET->create_new_fleet();
		ITEM("RESULT_MESSAGE", GETTEXT("A new fleet has been created."));
	}
	else if (!strcmp(ByWhatString, "CREATE_NEW_ADMIRAL"))
	{
		BLACK_MARKET->create_new_admiral();
		ITEM("RESULT_MESSAGE", GETTEXT("A new admiral has been created."));
	}
	else if (!strcmp(ByWhatString, "CREATE_NEW_PROJECT"))
	{
		BLACK_MARKET->create_new_project();
		ITEM("RESULT_MESSAGE", GETTEXT("A new project has been created."));
	}
	else if (!strcmp(ByWhatString, "CREATE_NEW_PLANET"))
	{
		BLACK_MARKET->create_new_planet();
		ITEM("RESULT_MESSAGE", GETTEXT("A new planet has been created."));
	}
	else
	{
		ITEM("ERROR_MESSAGE", GETTEXT("ByWhatString seems wrong. Please try again."));

		return output("admin/blackmarket_control_error.html");
	}

//	system_log("end page handler %s", get_name());

	return output("admin/black_market_control_result.html");
}
