#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageExpeditionResult::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name() );

	if ((!GAME->mUpdateTurn))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("Expeditions are disabled during prestart."));
		return output("error.html");
	}
	CFleetList *
		FleetList = aPlayer->get_fleet_list();

	QUERY("FLEET_ID", FleetIDString);
	int
		FleetID = as_atoi(FleetIDString);
	CFleet *
		Fleet = FleetList->get_by_id(FleetID);

	if (!Fleet)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("You don't have any fleet with ID %1$s."),
								dec2unit(FleetID)));
		return output("fleet/expedition_error.html");
	}

	for (int i=0 ; i<FleetList->length() ; i++)
	{
		CFleet *
			Fleet = (CFleet *)FleetList->get(i);
		CMission&
			Mission = Fleet->get_mission();

		if (Mission.get_mission() == CMission::MISSION_EXPEDITION ||
			Mission.get_mission() == CMission::MISSION_RETURNING_WITH_PLANET)
		{
			ITEM("ERROR_MESSAGE",
					GETTEXT("There's a fleet on expedition or returning from expedition."));
					return output("fleet/expedition_error.html");
		}
	}

	// QOL: opt-in auto-repeat. The checkbox rides on the (otherwise unused for
	// expeditions) mission_target field: 1 = relaunch after each success, 0 = one-shot.
	QUERY("AUTO_REPEAT", AutoStr);
	int
		Auto = as_atoi(AutoStr) ? 1 : 0;

	Fleet->init_mission(CMission::MISSION_EXPEDITION, Auto);

	Fleet->type(QUERY_UPDATE);
	STORE_CENTER->store(*Fleet);

	if (Auto)
		ITEM("RESULT_MESSAGE", GETTEXT("Your selected fleet has set out on a expedition"
										" to the deep space, and will automatically continue"
										" expeditions after each successful return."));
	else
		ITEM("RESULT_MESSAGE", GETTEXT("Your selected fleet has set out on a expedition"
										" to the deep space."));

//	system_log( "end page handler %s", get_name() );

	return output("fleet/expedition_result.html");
}

