#include <libintl.h>
#include "../../pages.h"
#include"../../archspace.h"

bool
CPageExpedition::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name());

	if ((!GAME->mUpdateTurn))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("Expeditions are disabled during prestart."));
		return output("error.html");
	}
	CFleetList *
		FleetList = aPlayer->get_fleet_list();

	// QOL: turn off auto-repeat on a fleet that's currently out. The current
	// trip finishes, then the fleet returns to stand-by instead of relaunching.
	QUERY("STOP_AUTO", StopAutoStr);
	if (StopAutoStr && *StopAutoStr)
	{
		CFleet *
			StopFleet = FleetList->get_by_id(as_atoi(StopAutoStr));
		if (StopFleet)
		{
			CMission&
				StopMission = StopFleet->get_mission();
			if ((StopMission.get_mission() == CMission::MISSION_EXPEDITION ||
				 StopMission.get_mission() == CMission::MISSION_RETURNING_WITH_PLANET) &&
				StopMission.get_target() != 0)
			{
				StopMission.set_target(0);
				StopFleet->type(QUERY_UPDATE);
				STORE_CENTER->store(*StopFleet);
			}
		}
	}

	if (FleetList->fleet_number_by_status(CFleet::FLEET_STAND_BY) == 0)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You don't have any fleets on stand-by."));
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
			static CString
				Error;
			Error = GETTEXT("There's a fleet on expedition or returning from expedition.");
			// Offer an off-switch when that fleet is on auto-repeat, otherwise
			// a RETURNING_WITH_PLANET fleet can never be stopped.
			if (Mission.get_target() != 0)
			{
				Error += "<BR>\n";
				Error += (char *)format(
						GETTEXT("Fleet %1$s is on auto-repeat."),
						Fleet->get_name());
				Error += (char *)format(
						" <A HREF=\"expedition.as?STOP_AUTO=%d\">%s</A>",
						Fleet->get_id(),
						GETTEXT("Stop auto-repeat"));
			}
			ITEM("ERROR_MESSAGE", (char *)Error);
			return output("fleet/expedition_error.html");
		}
	}

	ITEM("STRING_FLEET_STATUS", GETTEXT("Fleet Status"));

	ITEM("FLEETS", FleetList->expedition_fleet_list_html(aPlayer));

//	system_log("end page handler %s", get_name());

	return output( "fleet/expedition.html" );
}

