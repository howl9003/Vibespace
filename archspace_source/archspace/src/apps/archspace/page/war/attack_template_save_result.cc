#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"
#include "../../player.h"
#include <cstdio>

// Save the current offence deployment as a named attack template (the converse
// of a defence plan). Reached by a standalone "Save as template" POST from the
// HTML5 deploy board, carrying the same FLEET{n}_X/Y/O/ID contract the attack
// itself uses, plus PLAN_NAME and an optional PLAN_ID (overwrite an existing
// template of that id; absent = create a new one).
//
// Coordinates are stored in BOARD space (X 9..609, Y 226..426) exactly as
// submitted, so autoload round-trips them with no transform. The capital carries
// only its stance (the board always pins it to centre), stored with x=y=0.

bool
CPageAttackTemplateSaveResult::handler(CPlayer *aPlayer)
{
	QUERY("PLAN_NAME", PlanNameString);
	if (!PlanNameString)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't enter a name for the attack template."));
		return output("war/war_error.html");
	}
	if (!is_valid_name((char *)PlanNameString, 30))
	{
		ITEM("ERROR_MESSAGE", GETTEXT("That template name is invalid."));
		return output("war/war_error.html");
	}

	CFleetList *
		FleetList = aPlayer->get_fleet_list();

	QUERY("FLEET_NUMBER", FleetNumberString);
	int
		FleetNumber = as_atoi(FleetNumberString);
	if (FleetNumber < 1 || FleetNumber > 20)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("Wrong fleet number received."));
		return output("war/war_error.html");
	}

	CAttackPlanList *
		AttackPlanList = aPlayer->get_attack_plan_list();

	// Capital fleet (stance only).
	QUERY("capFleet_ID", CapitalIDString);
	int
		CapitalID = as_atoi(CapitalIDString);
	if (!CapitalID)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't select a capital fleet."));
		return output("war/war_error.html");
	}
	if (!FleetList->get_by_id(CapitalID))
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You don't have the selected capital fleet."));
		return output("war/war_error.html");
	}

	QUERY("capFleet_O", CapitalCommandString);
	int
		CapitalCommand = CDefenseFleet::get_fleet_command_from_string((char *)CapitalCommandString);
	if (CapitalCommand == -1)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("Wrong command string received."));
		return output("war/war_error.html");
	}

	// Build the fleet rows in a scratch plan first; only commit once all input
	// has validated, so a bad row can't leave a half-written template.
	CAttackPlan
		Scratch;

	CAttackFleet *
		CapFleet = new CAttackFleet;
	CapFleet->set_owner(aPlayer->get_game_id());
	CapFleet->set_fleet_id(CapitalID);
	CapFleet->set_command(CapitalCommand);
	CapFleet->set_x(0);
	CapFleet->set_y(0);
	Scratch.add_attack_fleet(CapFleet);

	char Query[64];
	for (int i=2 ; i<=FleetNumber ; i++)
	{
		sprintf(Query, "Fleet%d_ID", i);
		QUERY(Query, FleetIDString);
		int
			FleetID = as_atoi(FleetIDString);
		CFleet *
			Fleet = FleetList->get_by_id(FleetID);
		if (!Fleet)
		{
			ITEM("ERROR_MESSAGE", GETTEXT("Wrong fleet ID received."));
			return output("war/war_error.html");
		}

		sprintf(Query, "Fleet%d_X", i);
		QUERY(Query, LocationXString);
		sprintf(Query, "Fleet%d_Y", i);
		QUERY(Query, LocationYString);
		int
			LocationX = as_atoi(LocationXString),
			LocationY = as_atoi(LocationYString);
		if (LocationX < 9 || LocationX > 609 ||
			LocationY < 226 || LocationY > 426)
		{
			ITEM("ERROR_MESSAGE", GETTEXT("A fleet has a wrong location on the battle field."));
			return output("war/war_error.html");
		}

		sprintf(Query, "fleet%d_O", i);
		QUERY(Query, CommandString);
		int
			Command = CDefenseFleet::get_fleet_command_from_string((char *)CommandString);
		if (Command == -1)
		{
			ITEM("ERROR_MESSAGE", GETTEXT("Wrong command string received."));
			return output("war/war_error.html");
		}

		CAttackFleet *
			AttackFleet = new CAttackFleet;
		AttackFleet->set_owner(aPlayer->get_game_id());
		AttackFleet->set_fleet_id(FleetID);
		AttackFleet->set_command(Command);
		AttackFleet->set_x(LocationX);
		AttackFleet->set_y(LocationY);
		Scratch.add_attack_fleet(AttackFleet);
	}

	// Resolve the target id: overwrite an existing template if PLAN_ID names one
	// we own, else allocate a fresh id. Overwriting deletes the old plan + its
	// fleet rows wholesale, then recreates under the same id.
	int
		PlanID = 0;
	QUERY("PLAN_ID", PlanIDString);
	if (PlanIDString)
	{
		int
			ReqID = as_atoi(PlanIDString);
		CAttackPlan *
			Existing = AttackPlanList->get_by_id(ReqID);
		if (Existing)
		{
			CAttackFleetList *
				OldFleets = Existing->get_fleet_list();
			for (int j=OldFleets->length()-1 ; j>=0 ; j--)
			{
				CAttackFleet *
					Old = (CAttackFleet *)OldFleets->get(j);
				Old->type(QUERY_DELETE);
				STORE_CENTER->store(*Old);
			}
			Existing->type(QUERY_DELETE);
			STORE_CENTER->store(*Existing);
			AttackPlanList->remove_attack_plan(ReqID);   // frees Existing
			PlanID = ReqID;
		}
	}
	if (!PlanID) PlanID = AttackPlanList->get_new_id();

	CAttackPlan *
		NewPlan = new CAttackPlan();
	NewPlan->set_owner(aPlayer->get_game_id());
	NewPlan->set_id(PlanID);
	NewPlan->set_capital(CapitalID);
	NewPlan->set_name((char *)PlanNameString);
	AttackPlanList->add_attack_plan(NewPlan);

	NewPlan->type(QUERY_INSERT);
	STORE_CENTER->store(*NewPlan);

	// Move the validated rows from the scratch plan into the saved one, stamping
	// the resolved plan id, and persist each.
	CAttackFleetList *
		ScratchFleets = Scratch.get_fleet_list();
	for (int i=ScratchFleets->length()-1 ; i>=0 ; i--)
	{
		CAttackFleet *
			AttackFleet = (CAttackFleet *)ScratchFleets->get(i);
		ScratchFleets->remove_without_free_attack_fleet(AttackFleet->get_fleet_id());
		AttackFleet->set_plan_id(PlanID);
		NewPlan->add_attack_fleet(AttackFleet);

		AttackFleet->type(QUERY_INSERT);
		STORE_CENTER->store(*AttackFleet);
	}

	ITEM("RESULT_MESSAGE",
			(char *)format(GETTEXT("Attack template \"%1$s\" has been saved."),
							(char *)PlanNameString));

	return output("war/attack_template_save_result.html");
}
