#include <libintl.h>
#include "../../pages.h"

bool
CPageProject::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	if (!aPlayer->can_buy_any_project())
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You purchased all projects that you can gain."));
		return output("domestic/project_error.html");
	}

	ITEM("CURRENT_PP_MESSAGE",
			(char *)format(GETTEXT("Currently there is %1$s PP in your safe."),
							dec2unit(aPlayer->get_production())));

	ITEM("SELECT_PROJECT_MESSAGE",
			GETTEXT("Select the project you want to gain from the list below"));

	ITEM("PROJECT_SELECT", aPlayer->project_list_html());

//	system_log("end page handler %s", get_name());

	return output("domestic/project.html");
}
