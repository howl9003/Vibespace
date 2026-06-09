#include <libintl.h>
#include "../triggers.h"
#include "../archspace.h"
#include "../ending.h"

bool
CTriggerPersonalEnding::handler()
{
	SLOG("start Personal Ending checking");

	if (EMPIRE->is_dead() == false)
	{
		for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
		{
			CPlayer *
				Player = (CPlayer *)PLAYER_TABLE->get(i);
			if (Player->get_game_id() == EMPIRE_GAME_ID) continue;
			if (Player->is_dead() == true) continue;

			for (int j=0 ; j<PERSONAL_ENDING_TABLE->length() ; j++)
			{
				CPersonalEnding *
					Ending = (CPersonalEnding *)PERSONAL_ENDING_TABLE->get(j);
				//check race
				if (Ending->get_race_index() != Player->get_race()) continue;

				//check if already achived this ending
				CPurchasedProjectList *
					PurchasedProjectList = Player->get_purchased_project_list();
				if (PurchasedProjectList->get_by_id(Ending->get_project_id()) != NULL) continue;

				if (Ending->check_condition(Player) == true)
				{
					Player->buy_ending_project(Ending->get_project_id());

					Player->time_news(GETTEXT("* Your destiny has arisen! *"));
				}
			}
		}
	}

	SLOG("end Personal Ending checking");
	return true;
}
