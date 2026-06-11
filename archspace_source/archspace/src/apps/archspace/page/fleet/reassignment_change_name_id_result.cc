#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageReassignmentChangeNameIDResult::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name() );

	static CString
		Message;
	Message.clear();

	QUERY("FLEET", FleetIDString);

	int
		FleetID = as_atoi(FleetIDString);
	CFleet *
		Fleet = aPlayer->get_fleet_list()->get_by_id(FleetID);
	if (!Fleet)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("You don't have such a fleet with ID %1$s."),
								dec2unit(FleetID)));
		return output("fleet/reassignment_error.html");
	}

	if (Fleet->get_status() != CFleet::FLEET_STAND_BY)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You can reassign only stand-by fleets."));
		return output("fleet/reassignment_error.html");
	}

	// --- New name ---------------------------------------------------------
	QUERY("NEW_NAME", NewNameString);

	CString
		NewName;
	NewName = NewNameString ? NewNameString : "";
	NewName.htmlspecialchars();
	NewName.nl2br();
	NewName.strip_all_slashes();

	if ((char *)NewName == NULL || !*(char *)NewName)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't enter a fleet name."));
		return output("fleet/reassignment_error.html");
	}

	if (!is_valid_name((char *)NewName, 30))
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't enter a valid fleet name."));
		return output("fleet/reassignment_error.html");
	}

	// --- New id -----------------------------------------------------------
	QUERY("NEW_ID", NewIDString);

	int
		NewID = NewIDString ? as_atoi(NewIDString) : Fleet->get_id();
	if (NewID <= 0)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't enter a valid fleet ID."));
		return output("fleet/reassignment_error.html");
	}

	int
		OldID = Fleet->get_id();

	if (NewID != OldID && !aPlayer->get_fleet_list()->is_id_available(NewID))
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("Fleet ID %1$s is already in use."),
								dec2unit(NewID)));
		return output("fleet/reassignment_error.html");
	}

	CString
		OldNick;
	OldNick = Fleet->get_nick();

	Fleet->set_name((char *)NewName);

	if (NewID != OldID)
	{
		// The fleet row is keyed by (owner, id) and the UPDATE query never
		// rewrites the id column, so renumbering means dropping the old row
		// and inserting a fresh one under the new id.
		Fleet->type(QUERY_DELETE);
		STORE_CENTER->store(*Fleet);

		// The fleet list is sorted by each fleet's key (owner+id); set_id() rebuilds
		// the key in place but does NOT re-sort the list, so the fleet would keep its
		// old slot (still showing as e.g. #2). Pull it out under the old id, renumber,
		// then re-insert so it sorts into its new position.
		CFleetList *
			FleetList = aPlayer->get_fleet_list();
		FleetList->remove_without_free_fleet(OldID);

		Fleet->set_id(NewID);

		FleetList->add_fleet(Fleet);

		// Keep the commanding admiral's fleet-number reference in sync.
		CAdmiral *
			Admiral =
				aPlayer->get_admiral_list()->get_by_id(Fleet->get_admiral_id());
		if (Admiral)
		{
			Admiral->set_fleet_number(NewID);
			Admiral->type(QUERY_UPDATE);
			STORE_CENTER->store(*Admiral);
		}

		Fleet->type(QUERY_INSERT);
		STORE_CENTER->store(*Fleet);

		// Repoint the player's defence plans at the renumbered fleet so it isn't
		// silently dropped from them. A deployment row is keyed by
		// (owner, plan_id, fleet_id) and its UPDATE query has no WHERE clause, so
		// re-key it the same way as the fleet itself: delete the old row and insert
		// a fresh one under the new id (preserving its command/coordinates). Also
		// repoint any plan whose capital fleet was this one (that UPDATE is safe --
		// it is keyed by owner+id). A fleet can appear in several plans, so scan all.
		CDefensePlanList *
			PlanList = aPlayer->get_defense_plan_list();
		for (int p=0 ; p<PlanList->length() ; p++)
		{
			CDefensePlan *
				Plan = (CDefensePlan *)PlanList->get(p);
			if (Plan == NULL) continue;

			CDefenseFleetList *
				DFList = Plan->get_fleet_list();
			CDefenseFleet *
				DF = DFList->get_by_id(OldID);
			if (DF)
			{
				DF->type(QUERY_DELETE);
				STORE_CENTER->store(*DF);

				DFList->remove_without_free_defense_fleet(OldID);
				DF->set_fleet_id(NewID);
				DFList->add_defense_fleet(DF);

				DF->type(QUERY_INSERT);
				STORE_CENTER->store(*DF);
			}

			if (Plan->get_capital() == OldID)
			{
				Plan->set_capital(NewID);
				Plan->type(QUERY_UPDATE);
				STORE_CENTER->store(*Plan);
			}
		}
	}
	else
	{
		Fleet->type(QUERY_UPDATE);
		STORE_CENTER->store(*Fleet);
	}

	Message.format(GETTEXT("%1$s is now %2$s."),
					(char *)OldNick,
					Fleet->get_nick());
	Message += "<BR>\n";

	ITEM("RESULT_MESSAGE", (char *)Message);

//	system_log( "end page handler %s", get_name() );

	return output("fleet/reassignment_change_name_id_result.html");
}
