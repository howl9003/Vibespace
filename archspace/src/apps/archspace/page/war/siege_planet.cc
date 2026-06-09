#include <libintl.h>
#include "../../pages.h"

bool
CPageSiegePlanet::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name() );

	if (aPlayer->has_siege_blockade_restriction() == true)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("Your officers are too busy to plan a new offensive, because your newly conquered colony needs much management to be settled down."));

		return output("war/war_error.html");
	}

	ITEM("STRING_TARGET", GETTEXT("Target"));
	ITEM("INFORMATION_MESSAGE",
			GETTEXT("You can only attack a player who is at war with you."));

	static CString TargetListHTML;
	TargetListHTML.clear();

	TargetListHTML = aPlayer->war_target_list_html();

	CHECK(!aPlayer->war_target_list_html(),
			GETTEXT("There is no target player to attack."));

	ITEM("TARGET_LIST", (char *)TargetListHTML);

//	system_log( "end page handler %s", get_name() );

	return output("war/siege_planet.html");
}

