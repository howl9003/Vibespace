#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPagePlayerResearchStatus::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	CQueryList &
		IDString = CONNECTION->id_string();
	char *
		Admin = IDString.get_value("IS_ADMIN");

	if (!Admin)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}
	if (strcmp(Admin, "YES"))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}

	QUERY("PLAYER_ID", PlayerIDString);
	int
		PlayerID = as_atoi(PlayerIDString);
	CPlayer *
		Player = PLAYER_TABLE->get_by_game_id(PlayerID);
	if (Player == NULL)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("There is no player whose game ID is %1$s."),
								dec2unit(PlayerID)));
		return output("admin/admin_error.html");
	}

	if (Player->get_game_id() == EMPIRE_GAME_ID)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You can't inspect the Empire."));
		return output("admin/admin_error.html");
	}

	ITEM("STRING_TARGET_TECH", GETTEXT("Target Tech"));

	int
		TargetTechID = aPlayer->get_target_tech();
	if (TargetTechID == 0)
	{
		ITEM("TARGET_TECH", GETTEXT("Free Research"));
	}
	else
	{
		CTech *
			TargetTech = TECH_TABLE->get_by_id(TargetTechID);
		if (TargetTech != NULL)
		{
			ITEM("TARGET_TECH", TargetTech->get_name());
		}
		else
		{
			ITEM("TARGET_TECH",
				(char *)format(GETTEXT("The player's target tech(#%1$s) doesn't exist."),
								dec2unit(TargetTechID)));
		}
	}

	ITEM("RESEARCHED_TECH_MESSAGE",
			GETTEXT("The player has researched the following tech(s) :"));

	ITEM("STRING_SOCIAL_SCIENCE", GETTEXT("Social Science"));
	ITEM("STRING_INFORMATION_SCIENCE", GETTEXT("Information Science"));
	ITEM("STRING_MATTER_ENERGY_SCIENCE", GETTEXT("Matter-Energy Science"));
	ITEM("STRING_LIFE_SCIENCE", GETTEXT("Life Science"));

	ITEM("RESEARCHED_SOCIAL_SCIENCE_LIST", 
			aPlayer->known_science_list_html(CTech::TYPE_SOCIAL));
	ITEM("RESEARCHED_INFORMATION_SCIENCE_LIST", 
			aPlayer->known_science_list_html(CTech::TYPE_INFORMATION));
	ITEM("RESEARCHED_MATTER_ENERGY_SCIENCE_LIST", 
			aPlayer->known_science_list_html(CTech::TYPE_MATTER_ENERGY));
	ITEM("RESEARCHED_LIFE_SCIENCE_LIST", 
			aPlayer->known_science_list_html(CTech::TYPE_LIFE));

	ITEM("NOT_RESEARCHED_TECH_MESSAGE",
			GETTEXT("The player can research the following tech(s) :"));

	ITEM("NOT_RESEARCHED_SOCIAL_SCIENCE_LIST", 
			aPlayer->available_science_list_html(CTech::TYPE_SOCIAL));
	ITEM("NOT_RESEARCHED_INFORMATION_SCIENCE_LIST", 
			aPlayer->available_science_list_html(CTech::TYPE_INFORMATION));
	ITEM("NOT_RESEARCHED_MATTER_ENERGY_SCIENCE_LIST", 
			aPlayer->available_science_list_html(CTech::TYPE_MATTER_ENERGY));
	ITEM("NOT_RESEARCHED_LIFE_SCIENCE_LIST", 
			aPlayer->available_science_list_html(CTech::TYPE_LIFE));
	
//	system_log("end page handler %s", get_name());

	return output("admin/player_research_status.html");
}
