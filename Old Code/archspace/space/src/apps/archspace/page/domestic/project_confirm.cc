#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPageProjectConfirm::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	CProjectList *
		ProjectList = aPlayer->get_project_list();

	CCommandSet
		ProjectSet;
	ProjectSet.clear();

	for (int i=0 ; i<ProjectList->length() ; i++)
	{
		CString
			QueryVar;
		QueryVar.clear();
		QueryVar.format("PROJECT%d", i);

		QUERY((char *)QueryVar, ProjectString);
		if (!ProjectString) continue;
		if (strcasecmp(ProjectString, "ON")) continue;

		CProject *
			Project = (CProject *)ProjectList->get(i);

		if (!aPlayer->know_tech(Project->get_prerequisite()))
		{
			CTech *
				Prerequisite = TECH_TABLE->get_by_id(Project->get_prerequisite());
			ITEM("ERROR_MESSAGE",
					(char *)format(
							GETTEXT("You don't have the tech %1$s for the project %2$s."),
							Prerequisite->get_name_with_level(),
							Project->get_name()));
			return output("domestic/project_error.html");
		}

		if (!Project->is_for_race(aPlayer->get_race()))
		{
			ITEM("ERROR_MESSAGE",
					(char *)format(GETTEXT("Project %1$d can't be purchased by your race."),
									Project->get_name()));
			return output("domestic/project_error.html");
		}

		ProjectSet += i;
	}

	if (ProjectSet.is_empty())
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("There are no projects you chose."));

		return output("domestic/project_error.html");
	}

	ITEM("SELECTED_PROJECTS_MESSAGE",
			GETTEXT("You have selected the following project(s) :"));

	ITEM("SELECTED_PROJECTS_INFORMATION",
			ProjectList->selected_project_information_html(ProjectSet));

	ITEM("OVERALL_RESULT_MESSAGE",
			GETTEXT("The overall result would be like :"));

	ITEM("EFFECT_RESULT",
			ProjectList->selected_project_effect_html(aPlayer, ProjectSet));

	ITEM("CONFIRM_MESSAGE",
			GETTEXT("Once you gain the project(s), you cannot cancel the effect of the project(s).<BR>"
					"Are you sure you want to take the project(s)?"));

	ITEM("PROJECT_SET", ProjectSet.get_string());

//	system_log("end page handler %s", get_name());

	return output("domestic/project_confirm.html");
}
