#include "../triggers.h"
#include "../archspace.h"

bool
CTriggerDatabasePush::handler()
{
//	system_log("run trigger database push");

	STORE_CENTER->push();

	return true;
}
