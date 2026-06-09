#include <libintl.h>
#include "../../pages.h"
#include "../../triggers.h"

bool
CPageDonation::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	if (aPlayer->check_council_donation_effect_timer())
	{
		int
			Interval = (INTERVAL_COUNCIL * CGame::mSecondPerTurn);
		int
			IntervalInHour = Interval / 60 / 60;

		ITEM("RESULT_MESSAGE",
				(char *)format(GETTEXT("You may not donate again"
										" within %1$d hour(s)"
										" since the last donation."),
						IntervalInHour));
		return output("council/donation_result.html");
	}

	ITEM("STRING_COUNCIL_SAFE", GETTEXT("Current PP in Council Safe"));
	ITEM("STRING_PLAYER_SAFE", GETTEXT("Current PP in Your Safe"));
	ITEM("STRING_AVAILABLE_LIMIT", GETTEXT("Available PP Limit"));

	CCouncil* Council = aPlayer->get_council();
	ITEM("COUNCIL_SAFE", dec2unit(Council->get_production()));
	ITEM("PLAYER_SAFE", dec2unit(aPlayer->get_production()));
	ITEM("AVAILABLE_LIMIT", 
					dec2unit((int)(aPlayer->get_production()/10)));

	ITEM("HOW_MUCH_MESSAGE", 
					GETTEXT("How much PP do you want to donate?"));

//	system_log("end page handler %s", get_name());

	return output("council/donation.html");
}
