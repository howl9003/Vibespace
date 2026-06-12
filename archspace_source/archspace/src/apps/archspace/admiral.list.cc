#include "admiral.h"
#include "define.h"
#include <libintl.h>
#include "fleet.h"
#include "player.h"
#include "archspace.h"

CAdmiralList::CAdmiralList(): CSortedList(50, 50)
{
}

CAdmiralList::~CAdmiralList()
{
	remove_all();
}

bool
CAdmiralList::free_item(TSomething aItem)
{
	CAdmiral
		*Admiral = (CAdmiral *)aItem;

	if (!Admiral) return false;

	delete Admiral;

	return true;
}

int
CAdmiralList::compare(TSomething aItem1, TSomething aItem2) const
{
	CAdmiral
		*Admiral1 = (CAdmiral*)aItem1,
		*Admiral2 = (CAdmiral*)aItem2;

	if (Admiral1->get_id() > Admiral2->get_id()) return 1;
	if (Admiral1->get_id() < Admiral2->get_id()) return -1;
	return 0;
}

int
CAdmiralList::compare_key(TSomething aItem, TConstSomething aKey) const
{
	CAdmiral
		*Admiral = (CAdmiral*)aItem;

	if (Admiral->get_id() > (int)aKey) return 1;
	if (Admiral->get_id() < (int)aKey) return -1;
	return 0;
}

bool
CAdmiralList::remove_admiral(int aID)
{
	int
		Index = find_sorted_key((TConstSomething)aID);
	if (Index < 0) return false;

	return CSortedList::remove(Index);
}

bool
CAdmiralList::remove_without_free_admiral(int aID)
{
	int
		Index = find_sorted_key((TConstSomething)aID);
	if (Index < 0) return false;

	return remove_without_free(Index);
}



bool
CAdmiralList::add_admiral(CAdmiral *aAdmiral)
{
	if (!aAdmiral) return false;

	if (find_sorted_key((TConstSomething)aAdmiral->get_id()) >= 0) 
		return false;

	insert_sorted(aAdmiral);

	return true;
}

CAdmiral*
CAdmiralList::get_by_id(int aID)
{
	if (!length()) return NULL;

	int Index = find_sorted_key((TSomething)aID);

	if (Index < 0) return NULL;

	return (CAdmiral*)get(Index);
}

bool
CAdmiralList::update_DB()
{
	for (int i=0 ; i<length() ; i++)
	{
		CAdmiral *
			Admiral = (CAdmiral *)get(i);
		if (Admiral->is_changed() == false) continue;

		Admiral->type(QUERY_UPDATE);
		STORE_CENTER->store(*Admiral);
	}

	return true;
}

char *
CAdmiralList::attached_fleet_commander_info_html(CPlayer *aPlayer)
{
	static CString
		Info;
	Info.clear();

	if (!length())
	{
		Info = GETTEXT("There are no fleet commanders attached to a fleet.");
		return (char *)Info;
	}

	CPreference *aPreference =
		parent->get_preference();

	Info = "<TABLE WIDTH=\"550\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"0\" BORDERCOLOR=\"#2A2A2A\">\n";
	Info += "<TR BGCOLOR=\"#171717\">\n";

	Info += "<TH CLASS=\"tabletxt\" WIDTH=\"45\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Fleet ID");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"118\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Name");
	Info += "</FONT></TH>\n";

#define COMMANDER_STAT_TITLE(enum, title, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		Info.format("<TH WIDTH=\"%d\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", width, title);\
	}

	COMMANDER_STAT_TITLE(LEVEL, "Level", 51);
	COMMANDER_STAT_TITLE(EXP, "Exp", 44);
	COMMANDER_STAT_TITLE(FLEET_COMMANDING, "Fleet<BR>Commanding", 102);
	COMMANDER_STAT_TITLE(EFFICIENCY, "Efficiency", 42);
	COMMANDER_STAT_TITLE(SIEGE_PLANET, "Siege<BR>Planet", 44);
	COMMANDER_STAT_TITLE(BLOCKADE, "Blockade", 40);
	COMMANDER_STAT_TITLE(RAID, "Raid", 40);
	COMMANDER_STAT_TITLE(PRIVATEER, "Privateer", 40);
	COMMANDER_STAT_TITLE(SIEGE_REPELLING, "Siege<BR>Repelling", 44);
	COMMANDER_STAT_TITLE(BREAK_BLOCKADE, "Break<BR>Blockade", 44);
	COMMANDER_STAT_TITLE(PREVENT_RAID, "Prevent<BR>Raid", 44);
	COMMANDER_STAT_TITLE(MANEUVER, "Maneuver", 40);
	COMMANDER_STAT_TITLE(DETECTION, "Detection", 40);
	COMMANDER_STAT_TITLE(INTERPRETATION, "Interpretation", 44);
	COMMANDER_STAT_TITLE(ARMADA_CLASS, "Armada<BR>Class", 44);
	COMMANDER_STAT_TITLE(ABILITY, "Ability", 44);

