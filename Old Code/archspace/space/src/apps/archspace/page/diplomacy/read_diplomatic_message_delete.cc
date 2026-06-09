#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageReadDiplomaticMessageDelete::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name());

	QUERY("MESSAGE_ID", MessageIDString);
	int
		MessageID = as_atoi(MessageIDString);

	CDiplomaticMessageBox *
		MessageBox = aPlayer->get_diplomatic_message_box();
	if (!MessageBox->length())
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You don't have any diplomatic messages received."));
		return output("diplomacy/read_diplomatic_message_error.html");
	}

	CDiplomaticMessage *
		Message = MessageBox->get_by_id(MessageID);
	if (!Message)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You don't have that diplomatic message."));
		return output("diplomacy/read_diplomatic_message_error.html");
	}

	Message->type(QUERY_DELETE);
	STORE_CENTER->store(*Message);

	MessageBox->remove_diplomatic_message(MessageID);

	ITEM("RESULT_MESSAGE",
			GETTEXT("The message you selected was successfully deleted."));

//	system_log("end page handler %s", get_name());

	return output("diplomacy/read_diplomatic_message_delete.html");
}

