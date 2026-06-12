#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"
#include "../../player.h"

// Delete a saved attack template (plan row + all its fleet rows), by PLAN_ID.

bool
CPageAttackTemplateDeleteResult::handler(CPlayer *aPlayer)
{
	QUERY("PLAN_ID", PlanIDString);
	if (!PlanIDString)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't select an attack template to delete."));
		return output("war/war_error.html");
	}

	int
		PlanID = as_atoi(PlanIDString);
	CAttackPlanList *
		AttackPlanList = aPlayer->get_attack_plan_list();
	CAttackPlan *
		Plan = AttackPlanList->get_by_id(PlanID);
	if (!Plan)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You don't have that attack template."));
		return output("war/war_error.html");
	}

	CAttackFleetList *
		Fleets = Plan->get_fleet_list();
	for (int i=Fleets->length()-1 ; i>=0 ; i--)
	{
		CAttackFleet *
			Fleet = (CAttackFleet *)Fleets->get(i);
		Fleet->type(QUERY_DELETE);
		STORE_CENTER->store(*Fleet);
	}

	Plan->type(QUERY_DELETE);
	STORE_CENTER->store(*Plan);

	AttackPlanList->remove_attack_plan(PlanID);   // frees Plan

	ITEM("RESULT_MESSAGE", GETTEXT("The attack template has been deleted."));

	return output("war/attack_template_save_result.html");
}
