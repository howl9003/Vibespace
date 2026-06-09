#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageRebootConfirm::handler(CPlayer *aPlayer)
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

	ITEM("CONFIRM_MESSAGE",
			GETTEXT("Are you sure the server has to be restarted?"));

//	system_log("end page handler %s", get_name());

	return output("admin/reboot_confirm.html");
}
