#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPageMissionDispatchResult::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	static CString
		Message;
	Message.clear();

	CCommandSet
		FleetSet;
	FleetSet.clear();

	QUERY("FLEET_SET", FleetSetString);

	if (!FleetSetString)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't select any fleets."));
		return output("fleet/mission_error.html");
	}

	FleetSet.set_string(FleetSetString);

	if (FleetSet.is_empty())
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't select any fleets."));
		return output("fleet/mission_error.html");
	}

	QUERY("PLAYER_ID", PlayerIDString);

	int
		PlayerID = as_atoi(PlayerIDString);
	CPlayer *
		Player = PLAYER_TABLE->get_by_game_id(PlayerID);

	if (Player == NULL)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(
						GETTEXT("The player with ID %1$s doesn't exist."),
						dec2unit(PlayerID)));
		return output("fleet/mission_error.html");
	}

	CPlayerRelation *
		Relation = aPlayer->get_relation(Player);
	if (Relation == NULL)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(
						GETTEXT("The player with ID %1$s is not your ally."),
						dec2unit(PlayerID)));
		return output("fleet/mission_error.html");
	}
	else if (Relation->get_relation() != CRelation::RELATION_ALLY)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(
						GETTEXT("The player with ID %1$s is not your ally."),
						dec2unit(PlayerID)));
		return output("fleet/mission_error.html");
	}

	CAllyFleetList *
		AllyFleetList = Player->get_ally_fleet_list();
	for (int i=0 ; i<AllyFleetList->length() ; i++)
	{
		CFleet *
			AllyFleet = (CFleet *)AllyFleetList->get(i);
		if (AllyFleet->get_owner() == aPlayer->get_game_id())
		{
			ITEM("ERROR_MESSAGE",
					(char *)format(GETTEXT("You already have sent %1$s to %2$s as an ally fleet."),
									AllyFleet->get_nick(), Player->get_nick()));
			return output("fleet/mission_error.html");
		}
	}

	CFleetList *
		FleetList = aPlayer->get_fleet_list();
	for (int i=0 ; i<FleetList->length() ; i++)
	{
		CFleet *
			Fleet = (CFleet *)FleetList->get(i);
		if (Fleet->get_status() != CFleet::FLEET_STAND_BY) continue;
		if (FleetSet.has(i))
		{
			Fleet->init_mission(CMission::MISSION_DISPATCH_TO_ALLY, PlayerID);
		}
	}

	Message.format(GETTEXT("Your selected fleet(s) has been sent to your ally %1$s."),
					Player->get_nick());
	Message += "<BR>\n";

	int
		DispatchInHour = CMission::mDispatchToAllyMissionTime / 60 / 60;
	Message.format(GETTEXT("The fleet will return in %1$s hours, or when severely damaged, or when you and %2$s broke off the alliance."),
					dec2unit(DispatchInHour),
					Player->get_nick());

	ITEM("RESULT_MESSAGE", (char *)Message);

//	system_log("end page handler %s", get_name());

	return output("fleet/mission_dispatch_result.html");
}

