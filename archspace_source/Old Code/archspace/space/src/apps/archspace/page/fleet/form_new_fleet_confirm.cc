#include <libintl.h>
#include "../../pages.h"

bool
CPageFormNewFleetConfirm::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name());

	static CString
		Message;
	Message.clear();

	QUERY("FLEET_ID", FleetIDString);
	int
		FleetID = as_atoi(FleetIDString);

	if (FleetID < 1)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("The fleet ID have to be greater than 0."));
		return output ("fleet/form_new_fleet_error.html");
	}

	CFleetList *
		FleetList = aPlayer->get_fleet_list();
	CFleet *
		Fleet = (CFleet *)FleetList->get_by_id(FleetID);

	if (Fleet)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("That ID is already existing."));
		return output ("fleet/form_new_fleet_error.html");
	}

	QUERY("NEW_FLEET_NAME", NewFleetName);
	if (!NewFleetName)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You didn't enter a new fleet's name."));
		return output ("fleet/form_new_fleet_error.html");
	}

	if(!is_valid_name(NewFleetName))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You did not enter a valid fleet name."));
		return output ("fleet/form_new_fleet_error.html");
	}


	QUERY("SHIP_CLASS_ID", ShipClassIDString);
	int
		ShipClassID = as_atoi(ShipClassIDString);

	CShipDesignList *
		ClassList = aPlayer->get_ship_design_list();
	CShipDesign *
		Class = (CShipDesign *)ClassList->get_by_id(ShipClassID);

	if (!Class)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You don't have that ship class."));
		return output ("fleet/form_new_fleet_error.html");
	}

	QUERY("ADMIRAL_INDEX", AdmiralIndexString);
	int
		AdmiralIndex = as_atoi(AdmiralIndexString);
	CAdmiralList *
		AdmiralPool = aPlayer->get_admiral_pool();
	CAdmiral *
		Admiral = (CAdmiral *)AdmiralPool->get(AdmiralIndex);
	if (!Admiral)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You selected a wrong fleet commander."));
		return output ("fleet/form_new_fleet_error.html");
	}

	QUERY("SHIP_NUMBER", ShipNumberString);
	int
		ShipNumber = as_atoi(ShipNumberString);
	if (ShipNumber < 1)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You have to enter a larger number than 0."));
		return output ("fleet/form_new_fleet_error.html");
	}

	if (ShipNumber > Admiral->get_fleet_commanding())
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("Selected fleet commander can't control that number of ships."));
		return output ("fleet/form_new_fleet_error.html");
	}

	CDock *
		ShipPool = aPlayer->get_dock();

	CDockedShip *
		DockedShip = (CDockedShip *)ShipPool->get_by_id(ShipClassID);
	if (DockedShip == NULL || DockedShip->get_number() < ShipNumber)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You don't have enough ships."));
		return output ("fleet/form_new_fleet_error.html");
	}

	ITEM("STRING_FLEET_INFORMATION", GETTEXT("Fleet Information"));

	Message.format(GETTEXT("Fleet Name : %1$s"),
					NewFleetName);
	Message += "<BR>\n";
	Message.format(GETTEXT("Ship : %1$s of %2$s class"),
					dec2unit(ShipNumber),
					Class->get_name());
	Message += "<BR>\n";
	Message.format("%s : %s",
					GETTEXT("Commander"),
					Admiral->get_name());
	Message += "<BR><BR>\n";
	Message += GETTEXT("Are you sure you want to form this fleet?");

	ITEM("CONFIRM_MESSAGE", (char *)Message);

	ITEM("FLEET_ID",
			(char *)format("<INPUT TYPE=hidden NAME=\"FLEET_ID\" VALUE=\"%d\">\n",
							FleetID));
	ITEM("FLEET_NAME",
			(char *)format("<INPUT TYPE=hidden NAME=\"FLEET_NAME\" VALUE=\"%s\">\n",
							NewFleetName));
	ITEM("ADMIRAL_ID",
			(char *)format("<INPUT TYPE=hidden NAME=\"ADMIRAL_ID\" VALUE=\"%d\">\n",
							Admiral->get_id()));
	ITEM("SHIP_CLASS",
			(char *)format("<INPUT TYPE=hidden NAME=\"SHIP_CLASS\" VALUE=\"%d\">\n",
							Class->get_design_id()));
	ITEM("SHIP_NUMBER",
			(char *)format("<INPUT TYPE=hidden NAME=\"SHIP_NUMBER\" VALUE=\"%d\">\n",
							ShipNumber));

//	system_log("end page handler %s", get_name());

	return output( "fleet/form_new_fleet_confirm.html" );
}