#undef COMMANDER_STAT_TITLE

	Info += "<TH WIDTH=\"134\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Commanding<BR>Fleet");
	Info += "</FONT></TH>\n";

	Info += "</TR>\n";

	// Iterate existing fleets in ascending fleet-ID order (the fleet list is
	// kept sorted by its zero-padded "owner id" key, so plain iteration is
	// already ascending by fleet ID for this player), showing each fleet's
	// attached commander keyed by the fleet's ID rather than the commander's.
	CFleetList *
		FleetList = aPlayer->get_fleet_list();

	for (int i=0 ; i<FleetList->length() ; i++)
	{
		CFleet *
			Fleet = (CFleet *)FleetList->get(i);
		if (Fleet == NULL) continue;

		CAdmiral *
			Admiral = get_by_id(Fleet->get_admiral_id());
		if (Admiral == NULL) continue;

		Info += "<TR>\n";
		Info.format("<TD CLASS=\"tabletxt\" WIDTH=\"45\" ALIGN=\"CENTER\">%d</TD>\n",
					Fleet->get_id());

		Info += "<TD CLASS=\"tabletxt\" ALIGN=\"LEFT\" WIDTH=\"118\">";
		Info.format("<A HREF=\"/archspace/fleet/fleet_commander_information.as?ADMIRAL_ID=%d\">%s</A>",
					Admiral->get_id(), Admiral->get_name());
		Info += "</TD>\n";
			
#define COMMANDER_STAT(enum, function, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"%d\">%d</TD>\n",\
				width, Admiral->function());\
	}

	COMMANDER_STAT(LEVEL, get_level, 51);
	COMMANDER_STAT(EXP, get_exp, 44);
	COMMANDER_STAT(FLEET_COMMANDING, get_fleet_commanding, 102);
	COMMANDER_STAT(EFFICIENCY, get_real_efficiency, 42);
	COMMANDER_STAT(SIEGE_PLANET, get_siege_planet_level, 44);
	COMMANDER_STAT(BLOCKADE, get_blockade_level, 40);
	COMMANDER_STAT(RAID, get_raid_level, 40);
	COMMANDER_STAT(PRIVATEER, get_privateer_level, 40);
	COMMANDER_STAT(SIEGE_REPELLING, get_siege_repelling_level, 44);
	COMMANDER_STAT(BREAK_BLOCKADE, get_break_blockade_level, 44);
	COMMANDER_STAT(PREVENT_RAID, get_prevent_raid_level, 44);
	COMMANDER_STAT(MANEUVER, get_maneuver_level, 40);
	COMMANDER_STAT(DETECTION, get_detection_level, 40);
	COMMANDER_STAT(INTERPRETATION, get_interpretation_level, 44);
#undef COMMANDER_STAT
#define COMMANDER_STAT(enum, function, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"%d\">%s</TD>\n",\
				width, Admiral->function());\
	}
	COMMANDER_STAT(ARMADA_CLASS, get_armada_commanding_name, 44);
	COMMANDER_STAT(ABILITY, get_ability_name, 44);

#undef COMMANDER_STAT

		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"134\">%s</TD>\n",
					Fleet->get_nick());
		Info += "</TR>\n";
	}

	Info += "</TABLE>\n";

	return (char *)Info;
}

