#include <libintl.h>
#include "../../pages.h"

bool
CPageProjectResult::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	QUERY("PROJECT_SET", ProjectSetString);
	if (!ProjectSetString)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("There are no projects you chose."));

		return output("domestic/project_error.html");
	}

	CCommandSet
		ProjectSet;
	ProjectSet.clear();

	ProjectSet.set_string(ProjectSetString);

	if (ProjectSet.is_empty())
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("There are no projects you chose."));

		return output("domestic/project_error.html");
	}

	CProjectList *
		ProjectList = aPlayer->get_project_list();

	int
		Cost = 0;
	int
		CurrentProjectNumber = ProjectList->length();
	bool
		AnyProject = false;

	for (int i=CurrentProjectNumber-1 ; i>=0 ; i--)
	{
		if (!ProjectSet.has(i)) continue;

		CProject *
			Project = (CProject *)ProjectList->get(i);

		if (!aPlayer->know_tech(Project->get_prerequisite()))
		{
			ITEM("ERROR_MESSAGE", GETTEXT("You didn't research required technology."));
			return output("domestic/project_error.html");
		}

		if (!Project->is_for_race(aPlayer->get_race()))
		{
			ITEM("ERROR_MESSAGE",
					(char *)format(GETTEXT("You didn't research required technology."),
									Project->get_name()));
			return output("domestic/project_error.html");
		}

		AnyProject = true;

		if (Project->get_type() == TYPE_PLANET)
		{
			int
				PlanetNumber = aPlayer->get_planet_list()->length();
			if (MAX_PLAYER_PP - Cost < Project->get_cost()*PlanetNumber)
			{
				ITEM("ERROR_MESSAGE",
						GETTEXT("The total price of the project(s) you have chosen exceeds the limit of PP."));
				return output("domestic/project_error.html");
			}
			Cost += Project->get_cost() * PlanetNumber;
		}
		else
		{
			if (MAX_PLAYER_PP - Cost < Project->get_cost())
			{
				ITEM("ERROR_MESSAGE",
						GETTEXT("The total price of the project(s) you have chosen exceeds the limit of PP."));
				return output("domestic/project_error.html");
			}
			Cost = Project->get_cost();
		}
	}

	if (AnyProject == false)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("There are no projects you chose."));

		return output("domestic/project_error.html");
	}

	if (aPlayer->get_production() < Cost)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("Your PP is not enough to gain project(s) you selected."));

		return output("domestic/project_error.html");
	}

	ITEM("EFFECT_RESULT",
			ProjectList->selected_project_effect_html(aPlayer, ProjectSet));

	static CString
		ProjectName;
	ProjectName.clear();

	bool
		Comma = false;

	for (int i=CurrentProjectNumber-1 ; i>=0 ; i--)
	{
		if (!ProjectSet.has(i)) continue;

		CProject *
			Project = (CProject *)ProjectList->get(i);

		aPlayer->buy_project(Project->get_id());

		if (Comma == true) ProjectName += ", ";
		ProjectName += Project->get_name();
		Comma = true;
	}

	ITEM("RESULT_MESSAGE", 
			(char *)format(GETTEXT("You gain %1$s project at the cost of %2$s PP."),
							(char *)ProjectName, dec2unit(Cost)));

	refresh_product_point_item();

//	system_log("end page handler %s", get_name());

	return output("domestic/project_result.html");
}
