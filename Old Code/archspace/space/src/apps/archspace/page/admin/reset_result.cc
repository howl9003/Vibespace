#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../race.h"

bool
CPageResetResult::handler(CPlayer *aPlayer)
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
	LegalReferer.format("http://%s/archspace/admin/reset_confirm.as",
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

	QUERY("RESET", ResetString);
	if (ResetString == NULL)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You seem to have approached this page in a wrong way."));
		return output("admin/admin_error.html");
	}
	if (strcmp((char *)ResetString, "RESET"))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You seem to have approached this page in a wrong way."));
		return output("admin/admin_error.html");
	}

	QUERY("CONQUEROR_NAME", ConquerorNameString);
	if (ConquerorNameString == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't specify the conqueror's name."));
		return output("admin/admin_error.html");
	}

	QUERY("CONQUEROR_COUNCIL_NAME", ConquerorCouncilNameString);
	if (ConquerorCouncilNameString == NULL)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You didn't specify a name of the conqueror's council."));
		return output("admin/admin_error.html");
	}

	CCouncil *
		ConquerorCouncil = new CCouncil();
	ConquerorCouncil->set_name((char *)ConquerorCouncilNameString);
	COUNCIL_TABLE->add_council(ConquerorCouncil);

	CPlayer *
		Conqueror = new CPlayer(PLAYER_TABLE->get_max_id() + 1);
	CRace *
		RandomRace = (CRace *)RACE_TABLE->get(number(RACE_TABLE->length()) - 1);
	Conqueror->set_name((char *)ConquerorNameString);
	Conqueror->set_race(RandomRace->get_id());
	Conqueror->set_council(ConquerorCouncil);

	PLAYER_TABLE->add_player(Conqueror);

	Conqueror->type(QUERY_INSERT);
	STORE_CENTER->store(*Conqueror);

	ConquerorCouncil->type(QUERY_INSERT);
	STORE_CENTER->store(*ConquerorCouncil);

	EMPIRE_CAPITAL_PLANET->set_owner_id(Conqueror->get_game_id());

	EMPIRE_CAPITAL_PLANET->type(QUERY_UPDATE);
	STORE_CENTER->store(*EMPIRE_CAPITAL_PLANET);

	ITEM("RESULT_MESSAGE",
			(char *)format(GETTEXT("The Empire has been conquered by %1$s. Now the game is over."),
							Conqueror->get_nick()));

//	system_log("end page handler %s", get_name());

	return output("admin/reset_result.html");
}