char *
CAdmiralList::fleet_commander_list_javascript(CPlayer *aPlayer)
{
	static CString
		Info;
	Info.clear();

	CPreference *aPreference =
		aPlayer->get_preference();

	if (!length())
	{
		Info = GETTEXT("You don't have any admirals.");
		return (char *)Info;
	}

	int
		BaseFleetID = aPlayer->get_fleet_list()->get_new_fleet_id();

	CDock *
		ShipPool = aPlayer->get_dock();

	Info = "<TABLE CLASS=\"as-sortable\" WIDTH=\"550\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"0\" BORDERCOLOR=\"#2A2A2A\">\n";
	Info += "<THEAD>\n";
	Info += "<TR BGCOLOR=\"#171717\">\n";

	Info += "<TH CLASS=\"tabletxt\" WIDTH=\"45\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("ID");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"118\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Name");
	Info += "</FONT></TH>\n";

#define COMMANDER_STAT_TITLE(enum, title, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		Info.format("<TH WIDTH=\"%d\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", width, title);\
	}

	COMMANDER_STAT_TITLE(LEVEL, "Level", 51);
	COMMANDER_STAT_TITLE(EXP, "Exp", 44);
	COMMANDER_STAT_TITLE(FLEET_COMMANDING, "Fleet<BR>Commanding", 102);
	COMMANDER_STAT_TITLE(EFFICIENCY, "Efficiency", 42);
	COMMANDER_STAT_TITLE(SIEGE_PLANET, "Siege<BR>Planet", 44);
	COMMANDER_STAT_TITLE(BLOCKADE, "Blockade", 40);
	COMMANDER_STAT_TITLE(RAID, "Raid", 40);
	COMMANDER_STAT_TITLE(PRIVATEER, "Privateer", 40);
	COMMANDER_STAT_TITLE(SIEGE_REPELLING, "Siege<BR>Repelling", 44);
	COMMANDER_STAT_TITLE(BREAK_BLOCKADE, "Break<BR>Blockade", 44);
	COMMANDER_STAT_TITLE(PREVENT_RAID, "Prevent<BR>Raid", 44);
	COMMANDER_STAT_TITLE(MANEUVER, "Maneuver", 40);
	COMMANDER_STAT_TITLE(DETECTION, "Detection", 40);
	COMMANDER_STAT_TITLE(INTERPRETATION, "Interpretation", 44);
	COMMANDER_STAT_TITLE(ARMADA_CLASS, "Armada<BR>Class", 44);
	COMMANDER_STAT_TITLE(ABILITY, "Ability", 44);

#undef COMMANDER_STAT_TITLE

	Info += "<TH CLASS=\"tabletxt nosort\" WIDTH=\"99\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Ship Class");
	Info += "</FONT></TH>\n";

	Info += "<TH CLASS=\"tabletxt nosort\" WIDTH=\"44\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("# of Ship(s)");
	Info += "</FONT></TH>\n";

	Info += "<TH CLASS=\"tabletxt nosort\" WIDTH=\"118\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Fleet Name");
	Info += "</FONT></TH>\n";

	Info += "</TR>\n";
	Info += "</THEAD>\n";
	Info += "<TBODY>\n";

	for (int i=0 ; i<length() ; i++)
	{
		CAdmiral *
			Admiral = (CAdmiral *)get(i);

		Info += "<TR>\n";
		Info.format("<TD CLASS=\"tabletxt\" WIDTH=\"45\" ALIGN=\"CENTER\">%d</TD>\n",
					Admiral->get_id());

		Info += "<TD CLASS=\"tabletxt\" ALIGN=\"LEFT\" WIDTH=\"118\">";
		Info.format("<A HREF=\"/archspace/fleet/fleet_commander_information.as?ADMIRAL_ID=%d\">%s</A>",
					Admiral->get_id(), Admiral->get_name());
		Info += "</TD>\n";

#define COMMANDER_STAT(enum, function, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"%d\">%d</TD>\n",\
				width, Admiral->function());\
	}

	COMMANDER_STAT(LEVEL, get_level, 51);
	COMMANDER_STAT(EXP, get_exp, 44);
	COMMANDER_STAT(FLEET_COMMANDING, get_fleet_commanding, 102);
	COMMANDER_STAT(EFFICIENCY, get_real_efficiency, 42);
	COMMANDER_STAT(SIEGE_PLANET, get_siege_planet_level, 44);
	COMMANDER_STAT(BLOCKADE, get_blockade_level, 40);
	COMMANDER_STAT(RAID, get_raid_level, 40);
	COMMANDER_STAT(PRIVATEER, get_privateer_level, 40);
	COMMANDER_STAT(SIEGE_REPELLING, get_siege_repelling_level, 44);
	COMMANDER_STAT(BREAK_BLOCKADE, get_break_blockade_level, 44);
	COMMANDER_STAT(PREVENT_RAID, get_prevent_raid_level, 44);
	COMMANDER_STAT(MANEUVER, get_maneuver_level, 40);
	COMMANDER_STAT(DETECTION, get_detection_level, 40);
	COMMANDER_STAT(INTERPRETATION, get_interpretation_level, 44);
