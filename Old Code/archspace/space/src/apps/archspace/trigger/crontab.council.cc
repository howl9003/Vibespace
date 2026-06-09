#include "../triggers.h"
#include "../archspace.h"
#include "../council.h"

void
CCronTabCouncil::handler()
{
	SLOG("SYSTEM : Council CronTab handler() start");

	if (EMPIRE->is_dead() == false)
	{
		for(int i=0; i<COUNCIL_TABLE->length(); i++)
		{
			CCouncil *
				Council = (CCouncil *)COUNCIL_TABLE->get(i);

			Council->update_by_time();
		}
	}

	SLOG("SYSTEM : Council CronTab handler() end");
}
