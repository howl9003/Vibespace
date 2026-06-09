#include <libintl.h>
#include "../pages.h"
#include "../ending.h"
#include "../archspace.h"
#include "../game.h"

bool
CPagePersonalEnding::handler(CPlayer* aPlayer)
{
	QUERY("ENDING_ID", endingIDstr);
	int
		endingID = as_atoi(endingIDstr);
	CPersonalEnding *
		ending = PERSONAL_ENDING_TABLE->get_by_id(endingID);
	if (ending == NULL)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("That kind of Personal Ending doesn't exist."));
		return output("personal_ending_error.html");
	}

	CPurchasedProjectList *
		PurchasedProjectList = aPlayer->get_purchased_project_list();
	CPurchasedProject *
		endingProject = PurchasedProjectList->get_by_id(ending->get_project_id());
	if (endingProject == NULL)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You didn't achived that Personal Ending."));
		return output("personal_ending_error.html");
	}

	ITEM("IMAGE", " ");
	ITEM("TITLE", " ");

	ITEM("DESCRIPTION", endingProject->get_description());

	return output("personal_ending.html");
}
