#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageBroadcastConfirm::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	CQueryList &
		IDString = CONNECTION->id_string();
	char *
		IsAdmin = IDString.get_value("IS_ADMIN");

	if (!IsAdmin)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}
	if (strcmp(IsAdmin, "YES"))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}

	QUERY("BROADCAST_TYPE", BroadcastTypeString);
	if (BroadcastTypeString == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't specify how to broadcast a message."));

		return output("admin/admin_error.html");
	}

	if (strcmp(BroadcastTypeString, "MESSAGE") && strcmp(BroadcastTypeString, "MAIL"))
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You specified a wrong way to broadcast a message."));

		return output("admin/admin_error.html");
	}

	QUERY("BROADCAST_MESSAGE", BroadcastMessageString);
	if (BroadcastMessageString == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't input a broadcast message."));

		return output("admin/admin_error.html");
	}

	ITEM("CONFIRM_MESSAGE", GETTEXT("Are you sure this message has no problem?"));

	ITEM("BROADCAST_TYPE", BroadcastTypeString);
	ITEM("BROADCAST_MESSAGE", BroadcastMessageString);

//	system_log("end page handler %s", get_name());

	return output("admin/broadcast_confirm.html");
}
