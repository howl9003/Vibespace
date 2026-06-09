#include <libintl.h>
#include "../triggers.h"
#include "../archspace.h"
#include "../council.h"
#include "../battle.h"

void
CCronTabEmpireCounterattackMagistrate::handler()
{
	SLOG("EmpireCounterattackMagistrate Start");
	if (EMPIRE->is_dead() == false)
	{
		CMagistrateList *
			MagistrateList = EMPIRE->get_magistrate_list();
		for (int i=0 ; i<MagistrateList->length() ; i++)
		{
			CMagistrate *
				Magistrate = (CMagistrate *)MagistrateList->get(i);
			if (Magistrate->is_dead() == true) continue;

			CSortedPlanetList *
				MagistrateLostPlanetList = Magistrate->get_lost_planet_list();
			if (MagistrateLostPlanetList->length() == 0) continue;

			CPlanet *
				TargetPlanet = (CPlanet *)MagistrateLostPlanetList->get(MagistrateLostPlanetList->length() - 1);
			CPlayer *
				TargetPlayer = TargetPlanet->get_owner();

			CBattle
				Battle(CBattle::WAR_MAGISTRATE_COUNTERATTACK, EMPIRE, TargetPlayer, (void *)TargetPlanet);

			CAdmiralList
				*AttackerAdmiralList = EMPIRE->get_admiral_list(),
				*DefenderAdmiralList = TargetPlayer->get_admiral_list();
			CFleetList
				*AttackerFleetList = EMPIRE->get_fleet_list(),
				*DefenderFleetList = TargetPlayer->get_fleet_list();

			AttackerAdmiralList->remove_all();
			AttackerFleetList->remove_all();

			AttackerFleetList->create_as_empire_fleet_group_volatile(AttackerAdmiralList, CEmpire::EMPIRE_FLEET_GROUP_TYPE_MAGISTRATE_COUNTERATTACK, Magistrate->get_cluster_id());

			CDefensePlan
				OffensePlan;
			OffensePlan.init_for_empire(AttackerFleetList, true);

			CString Report;

			CDefensePlanList *
				DefensePlanList = TargetPlayer->get_defense_plan_list();
			CDefensePlan *
				DefensePlan = DefensePlanList->get_generic_plan();

			if (Battle.init_battle_fleet(&OffensePlan, AttackerFleetList, AttackerAdmiralList, DefensePlan, DefenderFleetList, DefenderAdmiralList))
			{
				while(Battle.run_step());

				if (Battle.attacker_win())
				{

					TargetPlayer->time_news(
							(char *)format(GETTEXT("%1$s invaded %2$s and won."), 
											EMPIRE->get_name(), TargetPlayer->get_nick()));

					TargetPlayer->drop_planet(TargetPlanet);

					TargetPlanet->set_owner(EMPIRE);
					Magistrate->add_planet(TargetPlanet);

					CEmpirePlanetInfo *
						EmpirePlanetInfo = EMPIRE_PLANET_INFO_LIST->get_by_planet_id(TargetPlanet->get_id());
					if (EmpirePlanetInfo != NULL)
					{
						EmpirePlanetInfo->set_owner_id(EMPIRE_GAME_ID);

						EmpirePlanetInfo->type(QUERY_UPDATE);
						STORE_CENTER->store(*EmpirePlanetInfo);
					}

					TargetPlanet->type(QUERY_UPDATE);
					STORE_CENTER->store(*TargetPlanet);
				}
				else
				{
					TargetPlayer->time_news(
							(char *)format(GETTEXT("%1$s invaded %2$s and lost."),
											EMPIRE->get_name(), TargetPlayer->get_nick()));
				}

					Battle.finish_report_after_battle();
					Battle.update_fleet_after_battle();
					Battle.save();
			}

			CDefenseFleetList *
				OffensePlanFleetList = OffensePlan.get_fleet_list();
			OffensePlanFleetList->remove_all();

			AttackerAdmiralList->remove_all();
			AttackerFleetList->remove_all();
		}
	}
	SLOG("EmpireCounterattackMagistrate End");
}

