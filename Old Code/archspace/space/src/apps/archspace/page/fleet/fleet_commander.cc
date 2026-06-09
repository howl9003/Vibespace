#include <libintl.h>
#include "../../pages.h"

bool
CPageFleetCommander::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name());

	ITEM( "RESULT_MESSAGE", " ");

	ITEM("STRING_ATTACHED_FLEET_COMMANDER_S_",
			GETTEXT("Attached Fleet Commander(s)"));

	ITEM("ATTACHED_FLEET_COMMANDER_INFO",
			aPlayer->get_admiral_list()->attached_fleet_commander_info_html(aPlayer));

	ITEM("STRING_FLEET_COMMANDER_S__IN_THE_POOL",
			GETTEXT("Fleet Commander(s) in the Pool"));

	ITEM("POOL_FLEET_COMMANDER_INFO",
			aPlayer->get_admiral_pool()->pool_fleet_commander_info_html());

//	system_log("end page handler %s", get_name());

	return output( "fleet/fleet_commander.html" );
}

