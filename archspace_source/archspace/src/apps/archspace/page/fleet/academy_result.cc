#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"
#include "../../player.h"
#include "../../admiral.h"
#include "../../ship.h"
#include "../../preference.h"

// Fleet Academy configuration handler. This is NOT where training happens
// (that is CPlayer::academy_handler(), run every turn) -- this page only applies
// the player's choices from the fleet commander page: the auto-enroll flag,
// per-commander enrollment, and the dock <-> academy ship allocation.
bool
CPageAcademyResult::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name() );

	if (aPlayer->get_preference() == NULL)
		aPlayer->set_preference(new CPreference(aPlayer->get_game_id()));

	CPreference *
		Pref = aPlayer->get_preference();
	CAdmiralList *
		Pool = aPlayer->get_admiral_pool();

	static CString
		Message;
	Message.clear();

	// 1) auto-enroll flag
	bool
		AutoEnroll = (mQuery.get_value("AUTO_ENROLL") != NULL);
	Pref->setAcademyAutoEnroll(AutoEnroll);

	// 2) per-commander enrollment. With auto-enroll on, every eligible commander
	// is enrolled (the per-commander checkboxes are moot); otherwise honor the
	// submitted checkboxes. A checkbox that is unchecked is simply absent from
	// the query, so we re-enumerate the live pool and test each id.
	int
		Enrolled = 0;
	for (int i = 0 ; i < Pool->length() ; i++)
	{
		CAdmiral *
			Admiral = (CAdmiral *)Pool->get(i);
		if (Admiral == NULL) continue;

		bool
			Want;
		if (Admiral->get_level() >= MAX_LEVEL)
		{
			Want = false;                       // maxed commanders cannot train
		}
		else if (AutoEnroll)
		{
			Want = true;
		}
		else
		{
			CString
				Name;
			Name.format("ENROLL_%d", Admiral->get_id());
			Want = (mQuery.get_value((char *)Name) != NULL);
		}

		if (Admiral->is_academy() != Want)
		{
			Admiral->set_academy(Want);
			Admiral->type(QUERY_UPDATE);
			STORE_CENTER->store(*Admiral);
		}
		if (Want) Enrolled++;
	}

	// 3) ship allocation. For each ship class, ACA_<designid> carries the desired
	// academy count (absolute). Move the difference between the player's dock and
	// the academy dock, bounded by what is actually available.
	CShipDesignList *
		Designs = aPlayer->get_ship_design_list();
	CDock *
		Dock = aPlayer->get_dock();
	CDock *
		Academy = aPlayer->get_academy_dock();

	int
		MovedIn = 0,
		MovedOut = 0;
	if (Designs != NULL)
	{
		for (int i = 0 ; i < Designs->length() ; i++)
		{
			CShipDesign *
				Class = (CShipDesign *)Designs->get(i);
			if (Class == NULL) continue;

			int
				DesignID = Class->get_design_id();

			CString
				Name;
			Name.format("ACA_%d", DesignID);
			const char *
				Value = mQuery.get_value((char *)Name);
			if (Value == NULL) continue;

			int
				Target = as_atoi(Value);
			if (Target < 0) Target = 0;

			int
				Current = Academy->count_ship(DesignID);
			int
				Delta = Target - Current;

			if (Delta > 0)
			{
				int
					Avail = Dock->count_ship(DesignID);
				int
					Move = (Delta < Avail) ? Delta : Avail;
				if (Move > 0)
				{
					Dock->change_ship(Class, -Move);
					Academy->change_ship(Class, Move);
					MovedIn += Move;
				}
			}
			else if (Delta < 0)
			{
				int
					Move = -Delta;
				if (Move > Current) Move = Current;
				if (Move > 0)
				{
					Academy->change_ship(Class, -Move);
					Dock->change_ship(Class, Move);
					MovedOut += Move;
				}
			}
		}
	}

	Message.format(GETTEXT("Fleet Academy updated. %1$d commander(s) enrolled; "
				"%2$d ship(s) allocated to the academy."),
			Enrolled, Academy->get_ship_number());
	Message += "<BR>\n";
	if (MovedIn > 0 || MovedOut > 0)
	{
		Message.format(GETTEXT("%1$d ship(s) moved into the academy, %2$d returned to the dock."),
				MovedIn, MovedOut);
		Message += "<BR>\n";
	}
	if (AutoEnroll)
	{
		Message += GETTEXT("New commanders will be enrolled automatically.");
		Message += "<BR>\n";
	}

	ITEM("RESULT_MESSAGE", (char *)Message);

//	system_log( "end page handler %s", get_name() );

	return output("fleet/academy_result.html");
}
