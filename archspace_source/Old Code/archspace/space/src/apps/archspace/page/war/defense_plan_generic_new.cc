#include <libintl.h>
#include "../../pages.h"

bool
CPageDefensePlanGenericNew::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name() );

	CFleetList *
		FleetList = aPlayer->get_fleet_list();
	if (FleetList->fleet_number_by_status(CFleet::FLEET_STAND_BY) == 0)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You do not have any fleets on stand-by.<BR>"
						"Please try again later."));
		return output("war/defense_plan_generic_new_error.html");
	}

	ITEM("SELECT_FLEET_MESSAGE",
			GETTEXT("Select fleets to deploy(up to 20 fleets)."));

	ITEM("FLEET_LIST", FleetList->deployment_fleet_list_html(aPlayer));

//	system_log( "end page handler %s", get_name() );

	return output( "war/defense_plan_generic_new.html" );
}

