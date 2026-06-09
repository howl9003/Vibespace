#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageFormNewFleetResult::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name() );

	QUERY("FLEET_ID", FleetIDString);
	if (!FleetIDString)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("The new fleet's ID was not found."
						" Please try again."));
		return output("fleet/form_new_fleet_error.html");
	}

	int
		FleetID = as_atoi(FleetIDString);
	CFleetList *
		FleetList = aPlayer->get_fleet_list();
	CFleet *
		Fleet = (CFleet *)FleetList->get_by_id(FleetID);

	if (Fleet)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("ID %1$s is already in use."),
								dec2unit(FleetID)));
		return output("fleet/form_new_fleet_error.html");
	}

	QUERY("FLEET_NAME", OriginalFleetName);

	CString
		FleetName;
	FleetName = OriginalFleetName;
	FleetName.htmlspecialchars();
	FleetName.nl2br();
	FleetName.strip_all_slashes();
	if ((char *)FleetName == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't enter a fleet name."));
		return output("fleet/form_new_fleet_error.html");
	}

	if(!is_valid_name((char *)FleetName))
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You did not enter a valid fleet name."));
		return output("fleet/form_new_fleet_error.html");
	}

	QUERY("ADMIRAL_ID", AdmiralIDString);
	if (!AdmiralIDString)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("Admiral ID String is NULL."
						" Please ask Archspace Development Team."));
		return output("fleet/form_new_fleet_error.html");
	}

	int
		AdmiralID = as_atoi(AdmiralIDString);
	CAdmiralList *
		AdmiralPool = (CAdmiralList *)aPlayer->get_admiral_pool();
	CAdmiral *
		Admiral = (CAdmiral *)AdmiralPool->get_by_id(AdmiralID);

	if (!Admiral)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("The admiral you chose doesn't exist."));
		return ("fleet/form_new_fleet_error.html");
	}

	QUERY("SHIP_CLASS", ShipClassString);
	if (!ShipClassString)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("Class String is NULL."
						" Please ask Archspace Development Team."));
		return ("fleet/form_new_fleet_error.html");
	}

	int
		ClassID = as_atoi(ShipClassString);
	CShipDesign *
		Class = aPlayer->get_ship_design_list()->get_by_id(ClassID);
	if (!Class)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("The admiral you chose doesn't exist."));
		return ("fleet/form_new_fleet_error.html");
	}

	QUERY("SHIP_NUMBER", ShipNumberString);
	int
		ShipNumber = as_atoi(ShipNumberString);

	if (ShipNumber < 1)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You have to enter a larger number than 0."));
		return ("fleet/form_new_fleet_error.html");
	}

	if (ShipNumber > aPlayer->get_dock()->count_ship(ClassID))
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("You have only %1$d %2$s ships."),
								aPlayer->get_dock()->count_ship(ClassID),
								Class->get_name()));
		return ("fleet/form_new_fleet_error.html");
	}

	if (ShipNumber > Admiral->get_fleet_commanding())
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(
						GETTEXT("Fleet Commander %1$s can command %2$d ship(s)"
								" at most."),
						Admiral->get_name(),
						Admiral->get_fleet_commanding()));
		return ("fleet/form_new_fleet_error.html");
	}

	Fleet = new CFleet;
	Fleet->set_id(FleetID);
	Fleet->set_owner(aPlayer->get_game_id());
	Fleet->set_name((char *)FleetName);
	Fleet->set_admiral(AdmiralID);
	Fleet->set_ship_class(Class);
	Fleet->set_max_ship(ShipNumber);
	Fleet->set_current_ship(ShipNumber);
	Fleet->set_exp(25 + aPlayer->get_control_model()->get_military()*3);

	aPlayer->get_dock()->change_ship(Class, -ShipNumber);
	aPlayer->get_fleet_list()->add_fleet(Fleet);

	Fleet->type(QUERY_INSERT);
	*STORE_CENTER << *Fleet;

	Admiral->set_fleet_number(FleetID);
	AdmiralPool->remove_without_free_admiral(AdmiralID);
	aPlayer->get_admiral_list()->add_admiral(Admiral);

	Admiral->type(QUERY_UPDATE);
	*STORE_CENTER << *Admiral;

	ITEM("RESULT_MESSAGE",
			(char *)format(GETTEXT("The new fleet %1$s has been formed.<BR>"
									"It is now on stand-by."),
							Fleet->get_nick()));

//	system_log( "end page handler %s", get_name() );

	return output("fleet/form_new_fleet_result.html");
}

