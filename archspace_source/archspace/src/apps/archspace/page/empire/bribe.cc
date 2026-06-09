#include "../../pages.h"

bool
CPageEmpireBribe::handler(CPlayer *aPlayer)
{
	ITEM( "DESCRIPTION", (char*)(*mBribeDescription) );
	ITEM( "EMPIRE_RELATION", aPlayer->get_degree_name_of_empire_relation() );
	ITEM( "COURT_RANK", aPlayer->get_court_rank_name() );

	return output("empire/bribe.html");
}
