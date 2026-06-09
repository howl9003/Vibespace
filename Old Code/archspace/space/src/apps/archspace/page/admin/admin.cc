#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"

bool
CPageAdmin::handler(CPlayer *aPlayer)
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

	if (mPortal.get_id() != 0)
	{
		mPortal.set_id(0);
		mPortal.set_name(NULL);
		mPortal.set_password(NULL);
		mPortal.set_email(NULL);
		mPortal.set_first_name(NULL);
		mPortal.set_last_name(NULL);
		mPortal.set_icq(0);
		mPortal.set_gender(NULL);
		mPortal.set_age(0);
		mPortal.set_country(NULL);
		mPortal.set_how_know_us(NULL);
		mPortal.set_created_time(0);
		mPortal.set_last_login(0);
		mPortal.set_is_admin(false);
	}

	CAdminDataList *
		AdminDataList = ADMIN_TOOL->get_admin_list();
	for (int i=AdminDataList->length()-1 ; i>=0 ; i--)
	{
		CAdminData *
			AdminData = (CAdminData *)AdminDataList->get(i);

		CString
			QueryString;
		QueryString.format("ADMIN_TYPE%d", i);

		QUERY((char *)QueryString, AdminTypeString);
		if (AdminTypeString != NULL)
		{
			if (!strcmp(AdminTypeString, "SUPER_USER")) AdminData->set_admin_type("S");
			if (!strcmp(AdminTypeString, "MODERATOR")) AdminData->set_admin_type("M");
			if (!strcmp(AdminTypeString, "DEPRIVATION"))
			{
				AdminDataList->remove_admin_data(AdminData);
			}
		}
	}

	QUERY("NEW_ADMIN_PORTAL_ID", NewAdminPortalIDString);
	int
		NewAdminPortalID = as_atoi(NewAdminPortalIDString);
	CPlayer *
		NewAdmin = PLAYER_TABLE->get_by_portal_id(NewAdminPortalID);
	QUERY("NEW_ADMIN_TYPE", NewAdminTypeString);

	if (NewAdminPortalID > 0 && NewAdmin != NULL && NewAdminTypeString != NULL)
	{
		if (!strcmp(NewAdminTypeString, "SUPER_USER"))
		{
			CAdminData *
				AdminData = new CAdminData("S", NewAdminPortalID);
			ADMIN_TOOL->add_admin_data(AdminData);
		}
		else if (!strcmp(NewAdminTypeString, "MODERATOR"))
		{
			CAdminData *
				AdminData = new CAdminData("M", NewAdminPortalID);
			ADMIN_TOOL->add_admin_data(AdminData);
		}
	}

	ADMIN_TOOL->save_admin_list();

	CIPList *
		BannedIPList = ADMIN_TOOL->get_banned_ip_list();
	for (int i=BannedIPList->length()-1 ; i>=0 ; i--)
	{
		CIP *
			BannedIP = (CIP *)BannedIPList->get(i);

		CString
			QueryString;
		QueryString.format("BANNED_IP%d", i);

		QUERY((char *)QueryString, BannedIPString);
		if (BannedIPString != NULL)
		{
			if (!strcasecmp(BannedIPString, "ON"))
			{
				BannedIPList->remove_ip(BannedIP->get_address());
			}
		}
	}

	QUERY("NEW_BANNED_IP", NewBannedIPString);

	if (NewBannedIPString != NULL)
	{
		ADMIN_TOOL->add_banned_ip((char *)NewBannedIPString);
	}

	ADMIN_TOOL->save_banned_ip_list();

	ITEM("STRING_ADMIN_TOOL", GETTEXT("Admin Tool"));

	ITEM("STRING_INSPECT", GETTEXT("Inspect"));
	ITEM("STRING_BLACK_MARKET", GETTEXT("Black Market"));

	ITEM("STRING_ADMIN_SETTING", GETTEXT("Admin Setting"));
	ITEM("STRING_SUPER_USER", GETTEXT("Super User"));
	ITEM("STRING_MODERATOR", GETTEXT("Moderator"));

	CString
		AdminList;
	AdminList = ADMIN_TOOL->admin_list_html();
	if (AdminList.length() == 0)
	{
		ITEM("ADMIN_LIST", " ");
	}
	else
	{
		ITEM("ADMIN_LIST", (char *)AdminList);
	}

	ITEM("STRING_BANNED_IP", GETTEXT("Banned IP"));

	CString
		BannedIPListHTML;
	BannedIPListHTML = ADMIN_TOOL->banned_ip_list_html();
	if (BannedIPListHTML.length() == 0)
	{
		ITEM("BANNED_IP_LIST", " ");
	}
	else
	{
		ITEM("BANNED_IP_LIST", (char *)BannedIPListHTML);
	}

	ITEM("STRING_CONFIG", GETTEXT("Config"));
	ITEM("STRING_REBOOT", GETTEXT("Reboot"));
	ITEM("STRING_RESET", GETTEXT("Reset"));

	ITEM("STRING_THE_CONQUEROR_S_NAME", GETTEXT("The Conqueror's Name"));
	ITEM("STRING_THE_NAME_OF_THE_CONQUEROR_S_COUNCIL",
			GETTEXT("The Name Of The Conqueror's Council"));

	ITEM("STRING_BROADCAST", GETTEXT("Broadcast"));
	ITEM("STRING_MESSAGE", GETTEXT("Message"));
	ITEM("STRING_MAIL", GETTEXT("Mail"));

//	system_log("end page handler %s", get_name());

	return output("admin/admin.html");
}
