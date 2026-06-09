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

	Info = "<TABLE WIDTH=\"550\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"0\" BORDERCOLOR=\"#2A2A2A\">\n";
	Info += "<TR BGCOLOR=\"#171717\">\n";

	Info += "<TH CLASS=\"tabletxt\" WIDTH=\"45\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("ID");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"118\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Name");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"51\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Level");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"44\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Exp.");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"102\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Fleet<BR>Commanding");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"42\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Efficiency");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"134\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Commanding<BR>Fleet");
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

		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"51\">%d</TD>\n",
					Admiral->get_level());
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"44\">%d</TD>\n",
					Admiral->get_exp());
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"102\">%d</TD>\n",
					Admiral->get_fleet_commanding());
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"42\">%d %%</TD>\n",
					Admiral->get_real_efficiency());

		CFleetList *
			FleetList = aPlayer->get_fleet_list();

		CFleet *
			Fleet = FleetList->get_by_id(Admiral->get_fleet_number());

		if( Fleet )
			Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"134\">%s</TD>\n",
					Fleet->get_nick());
		else
			Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"134\">-</TD>\n");
		Info += "</TR>\n";
	}

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

	Info = "<TABLE WIDTH=\"550\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"0\" BORDERCOLOR=\"#2A2A2A\">\n";
	Info += "<TR BGCOLOR=\"#171717\">\n";

	Info += "<TH CLASS=\"tabletxt\" WIDTH=\"45\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("ID");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"118\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Name");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"51\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Level");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"44\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Exp.");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"102\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Fleet<BR>Commanding");
	Info += "</FONT></TH>\n";

	Info += "<TH WIDTH=\"42\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	Info += GETTEXT("Efficiency");
	Info += "</FONT></TH>\n";

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

		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"51\">%d</TD>\n",
					Admiral->get_level());
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"44\">%d</TD>\n",
					Admiral->get_exp());
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"102\">%d</TD>\n",
					Admiral->get_fleet_commanding());
		Info.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"42\">%d %%</TD>\n",
					Admiral->get_real_efficiency());

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

	List += "<TH WIDTH=\"52\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	List += GETTEXT("Level");
	List += "</FONT></TH>\n";

	List += "<TH WIDTH=\"59\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	List += GETTEXT("Exp.");
	List += "</FONT></TH>\n";

	List += "<TH WIDTH=\"130\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	List += GETTEXT("Fleet Commanding");
	List += "</FONT></TH>\n";

	List += "<TH WIDTH=\"105\" CLASS=\"tabletxt\"><FONT COLOR=\"666666\">";
	List += GETTEXT("Efficiency");
	List += "</FONT></TH>\n";

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

		List.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"52\">%d</TD>\n",
					Admiral->get_level());
		List.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"59\">%d</TD>\n",
					Admiral->get_exp());
		List.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"130\">%d</TD>\n",
					Admiral->get_fleet_commanding());
		List.format("<TD CLASS=\"tabletxt\" ALIGN=\"CENTER\" WIDTH=\"105\">%d</TD>\n",
					Admiral->get_real_efficiency());

		List += "</TR>\n";
	}

	List += "</TABLE>\n";

	return (char *)List;
}


