#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"
#include "../../race.h"

bool
CPageDefensePlan::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name() );

	ITEM("STRING_GENERIC_DEFENSE_PLAN", GETTEXT("Generic Defense Plan"));

	CDefensePlanList *
		DefensePlanList = aPlayer->get_defense_plan_list();

	ITEM("SPECIAL_DEFENSE_PLAN_INFO",
			DefensePlanList->special_defense_plan_info_html());

	ITEM("STRING_RACE", GETTEXT("Race"));

	ITEM("STRING_HUMAN",
			RACE_TABLE->get_name_by_id(CRace::RACE_HUMAN));
	ITEM("STRING_TARGOID",
			RACE_TABLE->get_name_by_id(CRace::RACE_TARGOID));
	ITEM("STRING_BUCKANEER",
			RACE_TABLE->get_name_by_id(CRace::RACE_BUCKANEER));
	ITEM("STRING_TECANOID",
			RACE_TABLE->get_name_by_id(CRace::RACE_TECANOID));
	ITEM("STRING_EVINTOS",
			RACE_TABLE->get_name_by_id(CRace::RACE_EVINTOS));
	ITEM("STRING_AGERUS",
			RACE_TABLE->get_name_by_id(CRace::RACE_AGERUS));
	ITEM("STRING_BOSALIAN",
			RACE_TABLE->get_name_by_id(CRace::RACE_BOSALIAN));
	ITEM("STRING_XELOSS",
			RACE_TABLE->get_name_by_id(CRace::RACE_XELOSS));
	ITEM("STRING_XERUSIAN",
			RACE_TABLE->get_name_by_id(CRace::RACE_XERUSIAN));
	ITEM("STRING_XESPERADOS",
			RACE_TABLE->get_name_by_id(CRace::RACE_XESPERADOS));

	ITEM("STRING_POWER_RATIO", GETTEXT("Power Ratio"));

	ITEM("STRING_MIN_", GETTEXT("Min."));
	ITEM("STRING_MAX_", GETTEXT("Max."));

	ITEM("STRING_ATTACK_TYPE", GETTEXT("Attack Type"));

	ITEM("STRING_SIEGE", GETTEXT("Siege"));
	ITEM("STRING_BLOCKADE", GETTEXT("Blockade"));

	ITEM("MAKE_NEW_PLAN_MESSAGE",
			GETTEXT("Make new special defense plan."));

//	system_log( "end page handler %s", get_name() );

	return output( "war/defense_plan.html" );
}

