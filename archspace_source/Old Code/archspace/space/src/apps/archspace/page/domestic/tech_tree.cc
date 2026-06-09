#include "../../pages.h"

bool
CPageTechTree::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

//	system_log("end page handler %s", get_name());

	return output("domestic/tech_tree.html");
}