#undef COMMANDER_STAT
#define COMMANDER_STAT(enum, function, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"%d\">%s</TD>\n",\
				width, Admiral->function());\
	}
	COMMANDER_STAT(ARMADA_CLASS, get_armada_commanding_name, 44);
	COMMANDER_STAT(ABILITY, get_ability_name, 44);
#undef COMMANDER_STAT

		// Ship-class select. CDock::print_html_select() hardcodes
		// NAME="SHIP_CLASS_ID", so we emit a per-row SHIP_DESIGN{i}
		// select ourselves by iterating the docked ships.
		Info += "<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"99\">";
		Info.format("<SELECT NAME=SHIP_DESIGN%d>\n", i);
		if (ShipPool)
		{
			for (int s=0 ; s<ShipPool->length() ; s++)
			{
				CDockedShip *
					DockedShip = (CDockedShip *)ShipPool->get(s);
				CShipDesign *
					Class = (CShipDesign *)DockedShip;
				Info.format("<OPTION VALUE=%d>%s</OPTION>\n",
							Class->CShipDesign::get_design_id(), Class->get_name());
			}
		}
		Info += "</SELECT></TD>\n";

		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"44\"><INPUT NAME=SHIP_NUMBER%d VALUE=0 SIZE=4></TD>\n", i);

		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"118\"><INPUT NAME=FLEET_NAME%d SIZE=12 MAXLENGTH=30>", i);
		Info.format("<INPUT TYPE=hidden NAME=FLEET_ID%d VALUE=%d></TD>\n", i, BaseFleetID + i);

		Info += "</TR>\n";
	}

	Info += "</TBODY>\n";
	Info += "</TABLE>\n";

	return (char *)Info;
}

char *
CAdmiralList::pool_fleet_commander_info_html()
{
	static CString
		Info;
	Info.clear();

	if (!length())
	{
		Info = GETTEXT("There are no fleet commanders in the pool.");
		return (char *)Info;
	}

	CPreference *aPreference = 
		parent->get_preference();
	
	Info = "<TABLE WIDTH=\"550\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"0\" BORDERCOLOR=\"#2A2A2A\">\n";
	Info += "<TR BGCOLOR=\"#171717\">\n";

	Info += "<TH CLASS=\"tabletxt\" WIDTH=\"45\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("ID");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"118\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Name");
	Info += "</FONT></TH>\n";

#define COMMANDER_STAT_TITLE(enum, title, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		Info.format("<TH WIDTH=\"%d\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", width, title);\
	}

	COMMANDER_STAT_TITLE(LEVEL, "Level", 51);
	COMMANDER_STAT_TITLE(EXP, "Exp", 44);
	COMMANDER_STAT_TITLE(FLEET_COMMANDING, "Fleet<BR>Commanding", 102);
	COMMANDER_STAT_TITLE(EFFICIENCY, "Efficiency", 42);
	COMMANDER_STAT_TITLE(SIEGE_PLANET, "Siege<BR>Planet", 44);
	COMMANDER_STAT_TITLE(BLOCKADE, "Blockade", 40);
	COMMANDER_STAT_TITLE(RAID, "Raid", 40);
	COMMANDER_STAT_TITLE(PRIVATEER, "Privateer", 40);
	COMMANDER_STAT_TITLE(SIEGE_REPELLING, "Siege<BR>Repelling", 44);
	COMMANDER_STAT_TITLE(BREAK_BLOCKADE, "Break<BR>Blockade", 44);
	COMMANDER_STAT_TITLE(PREVENT_RAID, "Prevent<BR>Raid", 44);
	COMMANDER_STAT_TITLE(MANEUVER, "Maneuver", 40);
	COMMANDER_STAT_TITLE(DETECTION, "Detection", 40);
	COMMANDER_STAT_TITLE(INTERPRETATION, "Interpretation", 44);
	COMMANDER_STAT_TITLE(ARMADA_CLASS, "Armada<BR> Class", 44);
	COMMANDER_STAT_TITLE(ABILITY, "Ability", 44);

