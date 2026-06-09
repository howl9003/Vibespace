#include "ending.h"
#include "define.h"
#include "archspace.h"
#include "game.h"
#include "council.h"

CPersonalEnding::CPersonalEnding()
{
	mID = 0;
	mProjectID = 0;
	mRaceIndex = -1;

	mConditionTech = -1;
	mConditionTech2 = -1;
	mConditionProject = -1;
	mConditionPlanet = -1;
	mConditionCluster = -1;
	mConditionCommanderLevel = -1;
	mConditionFleet = -1;
	mConditionCouncil = -1;
	mConditionRP = -1;
	mConditionDA = -1;
	mConditionDAequalORless = -1;
	mConditionDAgreater = -1;
	mConditionEfficiency = -1;
	mConditionHaveDoomstar = -1;
	mConditionCouncilProject = -1;
	mConditionShipPool = -1;
	mConditionPopulationIncreasedLess = -1;
	mConditionPopulationIncreasedEqualOrMore = -1;
	mConditionConcentrationMode = -1;


	mConditionTechAll = false;
	mConditionHaveTitle = false;
	mConditionNoWarInCouncil = false;
	mConditionCouncilSpeaker = false;
}

CProject *
CPersonalEnding::get_ending_project()
{
	CProject*
		project = ENDING_PROJECT_TABLE->get_by_id( mID );
	if( project == NULL )
	{
	}
	return project;
}

void
CPersonalEnding::set_condition(char* aConditionName, int aValue)
{
	if (aConditionName == NULL) return;

	if(!strcasecmp("Tech", aConditionName))
	{
		mConditionTech = aValue;
	}
	else if(!strcasecmp("Tech2", aConditionName))
	{
		mConditionTech2 = aValue;
	}
	else if(!strcasecmp("Project", aConditionName))
	{
		mConditionProject = aValue;
	}
	else if(!strcasecmp("Planet", aConditionName))
	{
		mConditionPlanet = aValue;
	}
	else if(!strcasecmp("Cluster", aConditionName))
	{
		mConditionCluster = aValue;
	}
	else if(!strcasecmp("CommanderLevel", aConditionName))
	{
		mConditionCommanderLevel = aValue;
	}
	else if(!strcasecmp("Fleet", aConditionName))
	{
		mConditionFleet = aValue;
	}
	else if(!strcasecmp("Council", aConditionName))
	{
		mConditionCouncil = aValue;
	}
	else if(!strcasecmp("TechAll", aConditionName))
	{
		mConditionTechAll = true;
	}
	else if(!strcasecmp("RP", aConditionName))
	{
		mConditionRP = aValue;
	}
	else if(!strcasecmp("DAequalORless", aConditionName))
	{
		mConditionDAequalORless = aValue;
	}
	else if(!strcasecmp("Efficiency", aConditionName))
	{
		mConditionEfficiency = aValue;
	}
	else if(!strcasecmp("HaveTitle", aConditionName))
	{
		mConditionHaveTitle = true;
	}
	else if(!strcasecmp("NoWarInCouncil", aConditionName))
	{
		mConditionNoWarInCouncil = true;
	}
	else if(!strcasecmp("DAgreater", aConditionName))
	{
		mConditionDAgreater = aValue;
	}
	else if(!strcasecmp("HaveDoomstar", aConditionName))
	{
		mConditionHaveDoomstar = aValue;
	}
	else if(!strcasecmp("CouncilProject", aConditionName))
	{
		mConditionCouncilProject = aValue;
	}
	else if(!strcasecmp("DA", aConditionName))
	{
		mConditionDA = aValue;
	}
	else if(!strcasecmp("ShipPool", aConditionName))
	{
		mConditionShipPool = aValue;
	}
	else if(!strcasecmp("PopulationIncreasedLess", aConditionName))
	{
		mConditionPopulationIncreasedLess = aValue;
	}
	else if(!strcasecmp("ConcentrationMode", aConditionName))
	{
		mConditionConcentrationMode = aValue;
	}
	else if(!strcasecmp("PopulationIncreaedEqualOrMore", aConditionName))
	{
		mConditionPopulationIncreasedEqualOrMore = aValue;
	}
	else if(!strcasecmp("CouncilSpeaker", aConditionName))
	{
		mConditionCouncilSpeaker = true;
	}
}

