#include <libintl.h>
#include "../pages.h"
#include "../archspace.h"
#include "../game.h"

bool
CPageShowEvent::handler(CPlayer *aPlayer)
{
	QUERY("EVENT_ID", EventIDStr);
	QUERY("EVENT_TYPE", EventType);

	if (!EventIDStr || !EventType)
	{
		ITEM("RESULT_MESSAGE",
				GETTEXT("No event was selected or a type was missing."));
		return output("event_error.html");
	}

	int
		EventID = as_atoi(EventIDStr);

	CEventInstance
		*Event = NULL;
	if( strcasecmp( EventType, "Galactic" ) == 0 ){
		Event = GALACTIC_EVENT_LIST->get_by_id(EventID);
	} else if( strcasecmp( EventType, "Cluster" ) == 0 ){
		CCluster
			*Cluster = UNIVERSE->get_by_id(aPlayer->get_home_cluster_id());
		if( Cluster ) Event = Cluster->get_event_list()->get_by_id(EventID);
	} else {
		Event = aPlayer->get_event_list()->get_by_id(EventID);
	}

	CString
		Message;

	if (Event == NULL)
	{
		ITEM("RESULT_MESSAGE",
				GETTEXT("There is no such event."));
		return output("event_error.html");
	}

	Message.format( "<B>%s</B><BR>\nTYPE : %s<BR>\n%s<BR><BR>\n", Event->get_name(), Event->get_type_str(), Event->get_description() );

	if (Event->has_type(CEvent::EVENT_ANSWER))
	{
		if(Event->is_answered() == false)
		{
			Message.format("<A HREF=\"answer_event.as?EVENT_ID=%s&EVENT_TYPE=%s&EVENT_ANSWER=yes\"><IMG SRC=http://%s/image/as_game/bu_accept.gif BORDER = 0></A>\n<A HREF=\"answer_event.as?EVENT_ID=%s&EVENT_TYPE=%s&EVENT_ANSWER=no\"><IMG SRC=http://%s/image/as_game/bu_decline.gif BORDER=0></A>\n",
							EventIDStr,
							EventType,
							(char *)CGame::mImageServerURL,
							EventIDStr,
							EventType,
							(char *)CGame::mImageServerURL);
		}
	}

	ITEM("IMAGE", Event->get_image());
	ITEM("RESULT_MESSAGE", (char*)Message);

	return output("show_event.html");
}