#undef COMMANDER_STAT_TITLE

	Info += "<TH WIDTH=\"134\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Dismiss");
	Info += " <INPUT TYPE=checkbox onClick=allCheck()>";
	Info += "</FONT></TH>\n";

	Info += "</TR>\n";

	for (int i=0 ; i<length() ; i++)
	{
		CAdmiral *
			Admiral = (CAdmiral *)get(i);

		Info += "<TR>\n";
		Info.format("<TD CLASS=\"tabletxt\" WIDTH=\"45\" ALIGN=\"CENTER\">%d</TD>\n",
					Admiral->get_id());

		Info += "<TD CLASS=\"tabletxt\" ALIGN=\"LEFT\" WIDTH=\"118\">";
		Info.format("<A HREF=\"/archspace/fleet/fleet_commander_information.as?ADMIRAL_ID=%d\">%s</A>",
					Admiral->get_id(), Admiral->get_name());
		Info += "</TD>\n";

#define COMMANDER_STAT(enum, function, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"%d\">%d</TD>\n",\
				width, Admiral->function());\
	}

	COMMANDER_STAT(LEVEL, get_level, 51);
	COMMANDER_STAT(EXP, get_exp, 44);
	COMMANDER_STAT(FLEET_COMMANDING, get_fleet_commanding, 102);
	COMMANDER_STAT(EFFICIENCY, get_real_efficiency, 42);
	COMMANDER_STAT(SIEGE_PLANET, get_siege_planet_level, 44);
	COMMANDER_STAT(BLOCKADE, get_blockade_level, 40);
	COMMANDER_STAT(RAID, get_raid_level, 40);
	COMMANDER_STAT(PRIVATEER, get_privateer_level, 40);
	COMMANDER_STAT(SIEGE_REPELLING, get_siege_repelling_level, 44);
	COMMANDER_STAT(BREAK_BLOCKADE, get_break_blockade_level, 44);
	COMMANDER_STAT(PREVENT_RAID, get_prevent_raid_level, 44);
	COMMANDER_STAT(MANEUVER, get_maneuver_level, 40);
	COMMANDER_STAT(DETECTION, get_detection_level, 40);
	COMMANDER_STAT(INTERPRETATION, get_interpretation_level, 44);
#undef COMMANDER_STAT
#define COMMANDER_STAT(enum, function, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"%d\">%s</TD>\n",\
				width, Admiral->function());\
	}
	COMMANDER_STAT(ARMADA_CLASS, get_armada_commanding_name, 44);
	COMMANDER_STAT(ABILITY, get_ability_name, 44);

#undef COMMANDER_STAT

		Info += "<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"134\">";
		Info.format("<INPUT TYPE=checkbox NAME=ADMIRAL%d>", i);
		Info += "</TD>\n";
		Info += "</TR>\n";
	}

	Info += "</TABLE>\n";

	return (char *)Info;
}

char *
CAdmiralList::fleet_commander_list_html()
{
	static CString
		List;
	List.clear();

	if (!length())
	{
		List = GETTEXT("There are no fleet commanders attached to a fleet.");
		return (char *)List;
	}

	CPreference *aPreference = 
		parent->get_preference();
	
	List = "<TABLE WIDTH=\"550\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"0\" BORDERCOLOR=\"#2A2A2A\">\n";
	List += "<TR BGCOLOR=\"#171717\">\n";

	List += "<TH CLASS=\"tabletxt\" WIDTH=\"51\"><FONT COLOR=\"666666\">";
	List += GETTEXT("ID");
	List += "</FONT></TH>\n";

	List += "<TH WIDTH=\"24\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	List += "</FONT></TH>\n";

	List += "<TH WIDTH=\"113\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	List += GETTEXT("Name");
	List += "</FONT></TH>\n";
	
#define COMMANDER_STAT_TITLE(enum, title, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		List.format("<TH WIDTH=\"%d\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">%s</FONT></TH>\n", width, title);\
	}

	COMMANDER_STAT_TITLE(LEVEL, "Level", 51);
	COMMANDER_STAT_TITLE(EXP, "Exp", 44);
	COMMANDER_STAT_TITLE(FLEET_COMMANDING, "Fleet<BR>Commanding", 102);
	COMMANDER_STAT_TITLE(EFFICIENCY, "Efficiency", 42);
	COMMANDER_STAT_TITLE(SIEGE_PLANET, "Siege<BR>Planet", 44);
	COMMANDER_STAT_TITLE(BLOCKADE, "Blockade", 40);
	COMMANDER_STAT_TITLE(RAID, "Raid", 40);
	COMMANDER_STAT_TITLE(PRIVATEER, "Privateer", 40);
	COMMANDER_STAT_TITLE(SIEGE_REPELLING, "Siege<BR>Repelling", 44);
	COMMANDER_STAT_TITLE(BREAK_BLOCKADE, "Break<BR>Blockade", 44);
	COMMANDER_STAT_TITLE(PREVENT_RAID, "Prevent<BR>Raid", 44);
	COMMANDER_STAT_TITLE(MANEUVER, "Maneuver", 40);
	COMMANDER_STAT_TITLE(DETECTION, "Detection", 40);
	COMMANDER_STAT_TITLE(INTERPRETATION, "Interpretation", 44);
	COMMANDER_STAT_TITLE(ARMADA_CLASS, "Armada<BR>Class", 44);
	COMMANDER_STAT_TITLE(ABILITY, "Ability", 44);