void
CCronTabEmpireCounterattackEmpirePlanet::handler()
{
	SLOG("EmpireCounterattackEmpirePlanet Start");
	if (EMPIRE->is_dead() == false)
	{
		CPlanetList *
			EmpirePlanetList = EMPIRE->get_planet_list();
		if (EmpirePlanetList->length() == 0 ||
			EmpirePlanetList->length() == CEmpire::mInitialNumberOfEmpirePlanet) return;

		CCluster *
			EmpireCluster = UNIVERSE->get_by_id(EMPIRE_CLUSTER_ID);
		CSortedPlanetList *
			EmpireClusterPlanetList = EmpireCluster->get_planet_list();

		CSortedPlanetList
			AvailablePlanetList;
		for (int i=0 ; i<EmpireClusterPlanetList->length() ; i++)
		{
			CPlanet *
				Planet = (CPlanet *)EmpireClusterPlanetList->get(i);
			if (Planet->get_owner_id() == EMPIRE_GAME_ID) continue;

			AvailablePlanetList.add_planet(Planet);
		}

		if (AvailablePlanetList.length() == 0) return;

		CPlanet *
			TargetPlanet = (CPlanet *)AvailablePlanetList.get(AvailablePlanetList.length() - 1);
		CPlayer *
			TargetPlayer = TargetPlanet->get_owner();

		CBattle
			Battle(CBattle::WAR_EMPIRE_PLANET_COUNTERATTACK, EMPIRE, TargetPlayer, (void *)TargetPlanet);

		CAdmiralList
			*AttackerAdmiralList = EMPIRE->get_admiral_list(),
			*DefenderAdmiralList = TargetPlayer->get_admiral_list();
		CFleetList
			*AttackerFleetList = EMPIRE->get_fleet_list(),
			*DefenderFleetList = TargetPlayer->get_fleet_list();

		AttackerAdmiralList->remove_all();
		AttackerFleetList->remove_all();

		AttackerFleetList->create_as_empire_fleet_group_volatile(AttackerAdmiralList, CEmpire::EMPIRE_FLEET_GROUP_TYPE_EMPIRE_PLANET_COUNTERATTACK);

		CDefensePlan
			OffensePlan;
		OffensePlan.init_for_empire(AttackerFleetList, true);

		CString Report;

		CDefensePlanList *
			DefensePlanList = TargetPlayer->get_defense_plan_list();
		CDefensePlan *
			DefensePlan = DefensePlanList->get_generic_plan();

		if (Battle.init_battle_fleet(&OffensePlan, AttackerFleetList, AttackerAdmiralList, DefensePlan, DefenderFleetList, DefenderAdmiralList))
		{
			while(Battle.run_step());

			if (Battle.attacker_win())
			{
				TargetPlayer->time_news(
						(char *)format(GETTEXT("%1$s invaded %2$s and won."), 
										EMPIRE->get_name(), TargetPlayer->get_nick()));

				TargetPlayer->drop_planet(TargetPlanet);

				TargetPlanet->set_owner(EMPIRE);
				EMPIRE->add_empire_planet(TargetPlanet);

				CEmpirePlanetInfo *
					EmpirePlanetInfo = EMPIRE_PLANET_INFO_LIST->get_by_planet_id(TargetPlanet->get_id());
				if (EmpirePlanetInfo != NULL)
				{
					EmpirePlanetInfo->set_owner_id(EMPIRE_GAME_ID);

					EmpirePlanetInfo->type(QUERY_UPDATE);
					STORE_CENTER->store(*EmpirePlanetInfo);
				}

				TargetPlanet->type(QUERY_UPDATE);
				STORE_CENTER->store(*TargetPlanet);
			}
			else
			{
				TargetPlayer->time_news(
						(char *)format(GETTEXT("%1$s invaded %2$s and lost."),
										EMPIRE->get_name(), TargetPlayer->get_nick()));
			}

				Battle.finish_report_after_battle();
				Battle.update_fleet_after_battle();
				Battle.save();
		}

		CDefenseFleetList *
			OffensePlanFleetList = OffensePlan.get_fleet_list();
		OffensePlanFleetList->remove_all();

		AttackerAdmiralList->remove_all();
		AttackerFleetList->remove_all();
	}
	SLOG("EmpireCounterattackEmpirePlanet End");
}

