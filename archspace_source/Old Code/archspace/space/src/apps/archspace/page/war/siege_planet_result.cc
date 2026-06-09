#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"
#include "../../battle.h"
#include <cstdio>

bool
CPageSiegePlanetResult::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name() );

	if (aPlayer->has_siege_blockade_restriction() == true)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("Your officers are too busy to plan a new offensive, because your newly conquered colony needs much management to be settled down."));

		return output("war/war_error.html");
	}

	QUERY("MODE", ModeString);
	if (!ModeString)
	{
		ITEM("RESULT_MESSAGE",
			GETTEXT("You decided not to attack the enemy.<BR>"
					"Your fleets are returning home without attacking the planet.<BR>"
					"After some hours, your fleets will be on stand-by again."));

		return output("war/siege_planet_planet_return.html");
	}

	if (!strcasecmp("CANCEL", ModeString))
	{
		ITEM("RESULT_MESSAGE",
			GETTEXT("You decided not to attack the enemy.<BR>"
					"Your fleets are returning home without attacking the planet.<BR>"
					"After some hours, your fleets will be on stand-by again."));

		return output("war/siege_planet_planet_return.html");
	}

	QUERY("PLANET_ID", PlanetIDString);
	int
		PlanetID = as_atoi(PlanetIDString);
	CHECK((PlanetID<=0),
			GETTEXT("The planet's ID was not valid."));

	CPlanet *
		Planet = PLANET_TABLE->get_by_id(PlanetID);
	CHECK(!Planet,
			(char *)format(
					GETTEXT("There is no planet with ID %1$s."),
					dec2unit(PlanetID)));

	CPlayer *
		TargetPlayer = Planet->get_owner();
	CHECK(!TargetPlayer,
			(char *)format(
					GETTEXT("The planet with ID %1$s has no owner."
							" Please ask Archspace Development Team."),
					dec2unit(PlanetID)));

	CHECK(TargetPlayer->get_game_id() == EMPIRE_GAME_ID,
			GETTEXT("You can't attack the Empire in this menu."));

	CHECK(TargetPlayer->get_game_id() == aPlayer->get_game_id(),
			GETTEXT("You can't attack yourself."));

	CHECK(TargetPlayer->is_dead(), 
				format(GETTEXT("%1$s is dead."), TargetPlayer->get_nick()));

	if (TargetPlayer->has_siege_blockade_protection() == true)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("Recently the player %1$s had a planetary siege battle in his/her domain. You decided not to move your armada there until you get a clear information."),
								TargetPlayer->get_nick()));

		return output("war/war_error.html");
	}

    CString Attack; 
    Attack = aPlayer->check_attackable(TargetPlayer);
    CHECK(Attack.length(), Attack);

	CHECK(!TargetPlayer->is_border_area(PlanetID),
			(char *)format(
					GETTEXT("The planet %1$s is not a border planet."),
					Planet->get_name()));

    CCouncil *
		TargetPlayerCouncil = TargetPlayer->get_council();
    CRelation* Relation;

    if (aPlayer->get_council() == TargetPlayer->get_council())
    {
        Relation = aPlayer->get_relation(TargetPlayer);
        CHECK(!Relation, 
            (char*)format(GETTEXT("You have no relation with %1$s."), 
                                TargetPlayer->get_nick()));

        CHECK(Relation->get_relation() != CRelation::RELATION_WAR,
            (char*)format(GETTEXT("You are not at war with %1$s."), 
                                TargetPlayer->get_nick()));
    }
	else
	{
        Relation = TargetPlayerCouncil->get_relation(aPlayer->get_council());

	CHECK(!Relation, 
			(char *)format(GETTEXT("Your council has no relationship with %1$s's council."), 
							TargetPlayer->get_nick()));

        CHECK(Relation->get_relation() != CRelation::RELATION_WAR &&
                Relation->get_relation() != CRelation::RELATION_TOTAL_WAR,
            (char*)format(GETTEXT("Your council is not at war or total war with %1$s's council."), 
                                TargetPlayer->get_nick()));
    }	
				
	QUERY("FLEET_NUMBER", FleetNumberString)
	int FleetNumber = as_atoi(FleetNumberString);
	CHECK(!FleetNumber, GETTEXT("Wrong fleet number received."
								" Please ask Archspace Development Team."));


	CBattle
		Battle(CBattle::WAR_SIEGE, aPlayer, TargetPlayer, (void *)Planet);

	CFleetList
		*AttackerFleetList = aPlayer->get_fleet_list(),
		*DefenderFleetList = TargetPlayer->get_fleet_list();
	CAdmiralList
		*AttackerAdmiralList = aPlayer->get_admiral_list(),
		*DefenderAdmiralList = TargetPlayer->get_admiral_list();

	CDefensePlan
		OffensePlan;
	OffensePlan.set_owner(aPlayer->get_game_id());

	for (int i=1 ; i<=FleetNumber ; i++)
	{
		char Query[100];
		
		sprintf(Query, "FLEET%d_O", i);
		QUERY(Query, CommandString);

		sprintf(Query, "FLEET%d_X", i);
		QUERY(Query, LocationXString);

		sprintf(Query, "FLEET%d_Y", i);
		QUERY(Query, LocationYString);

		sprintf(Query, "FLEET%d_ID", i);
		QUERY(Query, FleetIDString);

		CHECK(!CommandString ||
				!LocationXString ||
				!LocationYString ||
				!FleetIDString,
				GETTEXT("1 or more fleets' deployment data were not found."));

		int
			Command = as_atoi(CommandString),
			LocationX = as_atoi(LocationXString),
			LocationY = as_atoi(LocationYString),
			FleetID = as_atoi(FleetIDString);

		/*SLOG("Command:%d, LocationX:%d, LocationY:%d, FleetID:%d",
				Command, LocationX, LocationY, FleetID);*/

		CFleet *
			Fleet = (CFleet *)AttackerFleetList->get_by_id(FleetID);

		CHECK(!Fleet,
				(char *)format(
						GETTEXT("You don't have the fleet with ID %1$s."),
						dec2unit(FleetID)));

		CHECK(Command < CDefenseFleet::COMMAND_NORMAL ||
			Command >= CDefenseFleet::COMMAND_MAX,
					(char *)format(GETTEXT("The fleet %1$s has a wrong command."),
									Fleet->get_name()));

		CHECK(LocationX < 9 || LocationX > 609 ||
			LocationY < 226 || LocationY > 426,
			format(GETTEXT("%1$s has wrong location in the battle field."), Fleet->get_nick()));

		CMission &Mission = Fleet->get_mission();
		CHECK(Mission.get_mission() != CMission::MISSION_SORTIE,
			format(GETTEXT("%1$s did not make a sortie."), Fleet->get_nick()));

#define SHIP_SIZE_CRUISER	5
		if (Planet->get_cluster_id() != aPlayer->get_home_cluster_id())
		{
			if (Fleet->get_size() < SHIP_SIZE_CRUISER)
			{
				ITEM("ERROR_MESSAGE",
						(char *)format(GETTEXT("The size of %1$s is too small to voyage out to the cluster that the player %2$s is in."),
										Fleet->get_nick(),
										TargetPlayer->get_nick()));

				return output("war/war_error.html");
			}

		}
#undef SHIP_SIZE_CRUISER

		if (i==1) OffensePlan.set_capital(FleetID);

		CDefenseFleet *
			OffenseFleet = new CDefenseFleet();
		OffenseFleet->set_owner(aPlayer->get_game_id());
		OffenseFleet->set_fleet_id(FleetID);
		OffenseFleet->set_command(Command);
		OffenseFleet->set_x(3000-(LocationY - 226)*10);
		OffenseFleet->set_y(8000-(LocationX - 9)*10);

		OffensePlan.add_defense_fleet(OffenseFleet);
	}

	if (OffensePlan.is_there_stacked_fleet() == true)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You stacked 1 or more fleets on another fleet(s)."));
		return output("war/war_error.html");
	}

	CString Report;
	CString BattleView;

	Report.format(GETTEXT("Your fleet(s) arrived on %1$s's orbit."), 
					Planet->get_name());
	Report += "<BR>\n";
	Report += GETTEXT("Your fleet(s) started detecting enemy fleets.");
	Report += "<BR>\n";

	CDefensePlanList *
		DefensePlanList = TargetPlayer->get_defense_plan_list();
	CDefensePlan *
		DefensePlan = DefensePlanList->get_optimal_plan(aPlayer->get_race(), aPlayer->get_power(), TargetPlayer->get_power(), CDefensePlan::TYPE_SIEGE);

	if (Battle.init_battle_fleet(&OffensePlan, AttackerFleetList, AttackerAdmiralList, DefensePlan, DefenderFleetList, DefenderAdmiralList))
	{
		BattleView.format("%s (%s %d : %d)\n",
				GETTEXT("Fleet Battle View"),
				GETTEXT("Attack vs. Defense"),
				Battle.get_offense_battle_fleet_list().length(),
				Battle.get_defense_battle_fleet_list().length());

		CString Buf;

		Buf = "<PRE>\nBATTLE REPORT\n";
		while(Battle.run_step());
		Buf += Battle.get_result_report();
		Buf += "</PRE>\n";
		Report += Buf;

		Buf.clear();
		if (Battle.attacker_win())
		{
			Report += GETTEXT("You won the fleet battle on the orbit.");
			Report += "<BR>\n";
			Report += GETTEXT("You have a counterattack from ground.");
			Report += "<BR>\n";
			if (Battle.siege_war())
			{
				Report.format(GETTEXT("You put down the military base(s) on %1$s."), 
							Planet->get_name());
				Report += "<BR>\n";
	
				Buf.format(GETTEXT("%1$s invaded %2$s of %3$s and won."), 
						aPlayer->get_nick(), Planet->get_name(), 
						TargetPlayer->get_nick());
				Buf += "<BR>\n";

				aPlayer->time_news((char*)Buf);
				TargetPlayer->time_news((char*)Buf);

				TargetPlayer->drop_planet(Planet);
				aPlayer->add_planet(Planet);

				CEmpirePlanetInfo *
					EmpirePlanetInfo = EMPIRE_PLANET_INFO_LIST->get_by_planet_id(Planet->get_id());
				if (EmpirePlanetInfo != NULL)
				{
					EmpirePlanetInfo->set_owner_id(aPlayer->get_game_id());

					EmpirePlanetInfo->type(QUERY_UPDATE);
					STORE_CENTER->store(*EmpirePlanetInfo);
				}

				Report.format(GETTEXT("You have gained new planet %1$s."), 
								Planet->get_name());
				Report += "<BR>\n";
				
				if (aPlayer->get_race() == TargetPlayer->get_race())
				{
					int Ratio = 10 + number(40)-1;
					int Ret = Planet->get_population() * Ratio / 100;
					Planet->change_population(-Ret);

					Report.format(GETTEXT("You massacred %1$s of people on %2$s."),
									dec2unit(Ret),
									Planet->get_name());
					Ratio = 25 + number(50)-1;
					Ret = Planet->get_building().get(BUILDING_FACTORY) * Ratio / 100;
					Planet->get_building().change(BUILDING_FACTORY, -Ret);
					Report.format(GETTEXT("You destroyed %1$s unit(s) of factory."), dec2unit(Ret));
					Report += "<BR>\n";

					Ratio = 25 + number(50)-1;
					Ret = Planet->get_building().get(BUILDING_RESEARCH_LAB) * Ratio / 100;
					Planet->get_building().change(BUILDING_RESEARCH_LAB, -Ret);
					Report.format(GETTEXT("You destroyed %1$s unit(s) of research lab."), dec2unit(Ret));
					Report += "<BR>\n";

					Ratio = 25 + number(50)-1;
					Ret = Planet->get_building().get(BUILDING_MILITARY_BASE) * Ratio / 100;
					Planet->get_building().change(BUILDING_MILITARY_BASE, -Ret);
					Report.format(GETTEXT("You destroyed %1$s unit(s) of military base."),
									dec2unit(Ret));
					Report += "<BR>\n";
				}
				else
				{
					Planet->change_population(-Planet->get_population());
					Report.format(GETTEXT("You have a genocide on %1$s."),
									Planet->get_name());
					Report += "<BR>\n";

					Planet->change_population(10000);
					Report += GETTEXT("You transfer 10 mil. people.");
					Report += "<BR>\n";

					int Ratio = 50 + number(50)-1;
					int Ret = Planet->get_building().get(BUILDING_FACTORY) * Ratio / 100;
					Planet->get_building().change(BUILDING_FACTORY, -Ret);
					Report.format(GETTEXT("You destroyed %1$s unit(s) of factory."), dec2unit(Ret));
					Report += "<BR>\n";

					Ratio = 50 + number(50)-1;
					Ret = Planet->get_building().get(BUILDING_RESEARCH_LAB) * Ratio / 100;
					Planet->get_building().change(BUILDING_RESEARCH_LAB, -Ret);
					Report.format(GETTEXT("You destroyed %1$s unit(s) of research lab."), dec2unit(Ret));
					Report += "<BR>\n";

					Ratio = 50 + number(50)-1;
					Ret = Planet->get_building().get(BUILDING_MILITARY_BASE) * Ratio / 100;
					Planet->get_building().change(BUILDING_MILITARY_BASE, -Ret);
					Report.format(GETTEXT("You destroyed %1$s unit(s) of military base."), dec2unit(Ret));
					Report += "<BR>\n";
				}

				Planet->start_terraforming();

				if (TargetPlayer->get_planet_list()->length() == 0)
				{
					static CString
						TargetNick;
					TargetNick.clear();
					TargetNick = TargetPlayer->get_nick();

					TargetPlayer->set_dead(
							(char *)format("Player %s has killed %s with siege. [%s]",
											aPlayer->get_nick(),
											(char *)TargetNick,
											aPlayer->get_last_login_ip()));

					Report.format(GETTEXT("Your attack has brought about the ruin of %1$s."), 
									TargetPlayer->get_nick());
					Report += "<BR>\n";
				}

				aPlayer->set_siege_blockade_restriction();
				TargetPlayer->set_siege_blockade_protection();

				ADMIN_TOOL->add_siege_planet_log(
						(char *)format("The player %s has succeeded to siege the planet %s whose owner is the player %s.",
										aPlayer->get_nick(),
										Planet->get_name(),
										TargetPlayer->get_nick()));
			}
			else
			{
				Report += GETTEXT("Your fleet had critical damage from the ground counterattack.");
				Report += "<BR>\n";
				Report += GETTEXT("Your fleet has been destroyed completely.");

				Buf.format(GETTEXT("%1$s invaded %2$s of %3$s and lost."),
						aPlayer->get_nick(), Planet->get_name(), 
						TargetPlayer->get_nick());

				aPlayer->time_news((char*)Buf);
				TargetPlayer->time_news((char*)Buf);


				ADMIN_TOOL->add_siege_planet_log(
						(char *)format("The player %s has failed to siege the planet %s whose owner is the player %s.",
										aPlayer->get_nick(),
										Planet->get_name(),
										TargetPlayer->get_nick()));
			}
		}
		else
		{
			Report += GETTEXT("Your fleet had critical damage in the fleet battle.");
			Report += "<BR>\n";
			Report += GETTEXT("Your fleet has been destroyed completely.");

			Buf.format(GETTEXT("%1$s invaded %2$s of %3$s and lost."),
					aPlayer->get_nick(), Planet->get_name(), 
					TargetPlayer->get_nick());

			aPlayer->time_news((char*)Buf);
			TargetPlayer->time_news((char*)Buf);


			ADMIN_TOOL->add_siege_planet_log(
					(char *)format("The player %s has failed to siege the planet %s whose owner is the player %s.",
									aPlayer->get_nick(),
									Planet->get_name(),
									TargetPlayer->get_nick()));
		}

		// start telecard 2001/01/21
		Battle.finish_report_after_battle();
		// end telecard
		Battle.update_fleet_after_battle();
		Battle.save();

		BattleView.format("&nbsp;\n"
			"<A HREF=\"battle_report_detail.as?LOG_ID=%d\">"
				"<IMG SRC=\"http://%s/image/as_game/bu_view.gif\""
					"WIDTH=44 HEIGHT=11 BORDER=\"0\"></A>",
					Battle.get_record()->get_id(),
					(char *)CGame::mImageServerURL);
	}
	else // have no defensive fleet
	{
		BattleView = GETTEXT("You had no fleet battle.");
		Report += GETTEXT("You encounter no defensive fleets.");
		Report += "<BR>\n";
		Report += GETTEXT("You have a counterattack from ground.");
		Report += "<BR>\n";
		CString Buf;
		if (Battle.siege_war())
		{
			Report.format(GETTEXT("You put down the military base(s) on %1$s."), 
							Planet->get_name());
			Report += "<BR>\n";

			Buf.format(GETTEXT("%1$s invaded %2$s of %3$s and won."),
					aPlayer->get_nick(), Planet->get_name(), 
						TargetPlayer->get_nick());

			aPlayer->time_news((char*)Buf);
			TargetPlayer->time_news((char*)Buf);

			TargetPlayer->drop_planet(Planet);
			aPlayer->add_planet(Planet);

			CEmpirePlanetInfo *
				EmpirePlanetInfo = EMPIRE_PLANET_INFO_LIST->get_by_planet_id(Planet->get_id());
			if (EmpirePlanetInfo != NULL)
			{
				EmpirePlanetInfo->set_owner_id(aPlayer->get_game_id());

				EmpirePlanetInfo->type(QUERY_UPDATE);
				STORE_CENTER->store(*EmpirePlanetInfo);
			}

			if (aPlayer->get_race() == TargetPlayer->get_race())
			{
				int Ratio = 10 + number(40)-1;
				int Ret = Planet->get_population() * Ratio / 100;
				Planet->change_population(-Ret);
				Report.format(GETTEXT("You massacred %1$s of people on %2$s."),
								dec2unit(Ret),
								Planet->get_name());
				Report += "<BR>\n";

				Ratio = 25 + number(50)-1;
				Ret = Planet->get_building().get(BUILDING_FACTORY) * Ratio / 100;
				Planet->get_building().change(BUILDING_FACTORY, -Ret);
				Report.format(GETTEXT("You destroyed %1$s unit(s) of factory."), dec2unit(Ret));
				Report += "<BR>\n";

				Ratio = 25 + number(50)-1;
				Ret = Planet->get_building().get(BUILDING_RESEARCH_LAB) * Ratio / 100;
				Planet->get_building().change(BUILDING_RESEARCH_LAB, -Ret);
				Report.format(GETTEXT("You destroyed %1$s unit(s) of research lab."), dec2unit(Ret));
				Report += "<BR>\n";

				Ratio = 25 + number(50)-1;
				Ret = Planet->get_building().get(BUILDING_MILITARY_BASE) * Ratio / 100;
				Planet->get_building().change(BUILDING_MILITARY_BASE, -Ret);
				Report.format(GETTEXT("You destroyed %1$s unit(s) of military base."), dec2unit(Ret));
				Report += "<BR>\n";
	
			}
			else
			{
				Planet->change_population(-Planet->get_population());
				Report.format(GETTEXT("You have a genocide on %1$s."),
								Planet->get_name());
				Report += "<BR>\n";
				Planet->change_population(10000);


				int Ratio = 50 + number(50)-1;
				int Ret = Planet->get_building().get(BUILDING_FACTORY) * Ratio / 100;
				Planet->get_building().change(BUILDING_FACTORY, -Ret);
				Report.format(GETTEXT("You destroyed %1$s unit(s) of factory."), dec2unit(Ret));
				Report += "<BR>\n";

				Ratio = 50 + number(50)-1;
				Ret = Planet->get_building().get(BUILDING_RESEARCH_LAB) * Ratio / 100;
				Planet->get_building().change(BUILDING_RESEARCH_LAB, -Ret);
				Report.format(GETTEXT("You destroyed %1$s unit(s) of research lab."), dec2unit(Ret));
				Report += "<BR>\n";

				Ratio = 50 + number(50)-1;
				Ret = Planet->get_building().get(BUILDING_MILITARY_BASE) * Ratio / 100;
				Planet->get_building().change(BUILDING_MILITARY_BASE, -Ret);
				Report.format(GETTEXT("You destroyed %1$s unit(s) of military base."), dec2unit(Ret));
				Report += "<BR>\n";
			}

			Planet->start_terraforming();

			if (TargetPlayer->get_planet_list()->length() == 0)
			{
				static CString
					TargetNick;
				TargetNick.clear();
				TargetNick = TargetPlayer->get_nick();

				TargetPlayer->set_dead(
						(char *)format("Player %s has killed %s with siege. [%s]",
										aPlayer->get_nick(),
										(char *)TargetNick,
										aPlayer->get_last_login_ip()));

				Report.format(GETTEXT("Your attack has brought about"
										" the ruin of %s."),
								TargetPlayer->get_nick());
				Report += "<BR>\n";
			}

			aPlayer->set_siege_blockade_restriction();
			TargetPlayer->set_siege_blockade_protection();

			ADMIN_TOOL->add_siege_planet_log(
					(char *)format("The player %s has succeeded to siege the planet %s whose owner is the player %s.",
									aPlayer->get_nick(),
									Planet->get_name(),
									TargetPlayer->get_nick()));
		}
		else
		{
			Buf.format(GETTEXT("%1$s invaded %2$s of %3$s and lost."),
					aPlayer->get_nick(), Planet->get_name(), 
					TargetPlayer->get_nick());

			aPlayer->time_news((char*)Buf);
			TargetPlayer->time_news((char*)Buf);

			Report += GETTEXT("Your fleet had critical damage from the ground counterattack.");
			Report += "<BR>\n";
			Report += GETTEXT("Your fleet has been destroyed completely.");


			ADMIN_TOOL->add_siege_planet_log(
					(char *)format("The player %s has failed to siege the planet %s whose owner is the player %s.",
									aPlayer->get_nick(),
									Planet->get_name(),
									TargetPlayer->get_nick()));
		}
		Battle.finish_report_after_battle();
		Battle.update_fleet_after_battle();
		Battle.save();
	}

	ITEM("MESSAGE", Report);
	ITEM("BATTLE_VIEW", BattleView);

	if(aPlayer->get_protected_mode() == CPlayer::PROTECTED_BEGINNER)
	{
		aPlayer->set_protected_mode(CPlayer::PROTECTED_NONE);
	}

	CDefenseFleetList *
		OffensePlanFleetList = OffensePlan.get_fleet_list();
	OffensePlanFleetList->remove_all();

	for (int i=1 ; i<=FleetNumber ; i++)
	{
		char Query[100];
		
		sprintf(Query, "FLEET%d_ID", i); QUERY(Query, FleetIDString);

		int FleetID = as_atoi(FleetIDString);

		CFleet *
			Fleet = (CFleet *)AttackerFleetList->get_by_id(FleetID);

		if (Fleet)
		{
			CEngine *Engine = 
				(CEngine*)COMPONENT_TABLE->get_by_id(Fleet->get_engine());
			assert(Engine);
			int ReturnTime = (9-Engine->get_level())*CGame::mSecondPerTurn;
			if (!aPlayer->has_cluster(Planet->get_cluster_id()))
				ReturnTime *= 2;    
			ReturnTime = aPlayer->calc_PA(ReturnTime, CPlayerEffect::PA_CHANGE_YOUR_FLEET_RETURN_TIME);
			Fleet->init_mission(CMission::MISSION_RETURNING, 0);
			CMission &Mission = Fleet->get_mission();
			Mission.set_terminate_time(CGame::get_game_time() + ReturnTime);
		}
	}

	aPlayer->type(QUERY_UPDATE);
	TargetPlayer->type(QUERY_UPDATE);
	Planet->type(QUERY_UPDATE);

	*STORE_CENTER << *aPlayer;
	*STORE_CENTER << *TargetPlayer;
	STORE_CENTER->store(*Planet);

//	system_log( "end page handler %s", get_name() );

	return output("war/siege_planet_result.html");
}

