#include "../triggers.h"
#include "../archspace.h"
#include "../council.h"

bool
CTriggerRank::handler()
{
	system_log("trigger rank start");

	if (EMPIRE->is_dead() == false)
	{
		PLAYER_TABLE->refresh_rank_table();
		COUNCIL_TABLE->refresh_rank_table();
	}

	system_log("trigger ranking end");
	return true;
}
