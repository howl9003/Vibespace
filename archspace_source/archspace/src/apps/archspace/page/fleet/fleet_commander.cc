#include <libintl.h>
#include "../../pages.h"
#include "../../game.h"
#include "../../player.h"
#include "../../admiral.h"
#include "../../ship.h"
#include "../../fleet.h"
#include "../../preference.h"

bool
CPageFleetCommander::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name());

	// if player doesn't have a preference object yet, make it (needed by the
	// pool/academy renderers below, which read preferences).
	if (aPlayer->get_preference() == NULL)
		aPlayer->set_preference(new CPreference(aPlayer->get_game_id()));

	ITEM( "RESULT_MESSAGE", " ");

	ITEM("STRING_ATTACHED_FLEET_COMMANDER_S_",
			GETTEXT("Attached Fleet Commander(s)"));

	ITEM("ATTACHED_FLEET_COMMANDER_INFO",
			aPlayer->get_admiral_list()->attached_fleet_commander_info_html(aPlayer));

	ITEM("STRING_FLEET_COMMANDER_S__IN_THE_POOL",
			GETTEXT("Fleet Commander(s) in the Pool"));

	ITEM("POOL_FLEET_COMMANDER_INFO",
			aPlayer->get_admiral_pool()->pool_fleet_commander_info_html());

	ITEM("PLAYER_COMMANDER_VIEW_OPTIONS",
			aPlayer->get_preference()->commander_view_html());

	//----------------------------------------------------------- Fleet Academy
	// Enrolled pool commanders are auto-trained every cycle using the allocated
	// ship pool -- same exp/cost/duration as a manual Train mission, no
	// micromanagement. These tokens render the enrollment + allocation form;
	// academy_result.as applies the submitted changes.
	CPreference *
		Pref = aPlayer->get_preference();
	CAdmiralList *
		Pool = aPlayer->get_admiral_pool();

	// auto-enroll checkbox
	CString
		AutoEnroll;
	AutoEnroll.format("<INPUT TYPE=checkbox NAME=AUTO_ENROLL VALUE=ON%s> %s",
			Pref->getAcademyAutoEnroll() ? " CHECKED" : "",
			GETTEXT("Automatically enroll new commanders in the academy"));
	ITEM("ACADEMY_AUTO_ENROLL", (char *)AutoEnroll);

	// enrollment table: one checkbox per pool commander, pre-checked if enrolled
	CString
		Enroll;
	Enroll.clear();
	if (!Pool->length())
	{
		Enroll = GETTEXT("There are no fleet commanders in the pool to enroll.");
	}
	else
	{
		Enroll = "<TABLE WIDTH=\"540\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"0\" BORDERCOLOR=\"#2A2A2A\">\n";
		Enroll += "<TR BGCOLOR=\"#171717\">\n";
		Enroll.format("<TH CLASS=\"tabletxt\" WIDTH=\"45\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", GETTEXT("ID"));
		Enroll.format("<TH CLASS=\"tabletxt\" WIDTH=\"160\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", GETTEXT("Name"));
		Enroll.format("<TH CLASS=\"tabletxt\" WIDTH=\"60\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", GETTEXT("Level"));
		Enroll.format("<TH CLASS=\"tabletxt\" WIDTH=\"90\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", GETTEXT("Fleet<BR>Commanding"));
		Enroll.format("<TH CLASS=\"tabletxt\" WIDTH=\"90\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", GETTEXT("Enroll"));
		Enroll += "</TR>\n";

		for (int i = 0 ; i < Pool->length() ; i++)
		{
			CAdmiral *
				Admiral = (CAdmiral *)Pool->get(i);
			if (Admiral == NULL) continue;

			Enroll += "<TR>\n";
			Enroll.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"45\">%d</TD>\n",
					Admiral->get_id());
			Enroll.format("<TD CLASS=\"tabletxt\" ALIGN=\"LEFT\" WIDTH=\"160\">"
					"<A HREF=\"/archspace/fleet/fleet_commander_information.as?ADMIRAL_ID=%d\">%s</A></TD>\n",
					Admiral->get_id(), Admiral->get_name());
			Enroll.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"60\">%d</TD>\n",
					Admiral->get_level());
			Enroll.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"90\">%d</TD>\n",
					Admiral->get_fleet_commanding());
			if (Admiral->get_level() >= MAX_LEVEL)
			{
				Enroll.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"90\">%s</TD>\n",
						GETTEXT("MAX"));
			}
			else
			{
				Enroll.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"90\">"
						"<INPUT TYPE=checkbox NAME=ENROLL_%d VALUE=ON%s></TD>\n",
						Admiral->get_id(),
						Admiral->is_academy() ? " CHECKED" : "");
			}
			Enroll += "</TR>\n";
		}
		Enroll += "</TABLE>\n";
	}
	ITEM("ACADEMY_ENROLL_INFO", (char *)Enroll);

	// ship-allocation table: per ship class, show dock/academy counts and an
	// input to set the academy allocation (absolute target).
	CString
		Alloc;
	Alloc.clear();
	CShipDesignList *
		Designs = aPlayer->get_ship_design_list();
	CDock *
		Dock = aPlayer->get_dock();
	CDock *
		Academy = aPlayer->get_academy_dock();

	if (Designs == NULL || !Designs->length())
	{
		Alloc = GETTEXT("You have no ship designs yet.");
	}
	else
	{
		Alloc = "<TABLE WIDTH=\"540\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"0\" BORDERCOLOR=\"#2A2A2A\">\n";
		Alloc += "<TR BGCOLOR=\"#171717\">\n";
		Alloc.format("<TH CLASS=\"tabletxt\" WIDTH=\"200\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", GETTEXT("Ship Class"));
		Alloc.format("<TH CLASS=\"tabletxt\" WIDTH=\"110\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", GETTEXT("In Dock"));
		Alloc.format("<TH CLASS=\"tabletxt\" WIDTH=\"110\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", GETTEXT("In Academy"));
		Alloc.format("<TH CLASS=\"tabletxt\" WIDTH=\"120\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", GETTEXT("Allocate"));
		Alloc += "</TR>\n";

		for (int i = 0 ; i < Designs->length() ; i++)
		{
			CShipDesign *
				Class = (CShipDesign *)Designs->get(i);
			if (Class == NULL) continue;

			int
				DesignID = Class->get_design_id();
			int
				InDock = Dock->count_ship(DesignID);
			int
				InAcademy = Academy->count_ship(DesignID);

			if (InDock == 0 && InAcademy == 0) continue;   // nothing to allocate

			Alloc += "<TR>\n";
			Alloc.format("<TD CLASS=\"tabletxt\" ALIGN=\"LEFT\" WIDTH=\"200\">%s</TD>\n",
					Class->get_name());
			Alloc.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"110\">%d</TD>\n", InDock);
			Alloc.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"110\">%d</TD>\n", InAcademy);
			Alloc.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"120\">"
					"<INPUT NAME=ACA_%d VALUE=%d SIZE=6></TD>\n",
					DesignID, InAcademy);
			Alloc += "</TR>\n";
		}
		Alloc += "</TABLE>\n";
		Alloc.format("<BR>%s\n",
				GETTEXT("Set the number of each class to keep in the academy. "
						"Ships are moved between your dock and the academy when you save."));
	}
	ITEM("ACADEMY_SHIP_ALLOC", (char *)Alloc);

	// status line: enrolled count, allocated ships, cycle length
	int
		Enrolled = 0;
	for (int i = 0 ; i < Pool->length() ; i++)
	{
		CAdmiral *
			Admiral = (CAdmiral *)Pool->get(i);
		if (Admiral != NULL && Admiral->is_academy()) Enrolled++;
	}
	int
		Turns = (CGame::mSecondPerTurn > 0)
				? (int)(CMission::mTrainMissionTime / CGame::mSecondPerTurn) : 0;
	CString
		Status;
	Status.format(GETTEXT("%1$d commander(s) enrolled. %2$d ship(s) allocated. "
				"Each training cycle takes %3$d turn(s)."),
			Enrolled, Academy->get_ship_number(), Turns);
	ITEM("ACADEMY_STATUS", (char *)Status);

//	system_log("end page handler %s", get_name());

	return output( "fleet/fleet_commander.html" );
}
