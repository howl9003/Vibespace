#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"
#include "../../player.h"
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>

bool
CPagePlayer::handler(CPlayer *aPlayer)
{
//	system_log("start page handler %s", get_name());

	CQueryList &
		IDString = CONNECTION->id_string();
	char *
		Admin = IDString.get_value("IS_ADMIN");

	if (!Admin)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}
	if (strcmp(Admin, "YES"))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/admin_error.html");
	}

	QUERY("PLAYER_INIT", PlayerInitString);
	if (PlayerInitString)
	{
		if (!strcmp(PlayerInitString, "YES"))
		{
			QUERY("ADMIN_TOOL_ID", AdminToolIDString);
			int
				ID = as_atoi(AdminToolIDString);

			QUERY("ADMIN_TOOL_NAME", AdminToolNameString);
			char *
				Name = (char *)AdminToolNameString;
			if (Name == NULL) Name = GETTEXT("N/A");

			QUERY("ADMIN_TOOL_EMAIL", AdminToolEMailString);
			char *
				EMail = (char *)AdminToolEMailString;
			if (EMail == NULL) EMail = GETTEXT("N/A");

			QUERY("ADMIN_TOOL_FIRST_NAME", AdminToolFirstNameString);
			char *
				FirstName = (char *)AdminToolFirstNameString;
			if (FirstName == NULL) FirstName = GETTEXT("N/A");

			QUERY("ADMIN_TOOL_LAST_NAME", AdminToolLastNameString);
			char *
				LastName = (char *)AdminToolLastNameString;
			if (LastName == NULL) LastName = GETTEXT("N/A");

			QUERY("ADMIN_TOOL_ICQ", AdminToolICQString);
			int
				ICQ = as_atoi(AdminToolICQString);

			QUERY("ADMIN_TOOL_GENDER", AdminToolGenderString);
			char *
				Gender = (char *)AdminToolGenderString;
			if (Gender == NULL) Gender = GETTEXT("N/A");

			QUERY("ADMIN_TOOL_AGE", AdminToolAgeString);
			int
				Age = as_atoi(AdminToolAgeString);

			QUERY("ADMIN_TOOL_COUNTRY", AdminToolCountryString);
			char *
				Country = (char *)AdminToolCountryString;
			if (Country == NULL) Country = GETTEXT("N/A");

			QUERY("ADMIN_TOOL_HOW_KNOW_US", AdminToolHowKnowUsString);
			char *
				HowKnowUs = (char *)AdminToolHowKnowUsString;
			if (HowKnowUs == NULL) HowKnowUs = GETTEXT("N/A");

			QUERY("ADMIN_TOOL_CREATED_TIME", AdminToolCreatedTimeString);
			time_t
				CreatedTime = (time_t)as_atoi(AdminToolCreatedTimeString);

			QUERY("ADMIN_TOOL_LAST_LOGIN", AdminToolLastLoginString);
			time_t
				LastLogin = (time_t)as_atoi(AdminToolLastLoginString);

			QUERY("ADMIN_TOOL_IS_ADMIN", AdminToolIsAdminString);
			bool
				IsAdmin;
			if (!AdminToolIsAdminString)
			{
				IsAdmin = false;
			}
			else if (!strcmp(AdminToolIsAdminString, "YES"))
			{
				IsAdmin = true;
			}
			else IsAdmin = false;

			mPortal.set_id(ID);
			mPortal.set_name(Name);
			mPortal.set_email(EMail);
			mPortal.set_first_name(FirstName);
			mPortal.set_last_name(LastName);
			mPortal.set_icq(ICQ);
			mPortal.set_gender(Gender);
			mPortal.set_age(Age);
			mPortal.set_country(Country);
			mPortal.set_how_know_us(HowKnowUs);
			mPortal.set_created_time(CreatedTime);
			mPortal.set_last_login(LastLogin);
			mPortal.set_is_admin(IsAdmin);
		}
	}

	QUERY("BY_WHAT", ByWhatString);

	if (ByWhatString)
	{
		if (!strcmp(ByWhatString, "PORTAL_NAME"))
		{
			QUERY("NEW_PORTAL_NAME", NewPortalNameString);
			char *
				PortalName = (char *)NewPortalNameString;

			if (!PortalName)
			{
				ITEM("ERROR_MESSAGE",
						GETTEXT("You didn't enter a portal name."));
				return output("admin/admin_error.html");
			}

			mPortal.set_name(PortalName);
		}
		else if (!strcmp(ByWhatString, "EMAIL"))
		{
			QUERY("NEW_EMAIL", NewEMailString);
			char *
				EMail = (char *)NewEMailString;

			if (!EMail)
			{
				ITEM("ERROR_MESSAGE",
						GETTEXT("You didn't enter an E-mail."));
				return output("admin/admin_error.html");
			}

			mPortal.set_email(EMail);
		}
		else if (!strcmp(ByWhatString, "IS_ADMIN"))
		{
			QUERY("NEW_IS_ADMIN", NewIsAdmin);

			bool
				IsAdmin;
			if (!NewIsAdmin)
			{
				IsAdmin = false;
			}
			else if (!strcmp(NewIsAdmin, "YES"))
			{
				IsAdmin = true;
			}
			else IsAdmin = false;

			mPortal.set_is_admin(IsAdmin);
		}
	}

	if (ByWhatString)
	{
		CPlayer *
			Player = PLAYER_TABLE->get_by_portal_id(mPortal.get_id());

		if (Player)
		{
			if (Player->get_game_id() != EMPIRE_GAME_ID)
			{
				if (!strcmp(ByWhatString, "IS_BANNED"))
				{
					QUERY("NEW_IS_BANNED", NewIsBanned);

					CIPList *
						IPBanList = ADMIN_TOOL->get_banned_ip_list();

					if (NewIsBanned)
					{
						if (!strcmp(NewIsBanned, "YES"))
						{
							if (IPBanList->has(inet_addr(Player->get_last_login_ip())) == false)
							{
								IPBanList->add_ip(Player->get_last_login_ip());
								IPBanList->save();
							}
						}
						else if (!strcmp(NewIsBanned, "NO"))
						{
							if (IPBanList->has(inet_addr(Player->get_last_login_ip())) == true)
							{
								IPBanList->remove_ip(inet_addr(Player->get_last_login_ip()));
								IPBanList->save();
							}
						}
					}
				}
				else if (!strcmp(ByWhatString, "PRODUCTION_POINT"))
				{
					QUERY("NEW_PRODUCTION_POINT", NewProductionPointString);

					int
						NewProductionPoint = as_atoi(NewProductionPointString);
					Player->set_production(NewProductionPoint);
				}
				else if (!strcmp(ByWhatString, "HONOR"))
				{
					QUERY("NEW_HONOR", NewHonorString);

					int
						NewHonor = as_atoi(NewHonorString);
					Player->set_honor(NewHonor);
				}
				else if (!strcmp(ByWhatString, "SHIP_POOL_PRODUCTION"))
				{
					QUERY("NEW_SHIP_POOL_PRODUCTION", NewShipPoolProductionString);

					int
						NewShipPoolProduction = as_atoi(NewShipPoolProductionString);
					Player->set_ship_production(NewShipPoolProduction);
				}
				else if (!strcmp(ByWhatString, "GIVE_ALL_TECHS"))
				{
					CTechList *
						AvailableTechList = Player->get_available_tech_list();
					while (AvailableTechList->length() > 0)
					{
						CTech *
							Tech = (CTech *)AvailableTechList->get(0);
						Player->discover_tech(Tech->get_id());
					}
				}
				else if (!strcmp(ByWhatString, "EXPEDITION"))
				{
					int
						ClusterID = Player->find_new_planet(true);
					if (ClusterID > 0)
					{
						CPlanet *
							Planet = new CPlanet();
						CCluster *
							Cluster = UNIVERSE->get_by_id(ClusterID);

						Planet->initialize();
						Planet->set_cluster(Cluster);
						Planet->set_name(Cluster->get_new_planet_name());

						PLANET_TABLE->add_planet(Planet);
						Player->add_planet(Planet);
						Planet->start_terraforming();

						Planet->type(QUERY_INSERT);
						STORE_CENTER->store(*Planet);

						Player->new_planet_news(Planet);
					}
				}
				else if (!strcmp(ByWhatString, "ADMIRAL"))
				{
					CAdmiralList *
						AdmiralPool = Player->get_admiral_pool();
					CAdmiral *
						Admiral = new CAdmiral(Player);
					AdmiralPool->add_admiral(Admiral);

					Admiral->type(QUERY_INSERT);
					STORE_CENTER->store(*Admiral);

					Player->new_admiral_news(Admiral);
				}
			}
		}
	}

	ITEM("PORTAL_INFORMATION_MESSAGE",
			(char *)format(GETTEXT("The portal information of account %1$s(%2$s)."),
							mPortal.get_name(),
							dec2unit(mPortal.get_id())));

	ITEM("STRING_PORTAL_ID", GETTEXT("Portal ID"));
	ITEM("PORTAL_ID", mPortal.get_id());

	ITEM("STRING_PORTAL_NAME", GETTEXT("Portal Name"));
	ITEM("PORTAL_NAME", mPortal.get_name());

	ITEM("STRING_E_MAIL", GETTEXT("E-Mail"));
	ITEM("E_MAIL", mPortal.get_email());

	ITEM("STRING_FIRST_NAME", GETTEXT("First Name"));
	ITEM("FIRST_NAME", mPortal.get_first_name());

	ITEM("STRING_LAST_NAME", GETTEXT("Last Name"));
	ITEM("LAST_NAME", mPortal.get_last_name());

	ITEM("STRING_ICQ", GETTEXT("ICQ"));
	ITEM("ICQ", mPortal.get_icq());

	ITEM("STRING_GENDER", GETTEXT("Gender"));
	if (mPortal.get_gender())
	{
		ITEM("GENDER", mPortal.get_gender());
	}
	else
	{
		ITEM("GENDER", GETTEXT("(Not entered)"));
	}

	ITEM("STRING_AGE", GETTEXT("Age"));
	ITEM("AGE", mPortal.get_age());

	ITEM("STRING_COUNTRY", GETTEXT("Country"));
	if (mPortal.get_country())
	{
		ITEM("COUNTRY", mPortal.get_country());
	}
	else
	{
		ITEM("COUNTRY", GETTEXT("(Not entered)"));
	}

	ITEM("STRING_JOB", GETTEXT("Job"));
	if (mPortal.get_job())
	{
		ITEM("JOB", mPortal.get_job());
	}
	else
	{
		ITEM("JOB", GETTEXT("(Not entered)"));
	}

	ITEM("STRING_HOW_KNOW_US", GETTEXT("How Know Us"));
	if (mPortal.get_how_know_us())
	{
		ITEM("HOW_KNOW_US", mPortal.get_how_know_us());
	}
	else
	{
		ITEM("HOW_KNOW_US", GETTEXT("(Not entered)"));
	}

	char TimeString[60];
	time_t TimeT;
	struct tm *Time;

	TimeT = mPortal.get_created_time();
	Time = localtime(&TimeT);
	strftime(TimeString, 60, "%Y/%m/%d&nbsp;%r", Time);

	ITEM("STRING_CREATED_TIME", GETTEXT("Created Time"));
	ITEM("CREATED_TIME", TimeString);

	TimeT = mPortal.get_last_login();
	Time = localtime(&TimeT);
	strftime(TimeString, 60, "%Y/%m/%d&nbsp;%r", Time);

	ITEM("STRING_LAST_LOGIN", GETTEXT("Last Login"));
	ITEM("LAST_LOGIN", TimeString);

	ITEM("STRING_IS_ADMIN", GETTEXT("Is Admin"));
	if (mPortal.get_is_admin() == true)
	{
		ITEM("IS_ADMIN", GETTEXT("Admin"));
	}
	else
	{
		ITEM("IS_ADMIN", GETTEXT("Not Admin"));
	}

	ITEM("PLAYER_INFORMATION_MESSAGE",
			(char *)format(GETTEXT("The player information of account %1$s(%2$s)."),
							mPortal.get_name(),
							dec2unit(mPortal.get_id())));

	static CString
		PlayerInfo;
	PlayerInfo.clear();

	CPlayer *
		Player = PLAYER_TABLE->get_by_portal_id(mPortal.get_id());

	if (!Player)
	{
		PlayerInfo = "<TR>\n";
		PlayerInfo += "<TD ALIGN=\"center\">\n";

		PlayerInfo += "<TABLE WIDTH=\"550\"\n";
		PlayerInfo += "<TR>\n";
		PlayerInfo.format("<TD CLASS=\"maintext\" ALIGN=\"center\">%s</TD>\n", GETTEXT("This account has no character."));
		PlayerInfo += "</TR>\n";
		PlayerInfo += "</TABLE>\n";

		PlayerInfo += "</TD>\n";
		PlayerInfo += "</TR>\n";
	}
	else
	{
		PlayerInfo = "<TR>\n";
		PlayerInfo += "<TD ALIGN=\"center\">\n";

		PlayerInfo += "<TABLE WIDTH=\"550\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"0\" BORDERCOLOR=\"#2A2A2A\">\n";
		PlayerInfo += "<TR>\n";

		PlayerInfo += "<TD CLASS=\"tabletxt\" WIDTH=\"150\" BGCOLOR=\"#171717\">\n";
		PlayerInfo.format("<FONT COLOR=\"666666\">&nbsp;%s</FONT>\n",
							GETTEXT("Race"));
		PlayerInfo += "</TD>\n";

		PlayerInfo.format("<TD CLASS=\"tabletxt\">&nbsp;%s</TD>\n",
							Player->get_race_name());

		PlayerInfo += "</TR>\n";

		PlayerInfo += "<FORM METHOD=post ACTION=player.as>\n";
		PlayerInfo += "<TR>\n";

		PlayerInfo += "<TD CLASS=\"tabletxt\" WIDTH=\"150\" BGCOLOR=\"#171717\">\n";
		PlayerInfo.format("<FONT COLOR=\"666666\">&nbsp;%s</FONT>\n",
							GETTEXT("Production Point"));
		PlayerInfo += "</TD>\n";

		PlayerInfo.format("<TD CLASS=\"tabletxt\">&nbsp;%s<BR>\n",
							dec2unit(Player->get_production()));

		PlayerInfo += "<INPUT TYPE=input NAME=NEW_PRODUCTION_POINT>\n";
		PlayerInfo += "<INPUT TYPE=hidden NAME=BY_WHAT VALUE=PRODUCTION_POINT>\n";
		PlayerInfo.format("<INPUT TYPE=image SRC=\"http://%s/image/as_game/bu_change.gif\">\n",
							(char *)CGame::mImageServerURL);
		PlayerInfo += "</TD>\n";

		PlayerInfo += "</TR>\n";
		PlayerInfo += "</FORM>\n";

		PlayerInfo += "<FORM METHOD=post ACTION=player.as>\n";
		PlayerInfo += "<TR>\n";

		PlayerInfo += "<TD CLASS=\"tabletxt\" WIDTH=\"150\" BGCOLOR=\"#171717\">\n";
		PlayerInfo.format("<FONT COLOR=\"666666\">&nbsp;%s</FONT>\n",
							GETTEXT("Honor"));
		PlayerInfo += "</TD>\n";

		PlayerInfo.format("<TD CLASS=\"tabletxt\">&nbsp;%s<BR>\n",
							dec2unit(Player->get_honor()));

		PlayerInfo += "<INPUT TYPE=input NAME=NEW_HONOR>\n";
		PlayerInfo += "<INPUT TYPE=hidden NAME=BY_WHAT VALUE=HONOR>\n";
		PlayerInfo.format("<INPUT TYPE=image SRC=\"http://%s/image/as_game/bu_change.gif\">\n",
							(char *)CGame::mImageServerURL);
		PlayerInfo += "</TD>\n";

		PlayerInfo += "</TR>\n";
		PlayerInfo += "</FORM>\n";

		PlayerInfo += "<FORM METHOD=post ACTION=player.as>\n";
		PlayerInfo += "<TR>\n";

		PlayerInfo += "<TD CLASS=\"tabletxt\" WIDTH=\"150\" BGCOLOR=\"#171717\">\n";
		PlayerInfo.format("<FONT COLOR=\"666666\">&nbsp;%s</FONT>\n",
							GETTEXT("Ship Pool Production"));
		PlayerInfo += "</TD>\n";

		PlayerInfo.format("<TD CLASS=\"tabletxt\">&nbsp;%s<BR>\n",
							dec2unit(Player->get_ship_production()));

		PlayerInfo += "<INPUT TYPE=input NAME=NEW_SHIP_POOL_PRODUCTION>\n";
		PlayerInfo += "<INPUT TYPE=hidden NAME=BY_WHAT VALUE=SHIP_POOL_PRODUCTION>\n";
		PlayerInfo.format("<INPUT TYPE=image SRC=\"http://%s/image/as_game/bu_change.gif\">\n",
							(char *)CGame::mImageServerURL);
		PlayerInfo += "</TD>\n";

		PlayerInfo += "</TR>\n";
		PlayerInfo += "</FORM>\n";

		PlayerInfo += "<FORM METHOD=post ACTION=player.as>\n";
		PlayerInfo += "<TR>\n";

		PlayerInfo += "<TD CLASS=\"tabletxt\" WIDTH=\"150\" BGCOLOR=\"#171717\">\n";
		PlayerInfo.format("<FONT COLOR=\"666666\">&nbsp;%s</FONT>\n",
							GETTEXT("The number of Techs"));
		PlayerInfo += "</TD>\n";

		CKnownTechList *
			KnownTechList = Player->get_tech_list();
		PlayerInfo.format("<TD CLASS=\"tabletxt\">&nbsp;%s<BR>\n",
							dec2unit(KnownTechList->length()));
		PlayerInfo.format("<A HREF=\"player_research_status.as?PLAYER_ID=%d\">%s</A><BR>\n",
							Player->get_game_id(),
							GETTEXT("To see this player's research status"));

		PlayerInfo += "<INPUT TYPE=hidden NAME=BY_WHAT VALUE=GIVE_ALL_TECHS>\n";
		PlayerInfo.format("<INPUT TYPE=image SRC=\"http://%s/image/as_game/bu_change.gif\">\n",
							(char *)CGame::mImageServerURL);
		PlayerInfo += "</TD>\n";

		PlayerInfo += "</TR>\n";
		PlayerInfo += "</FORM>\n";

		PlayerInfo += "<FORM METHOD=post ACTION=player.as>\n";
		PlayerInfo += "<TR>\n";

		PlayerInfo += "<TD CLASS=\"tabletxt\" WIDTH=\"150\" BGCOLOR=\"#171717\">\n";
		PlayerInfo.format("<FONT COLOR=\"666666\">&nbsp;%s</FONT>\n",
							GETTEXT("# of Planet(s)"));
		PlayerInfo += "</TD>\n";

		CPlanetList *
			PlanetList = Player->get_planet_list();
		PlayerInfo.format("<TD CLASS=\"tabletxt\">&nbsp;%s<BR>",
							dec2unit(PlanetList->length()));

		PlayerInfo += "<INPUT TYPE=hidden NAME=BY_WHAT VALUE=EXPEDITION>\n";
		PlayerInfo.format("<INPUT TYPE=image SRC=\"http://%s/image/as_game/bu_expedition.gif\">\n",
							(char *)CGame::mImageServerURL);
		PlayerInfo += "</TD>\n";

		PlayerInfo += "</TR>\n";
		PlayerInfo += "</FORM>\n";

		PlayerInfo += "<FORM METHOD=post ACTION=player.as>\n";
		PlayerInfo += "<TR>\n";

		PlayerInfo += "<TD CLASS=\"tabletxt\" WIDTH=\"150\" BGCOLOR=\"#171717\">\n";
		PlayerInfo.format("<FONT COLOR=\"666666\">&nbsp;%s</FONT>\n",
							GETTEXT("# of Admiral(s) in the Pool"));
		PlayerInfo += "</TD>\n";

		CAdmiralList *
			AdmiralList = Player->get_admiral_pool();
		PlayerInfo.format("<TD CLASS=\"tabletxt\">&nbsp;%s<BR>",
							dec2unit(AdmiralList->length()));

		PlayerInfo += "<INPUT TYPE=hidden NAME=BY_WHAT VALUE=ADMIRAL>\n";
		PlayerInfo.format("<INPUT TYPE=image SRC=\"http://%s/image/as_game/bu_change.gif\">\n",
							(char *)CGame::mImageServerURL);
		PlayerInfo += "</TD>\n";

		PlayerInfo += "</TR>\n";
		PlayerInfo += "</FORM>\n";

		PlayerInfo += "<TR>\n";

		PlayerInfo += "<TD CLASS=\"tabletxt\" WIDTH=\"150\" BGCOLOR=\"#171717\">\n";
		PlayerInfo.format("<FONT COLOR=\"666666\">&nbsp;%s</FONT>\n",
							GETTEXT("Council"));
		PlayerInfo += "</TD>\n";

		PlayerInfo += "<TD CLASS=\"tabletxt\">\n";
		PlayerInfo += "<FORM METHOD=post ACTION=\"council.as\">\n";
		CCouncil *
			Council = Player->get_council();
		PlayerInfo.format("%s\n", Council->get_nick());
		PlayerInfo.format("<INPUT TYPE=hidden NAME=COUNCIL_ID VALUE=%d>\n",
							Council->get_id());
		PlayerInfo.format("<INPUT TYPE=image SRC=\"http://%s/image/as_game/bu_view.gif\">\n",
							(char *)CGame::mImageServerURL);
		PlayerInfo += "</FORM>\n";
		PlayerInfo += "</TD>\n";
		PlayerInfo += "</TR>\n";

		PlayerInfo += "<FORM METHOD=post ACTION=player.as>\n";
		PlayerInfo += "<TR>\n";

		PlayerInfo += "<TD CLASS=\"tabletxt\" WIDTH=\"150\" BGCOLOR=\"#171717\">\n";
		PlayerInfo.format("<FONT COLOR=\"666666\">&nbsp;%s</FONT>\n",
							GETTEXT("IP Banned"));
		PlayerInfo += "</TD>\n";

		CIPList *
			IPBanList = ADMIN_TOOL->get_banned_ip_list();

		if (IPBanList->has(inet_addr(Player->get_last_login_ip())))
		{
			PlayerInfo.format("<TD CLASS=\"tabletxt\">&nbsp;%s(%s : %s)<BR>\n",
								GETTEXT("Banned"),
								GETTEXT("Last Login IP"),
								Player->get_last_login_ip());
		}
		else
		{
			PlayerInfo.format("<TD CLASS=\"tabletxt\">&nbsp;%s(%s : %s)<BR>\n",
								GETTEXT("Not Banned"),
								GETTEXT("Last Login IP"),
								Player->get_last_login_ip());
		}

		PlayerInfo += "<SELECT NAME=NEW_IS_BANNED>\n";
		PlayerInfo += "<OPTION VALUE=\"YES\">Yes</OPTION>\n";
		PlayerInfo += "<OPTION VALUE=\"NO\">No</OPTION>\n";
		PlayerInfo += "</SELECT>\n";
		PlayerInfo += "<INPUT TYPE=hidden NAME=BY_WHAT VALUE=IS_BANNED>\n";
		PlayerInfo.format("<INPUT TYPE=image SRC=\"http://%s/image/as_game/bu_change.gif\">\n",
							(char *)CGame::mImageServerURL);
		PlayerInfo += "</TD>\n";

		PlayerInfo += "</TR>\n";
		PlayerInfo += "</FORM>\n";

		PlayerInfo += "</TABLE>\n";

		PlayerInfo += "</TD>\n";
		PlayerInfo += "</TR>\n";

		PlayerInfo += "<TR>\n";
		PlayerInfo += "<TD CLASS=\"maintxt\" ALIGN=\"center\">\n";
		PlayerInfo += "<A HREF=\"player_see_domestic.as\">";
		PlayerInfo.format(GETTEXT("See %1$s's domestic status"), Player->get_nick());
		PlayerInfo += "</A>\n";
		PlayerInfo += "</TD>\n";
		PlayerInfo += "</TR>\n";

		PlayerInfo += "<TR>\n";
		PlayerInfo += "<TD CLASS=\"maintxt\" ALIGN=\"center\">\n";
		PlayerInfo += "<A HREF=\"player_message.as\">";
		PlayerInfo.format(GETTEXT("See %1$s's messages"), Player->get_nick());
		PlayerInfo += "</A>\n";
		PlayerInfo += "</TD>\n";
		PlayerInfo += "</TR>\n";

		PlayerInfo += "<TR>\n";
		PlayerInfo += "<TD CLASS=\"maintxt\" ALIGN=\"center\">\n";
		PlayerInfo += "<A HREF=\"player_punishment.as\">";
		PlayerInfo.format(GETTEXT("Punish %1$s"), Player->get_nick());
		PlayerInfo += "</A>\n";
		PlayerInfo += "</TD>\n";
		PlayerInfo += "</TR>\n";
	}

	ITEM("PLAYER_INFO", (char *)PlayerInfo);

	ITEM("SEE_MESSAGES_MESSAGE", GETTEXT("See Messages"));

	ITEM("FINAL_PORTAL_ID", mPortal.get_id());
	ITEM("FINAL_PORTAL_NAME", mPortal.get_name());
	ITEM("FINAL_PORTAL_PASSWORD", mPortal.get_password());
	ITEM("FINAL_EMAIL", mPortal.get_email());
	if (mPortal.get_is_admin() == true)
	{
		ITEM("FINAL_IS_ADMIN", "YES");
	}
	else
	{
		ITEM("FINAL_IS_ADMIN", "NO");
	}

	ITEM("SEND_MESSAGE_MESSAGE",
			GETTEXT("To send a message to this player, please click <A HREF=\"player_send_message.as\">this</A>."));

	ITEM("SEND_MAIL_MESSAGE",
			(char *)format(GETTEXT("To send a mail to this player, please click <A HREF=\"mailto:%1$s\">this</A>."),
					mPortal.get_email()));

	ITEM("MESSAGE_PORTAL_ID", mPortal.get_id());

//	system_log("end page handler %s", get_name());

	return output("admin/player.html");
}
