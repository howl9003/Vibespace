#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageRebootResult::handler(CPlayer *aPlayer)
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

	CString
		CurrentReferer,
		LegalReferer;
	CurrentReferer = mConnection->get_referer();
	LegalReferer.format("http://%s/archspace/admin/reboot_confirm.as",
						mConnection->get_host_name());

	if ((char *)CurrentReferer == NULL)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You seem to have approached this page in a wrong way."));
		return output("admin/admin_error.html");
	}
	if (strcmp((char *)CurrentReferer, (char *)LegalReferer))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You seem to have approached this page in a wrong way."));
		return output("admin/admin_error.html");
	}

	QUERY("REBOOT", RebootString);
	if (RebootString == NULL)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You seem to have approached this page in a wrong way."));
		return output("admin/admin_error.html");
	}
	if (strcmp((char *)RebootString, "REBOOT"))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You seem to have approached this page in a wrong way."));
		return output("admin/admin_error.html");
	}

	CArchspace::shutdown_server();

	ITEM("RESULT_MESSAGE",
			(char *)format(GETTEXT("The server is rebooting in %s secs."),
							dec2unit(SERVER_SHUTDOWN_DELAY)));

//	system_log("end page handler %s", get_name());

	return output("admin/reboot_result.html");
}
