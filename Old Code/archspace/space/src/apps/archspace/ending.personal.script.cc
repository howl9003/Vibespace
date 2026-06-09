#include "script.h"
#include "define.h"
#include "ending.h"
#include <cstdlib>
#include "archspace.h"
#include "game.h"

bool
CPersonalEndingScript::get(CPersonalEndingTable *aPersonalEndingTable)
{
	SLOG("start script no:%d", mRoot.length());
	int i=0;
	TSomething
		Ending;

	while ((Ending = get_array(i++)))
	{
		CPersonalEnding *
			tmpEnding = new CPersonalEnding;
		tmpEnding->set_name(get_name(Ending));
		TSomething
			something;
		//ending id	
		something = get_section("Number", Ending);
		if (Ending)
		{
			tmpEnding->set_id(atoi(get_data(something)));
		}
		else
		{
			SLOG("WRONG ENDING FILE at %s", tmpEnding->get_name());
			return false;
		}
		//ending condition
		something = get_section("Condition", Ending);
		if(something)
		{
			int i=0;
			TSomething
				condition;
			while((condition = get_array(i++, something)))
			{
				const char* type = get_name(condition);
				if( !strcasecmp(type, "Tech")
					|| !strcasecmp(type, "Tech2")
					|| !strcasecmp(type, "Project")
					|| !strcasecmp(type, "Planet")
					|| !strcasecmp(type, "Cluster")
					|| !strcasecmp(type, "CommanderLevel")
					|| !strcasecmp(type, "Fleet")
					|| !strcasecmp(type, "Council")
					|| !strcasecmp(type, "TechAll")
					|| !strcasecmp(type, "RP")
					|| !strcasecmp(type, "DAequalORless")
					|| !strcasecmp(type, "Efficiency")
					|| !strcasecmp(type, "HaveTitle")
					|| !strcasecmp(type, "NoWarInCouncil")
					|| !strcasecmp(type, "DAgreater")
					|| !strcasecmp(type, "HaveDoomstar")
					|| !strcasecmp(type, "CouncilProject")
					|| !strcasecmp(type, "DA")
					|| !strcasecmp(type, "ShipPool")
					|| !strcasecmp(type, "PopulationIncreasedLess")
					|| !strcasecmp(type, "ConcentrationMode")
					|| !strcasecmp(type, "PopulationIncreasedEqualOrMore")
					|| !strcasecmp(type, "CouncilSpeaker") )
				{
					tmpEnding->set_condition( get_name(condition), atoi(get_data(condition)) );
				}
				else
				{
					SLOG("WRONG TYPE %s", type);
				}
			}
			
		}
		else
		{
			SLOG("WRONG ENDING FILE at %s", tmpEnding->get_name());
			return false;
		}
		//ending project
		something = get_section("Project", Ending);
		if(something)
		{
			tmpEnding->set_project_id(atoi(get_data(something)));
		}
		else
		{
			SLOG("WRONG ENDING FILE at %s", tmpEnding->get_name());
			return false;
		}
		//ending race
		something = get_section("Race", Ending);
		if(something)
		{
			tmpEnding->set_race_index(atoi(get_data(something)));
		}
		else
		{
			SLOG("WRONG ENDING FILE at %s", tmpEnding->get_name());
			return false;
		}

		//add to table
		if( ENDING_PROJECT_TABLE->get_by_id( tmpEnding->get_project_id() ) == NULL )
		{
			continue;
		}
		aPersonalEndingTable->add_personal_ending(tmpEnding);
	}
	SLOG("end ending script");
	return true;
}