bool
CPersonalEnding::check_condition(CPlayer *aPlayer)
{
	CKnownTechList *
		KnownTechList = aPlayer->get_tech_list();
	CPurchasedProjectList *
		PurchasedProjectList = aPlayer->get_purchased_project_list();

	if (mConditionTech != -1)
	{
		if (KnownTechList->get_by_id(mConditionTech) == NULL) return false;
	}

	if (mConditionTech2 != -1)
	{
		if (KnownTechList->get_by_id(mConditionTech2) == NULL) return false;
	}

	if (mConditionProject != -1)
	{
		if (PurchasedProjectList->get_by_id(mConditionProject) == NULL) return false;
	}

	if (mConditionPlanet != -1)
	{
		if( aPlayer->get_planet_list()->length() < mConditionPlanet )
		{
			return false;
		}
	}
	if( mConditionCluster != -1 )
	{
		if( aPlayer->cluster_list().length() < mConditionCluster )
		{
			return false;
		}
	}
	if( mConditionCommanderLevel != -1 )
	{
		bool
			exist = false;
		CAdmiralList*
			admiralList = aPlayer->get_admiral_list();
		for(int i=0 ; i<admiralList->length() ; i++)
		{
			CAdmiral*
				admiral = (CAdmiral*)admiralList->get(i);
			if( admiral->get_level() >= mConditionCommanderLevel )
			{
				exist = true;
				break;
			}
		}
		if(exist == false)
		{
			admiralList = aPlayer->get_admiral_pool();
			for(int i=0 ; i<admiralList->length() ; i++)
			{
				CAdmiral*
					admiral = (CAdmiral*)admiralList->get(i);
				if( admiral->get_level() >= mConditionCommanderLevel )
				{
					exist = true;
					break;
				}
			}
		}
		if(exist == false)
		{
			return false;
		}
	}
	if( mConditionFleet != -1 )
	{
		if( aPlayer->get_fleet_list()->length() < mConditionFleet )
		{
			return false;
		}
	}
	if( mConditionCouncil != -1 )
	{
		if( aPlayer->get_council()->get_members()->length() < mConditionCouncil )
		{
			return false;
		}
	}
	if( mConditionRP != -1 )
	{
		if( aPlayer->get_last_turn_research() < mConditionRP )
		{
			return false;
		}
	}
	if( mConditionDA != -1 )
	{
		if( aPlayer->get_empire_relation() != mConditionDA )
		{
			return false;
		}
	}
	if( mConditionDAequalORless != -1 )
	{
		if( aPlayer->get_empire_relation() > mConditionDAequalORless )
		{
			return false;
		}
	}
	if( mConditionDAgreater != -1 )
	{
		if( aPlayer->get_empire_relation() <= mConditionDAgreater )
		{
			return false;
		}
	}
	if( mConditionEfficiency != -1 )
	{
		if( aPlayer->get_control_model()->get_real_efficiency() < mConditionEfficiency )
		{
			return false;
		}
	}
	if( mConditionHaveDoomstar != -1 )
	{
		int
			ships = 0;

		CFleetList*
			fleetList = aPlayer->get_fleet_list();
		for(int i=0 ; i<fleetList->length() ; i++)
		{
			CFleet*
				fleet = (CFleet*)fleetList->get(i);
			if( fleet->get_size() == 10 )
			{
				ships += fleet->get_current_ship();
			}
		}
		CDock*
			dock = aPlayer->get_dock();
		for(int i=0 ; i<dock->length() ; i++)
		{
			CDockedShip*
				dockedShip = (CDockedShip*)dock->get(i);
			if( dockedShip->get_size() == 10 )
			{
				ships += dockedShip->get_number();
			}
		}
		CRepairBay*
			repairBay = aPlayer->get_repair_bay();
		for(int i=0 ; i<repairBay->length() ; i++)
		{
			CDamagedShip*
				damagedShip = (CDamagedShip*)repairBay->get(i);
			if( damagedShip->get_size() == 10 )
			{
				ships++;
			}
		}

		if( ships < mConditionHaveDoomstar )
		{
			return false;
		}
	}
	if( mConditionCouncilProject != -1 )
	{
		if( aPlayer->get_council()->get_purchased_project_list()->get_by_id( mConditionCouncilProject ) == NULL )
		{
			return false;
		}
	}
	if( mConditionShipPool != -1 )
	{
		int
			ships = 0;
		CDock*
			dock = aPlayer->get_dock();
		for(int i=0 ; i<dock->length() ; i++)
		{
			CDockedShip*
				dockedShip = (CDockedShip*)dock->get(i);
			ships += dockedShip->get_number();
		}

		if( ships < mConditionShipPool )
		{
			return false;
		}
	}
	if( mConditionPopulationIncreasedLess != -1 )
	{
		if( aPlayer->get_last_turn_population_increased() >= mConditionPopulationIncreasedLess )
		{
			return false;
		}
	}
	if( mConditionPopulationIncreasedEqualOrMore != -1 )
	{
		if( aPlayer->get_last_turn_population_increased() < mConditionPopulationIncreasedEqualOrMore )
		{
			return false;
		}
	}
	if( mConditionConcentrationMode != -1 )
	{
		if( aPlayer->get_mode() != mConditionConcentrationMode )
		{
			return false;
		}
	}

	if( mConditionTechAll == true )
	{
		if( aPlayer->get_available_tech_list()->length() != 0 )
		{
			return false;
		}
	}
	if( mConditionHaveTitle == true )
	{
		if( aPlayer->get_court_rank() == CPlayer::CR_NONE )
		{
			return false;
		}
	}
	if( mConditionNoWarInCouncil == true )
	{
		CPlayerList*
			playerList = aPlayer->get_council()->get_members();
		for(int i=0 ; i<playerList->length() ; i++)
		{
			CPlayer*
				player = (CPlayer*)playerList->get(i);
			CPlayerRelation
				*Relation = aPlayer->get_relation(player);
			if( Relation && Relation->get_relation() == CRelation::RELATION_WAR )
			{
				return false;
			}
		}
	}
	if( mConditionCouncilSpeaker == true )
	{
		if( aPlayer->get_council()->get_speaker_id() != aPlayer->get_game_id() )
		{
			return false;
		}
	}

	return true;
}