#undef COMMANDER_STAT_TITLE

	List += "</TR>\n";

	for (int i=0 ; i<length() ; i++)
	{
		CAdmiral *
			Admiral = (CAdmiral *)get(i);

		List += "<TR>\n";

		List.format("<TD CLASS=\"tabletxt\" WIDTH=\"51\" ALIGN=\"CENTER\">%d</TD>\n",
					Admiral->get_id());

		List += "<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"24\">\n";
		if (i == 0)
		{
			List.format("<INPUT TYPE=\"radio\" CHECKED NAME=\"ADMIRAL_INDEX\" VALUE=\"%d\" onClick=\"shipSel()\" onFocus=\"this.blur()\">\n", i);
		} else
		{
			List.format("<INPUT TYPE=\"radio\" NAME=\"ADMIRAL_INDEX\" VALUE=\"%d\" onClick=\"shipSel()\" onFocus=\"this.blur()\">\n", i);
		}
		List += "</TD>\n";

		List += "<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"113\">\n";
		List.format("<A HREF=/archspace/fleet/fleet_commander_information.as?ADMIRAL_ID=%d>%s</A>",
					Admiral->get_id(), Admiral->get_name());
		List += "</TD>\n";
		
#define COMMANDER_STAT(enum, function, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		List.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"%d\">%d</TD>\n",\
				width, Admiral->function());\
	}

	COMMANDER_STAT(LEVEL, get_level, 51);
	COMMANDER_STAT(EXP, get_exp, 44);
	COMMANDER_STAT(FLEET_COMMANDING, get_fleet_commanding, 102);
	COMMANDER_STAT(EFFICIENCY, get_real_efficiency, 42);
	COMMANDER_STAT(SIEGE_PLANET, get_siege_planet_level, 44);
	COMMANDER_STAT(BLOCKADE, get_blockade_level, 40);
	COMMANDER_STAT(RAID, get_raid_level, 40);
	COMMANDER_STAT(PRIVATEER, get_privateer_level, 40);
	COMMANDER_STAT(SIEGE_REPELLING, get_siege_repelling_level, 44);
	COMMANDER_STAT(BREAK_BLOCKADE, get_break_blockade_level, 44);
	COMMANDER_STAT(PREVENT_RAID, get_prevent_raid_level, 44);
	COMMANDER_STAT(MANEUVER, get_maneuver_level, 40);
	COMMANDER_STAT(DETECTION, get_detection_level, 40);
	COMMANDER_STAT(INTERPRETATION, get_interpretation_level, 44);
#undef COMMANDER_STAT
#define COMMANDER_STAT(enum, function, width) \
	if (aPreference->hasCommanderStat(CPreference::enum))\
	{\
		List.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"%d\">%s</TD>\n",\
				width, Admiral->function());\
	}
	COMMANDER_STAT(ARMADA_CLASS, get_armada_commanding_name, 44);
	COMMANDER_STAT(ABILITY, get_ability_name, 44);

#undef COMMANDER_STAT

		List += "</TR>\n";
	}

	List += "</TABLE>\n";

	return (char *)List;
}


