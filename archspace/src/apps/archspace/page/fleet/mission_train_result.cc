#include <libintl.h>
#include "../../pages.h"
#include "../../game.h"

bool
CPageMissionTrainResult::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name() );


	if (!CGame::mUpdateTurn)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You cannot perform this action until time starts."));
		return output("fleet/mission_error.html");
	}
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

	CFleetList *
		FleetList = aPlayer->get_fleet_list();
	int
		UpkeepPP,
		UpkeepMP = 0;
	for (int i=0 ; i<FleetList->length() ; i++)
	{
		CFleet *
			Fleet = (CFleet *)FleetList->get(i);
		if (Fleet->get_status() != CFleet::FLEET_STAND_BY) continue;
		if (FleetSet.has(i) == false) continue;

		if (MAX_PLAYER_MP - UpkeepMP < Fleet->calc_upkeep())
		{
			UpkeepMP = MAX_PLAYER_MP;
			break;
		}
		else
		{
			UpkeepMP += Fleet->calc_upkeep();
		}
	}

	if (UpkeepMP <= aPlayer->get_last_turn_military()) UpkeepPP = 0;
	else
	{
		if ((UpkeepMP-aPlayer->get_last_turn_military()) > MAX_PLAYER_PP/20)
		{
			UpkeepPP = MAX_PLAYER_PP;
		}
		else
		{
			UpkeepPP = 20 * (UpkeepMP-aPlayer->get_last_turn_military());
		}
		UpkeepMP = aPlayer->get_last_turn_military();
	}
	if (UpkeepPP > aPlayer->get_production())
	{
		Message.clear();
		Message.format(GETTEXT("Training selected fleet(s) will cost %1$s MP and %2$s PP.<BR>"
								"You don't have enough PP. Please try again later."),
						dec2unit(UpkeepMP),
						dec2unit(UpkeepPP));
		ITEM("ERROR_MESSAGE", (char *)Message);
		return output("fleet/mission_error.html");
	}

	int
		TrainInHour = (CMission::mTrainMissionTime / 60) / 60,
		TrainInMin = (CMission::mTrainMissionTime / 60) % 60;

	CString
		TrainInHourString,
		TrainInMinString;
	TrainInHourString = dec2unit(TrainInHour);
	TrainInMinString = dec2unit(TrainInMin);

	Message.format(GETTEXT("Your selected fleet(s) enter(s) the training process. It will end in %1$s hour(s) and %2$s minute(s), and until then you do not have any control of the fleet."),
					(char *)TrainInHourString, (char *)TrainInMinString);

	Message += "<BR><BR>\n";

	for (int i=0 ; i<FleetList->length() ; i++)
	{
		CFleet *
			Fleet = (CFleet *)FleetList->get(i);
		if (Fleet->get_status() != CFleet::FLEET_STAND_BY) continue;
		if (FleetSet.has(i))
		{
			Fleet->init_mission(CMission::MISSION_TRAIN, 0);
		}
	}

	aPlayer->change_last_turn_military(-UpkeepMP);
	aPlayer->change_reserved_production(-UpkeepPP);

	Message.format(GETTEXT("You have lost %1$d MP and %2$d PP."), UpkeepMP, UpkeepPP );

	ITEM("RESULT_MESSAGE", (char *)Message);

//	system_log( "end page handler %s", get_name() );

	return output("fleet/mission_train_result.html");
}

