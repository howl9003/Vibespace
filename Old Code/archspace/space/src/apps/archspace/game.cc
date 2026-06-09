#include "game.h"
#include "race.h"
#include "council.h"
#include "archspace.h"
#include <cstdlib>
#include <cstdio>
#include "script.h"
#include "ending.h"
#include "battle.h"
#include "encyclopedia.h"
#include "banner.h"
#include "admin.h"

CGame::ELanguage CGame::mLanguage = CGame::LANG_EN;
CString CGame::mImageServerURL;
CString CGame::mForumServerURL;

time_t CGame::mGameStartTime = 0;
time_t CGame::mServerStartTime = 0;
time_t CGame::mSecondPerTurn = 300;
int	CGame::mMaxUser = 10000;
int CGame::mSiegeBlockadeRestrictionDuration = 600;
int CGame::mSiegeBlockadeProtectionDuration = 600;

int CGame::mMinAvailableCluster = 7;
int CGame::mMinCouncilCount = 3;

pth_mutex_t CGame::mMutex = PTH_MUTEX_INIT;
pth_t CGame::mUpdateThread;

CGame::CGame()
{
	mUniverse = NULL;
	mPlayerTable = NULL; 
	mPlanetTable = NULL; 
	mTechTable = NULL; 
	mProjectTable = NULL;
	mRaceTable = NULL; 
	mCouncilTable = NULL; 
	mConfig = NULL;
	mComponentTable = NULL; /* telecard 2000/10/02 */
	mShipSizeTable = NULL;
	mAdmiralNameTable = NULL;
	mPlayerActionTable = NULL;
	mPlayerRelationTable = NULL;
	mCouncilActionTable = NULL;
	mCouncilRelationTable = NULL;
	mSpyTable = NULL;
	mEventTable = NULL;
	mGalacticEventList = NULL;
	mEmpireShipDesignTable = NULL;

	mAdminTool = NULL;
}

CGame::~CGame()
{
	if (mUniverse)
		delete mUniverse;
	if (mPlayerTable)
		delete mPlayerTable;
	if (mPlanetTable)
		delete mPlanetTable;
	if (mTechTable)
		delete mTechTable;
	if (mProjectTable)
		delete mProjectTable;
	if (mRaceTable)
		delete mRaceTable;
	if (mCouncilTable)
		delete mCouncilTable;
	if (mComponentTable)
		delete mComponentTable;
	if (mShipSizeTable)
		delete mShipSizeTable;
	if (mAdmiralNameTable)
		delete mAdmiralNameTable;
	if (mPlayerActionTable)
		delete mPlayerActionTable;
	if (mPlayerRelationTable)
		delete mPlayerRelationTable;
	if (mCouncilActionTable)
		delete mCouncilActionTable;
	if (mCouncilRelationTable)
		delete mCouncilRelationTable;
	if (mEmpireShipDesignTable != NULL)
		delete mEmpireShipDesignTable;

	if (mSpyTable) delete mSpyTable;
	if (mEventTable) delete mEventTable;
	if (mGalacticEventList) delete mGalacticEventList;

	if (mAdminTool) delete mAdminTool;
}

char *
CGame::get_charset()
{
	switch (mLanguage)
	{
		case CGame::LANG_EN :
			return "charset=iso-8859-1";
			break;

		case CGame::LANG_KO :
			return "charset=euc-kr";
			break;

		default :
			return "charset=iso-8859-1";
			break;
	}
}

CPlayer *
CGame::get_player_by_portal_id(int aPortalID)
{
	return mPlayerTable->get_by_portal_id(aPortalID);
}

CPlayer *
CGame::get_player_by_game_id(int aGameID)
{
	return mPlayerTable->get_by_game_id(aGameID);
}

CPlayer *
CGame::get_player_by_name(const char *aName)
{
	return mPlayerTable->get_by_name(aName);
}

bool 
CGame::initialize(CIniFile *aConfig)
{
	mConfig = aConfig;	
	int
		Temp;
	char *
		TempString;

	TempString = mConfig->get_string("Game", "ImageServerURL", NULL);
	if (TempString == NULL)
	{
		SLOG("ERROR : The image server is not specified!");
		return false;
	}
	else
	{
		mImageServerURL = TempString;
	}

	TempString = mConfig->get_string("Game", "ForumServerURL", NULL);
	if (TempString == NULL)
	{
		SLOG("ERROR : The forum server is not specified!");
		return false;
	}
	else
	{
		mForumServerURL = TempString;
	}

	Temp = mConfig->get_integer("Game", "MinAvailableCluster", -1);
	if (Temp > 0) CGame::mMinAvailableCluster = Temp;

	Temp = mConfig->get_integer("Game", "MinCouncilCount", -1);
	if (Temp > 0) CGame::mMinCouncilCount = Temp;

	Temp = mConfig->get_integer("Game", "SecondPerTurn", -1);
	if (Temp > 0) mSecondPerTurn = Temp;

	Temp = mConfig->get_integer("Game", "TrainMissionTime", -1);
	if (Temp > 0) CMission::mTrainMissionTime = Temp * mSecondPerTurn;

	Temp = mConfig->get_integer("Game", "PatrolMissionTime", -1);
	if (Temp > 0) CMission::mPatrolMissionTime = Temp * mSecondPerTurn;

	Temp = mConfig->get_integer("Game", "DispatchToAllyMissionTime", -1);
	if (Temp > 0) CMission::mDispatchToAllyMissionTime = Temp * mSecondPerTurn;

	Temp = mConfig->get_integer("Game", "ExpeditionMissionTime", -1);
	if (Temp > 0) CMission::mExpeditionMissionTime = Temp * mSecondPerTurn;

	Temp = mConfig->get_integer("Game", "ReturningWithPlanetMissionTime", -1);
	if (Temp > 0) CMission::mReturningWithPlanetMissionTime = Temp * mSecondPerTurn;

	Temp = mConfig->get_integer("Game", "BlackMarketItemRegen", -1);
	if (Temp > 0) CBlackMarket::mBlackMarketItemRegen = Temp;

	Temp = mConfig->get_integer("Game", "BidExpireTime", -1);
	if (Temp > 0) CBlackMarket::mBidExpireTime = Temp;

	Temp = mConfig->get_integer("Game", "MaxUser", -1);
	if (Temp > 0) mMaxUser = Temp;

	Temp = mConfig->get_integer("Game", "SiegeBlockadeRestrictionDuration", -1);
	if (Temp > 0) mSiegeBlockadeRestrictionDuration = Temp;

	Temp = mConfig->get_integer("Game", "SiegeBlockadeProtectionDuration", -1);
	if (Temp > 0) mSiegeBlockadeProtectionDuration = Temp;

	Temp = mConfig->get_integer("Empire", "InitialNumberOfMagistratePlanet", -1);
	if (Temp > 0) CEmpire::mInitialNumberOfMagistratePlanet = Temp;

	Temp = mConfig->get_integer("Empire", "InitialNumberOfEmpirePlanet", -1);
	if (Temp > 0) CEmpire::mInitialNumberOfEmpirePlanet = Temp;

	Temp = mConfig->get_integer("Empire", "FleetRegenCycleInTurn", -1);
	if (Temp > 0) CEmpire::mFleetRegenCycleInTurn = Temp;

	Temp = mConfig->get_integer("Empire", "AmountOfMagistrateShipRegen", -1);
	if (Temp > 0) CEmpire::mAmountOfMagistrateShipRegen = Temp;

	Temp = mConfig->get_integer("Empire", "AmountOfFortress1ShipRegen", -1);
	if (Temp > 0) CEmpire::mAmountOfFortress1ShipRegen = Temp;

	Temp = mConfig->get_integer("Empire", "AmountOfFortress2ShipRegen", -1);
	if (Temp > 0) CEmpire::mAmountOfFortress2ShipRegen = Temp;

	Temp = mConfig->get_integer("Empire", "AmountOfFortress3ShipRegen", -1);
	if (Temp > 0) CEmpire::mAmountOfFortress3ShipRegen = Temp;

	Temp = mConfig->get_integer("Empire", "AmountOfFortress4ShipRegen", -1);
	if (Temp > 0) CEmpire::mAmountOfFortress4ShipRegen = Temp;

	Temp = mConfig->get_integer("Empire", "AmountOfCapitalPlanetShipRegen", -1);
	if (Temp > 0) CEmpire::mAmountOfCapitalPlanetShipRegen = Temp;

	Temp = mConfig->get_integer("Empire", "Fortress1AdmiralFleetCommandingBonus", -1);
	if (Temp > 0) CEmpire::mFortress1AdmiralFleetCommandingBonus = Temp;

	Temp = mConfig->get_integer("Empire", "Fortress2AdmiralFleetCommandingBonus", -1);
	if (Temp > 0) CEmpire::mFortress2AdmiralFleetCommandingBonus = Temp;

	Temp = mConfig->get_integer("Empire", "Fortress3AdmiralFleetCommandingBonus", -1);
	if (Temp > 0) CEmpire::mFortress3AdmiralFleetCommandingBonus = Temp;

	Temp = mConfig->get_integer("Empire", "Fortress4AdmiralFleetCommandingBonus", -1);
	if (Temp > 0) CEmpire::mFortress4AdmiralFleetCommandingBonus = Temp;

	Temp = mConfig->get_integer("Empire", "CapitalPlanetAdmiralFleetCommandingBonus", -1);
	if (Temp > 0) CEmpire::mCapitalPlanetAdmiralFleetCommandingBonus = Temp;

	Temp = mConfig->get_integer("Empire", "EmpireInvasionLimit", -1);
	if (Temp > 0) CEmpire::mEmpireInvasionLimit = Temp;

	Temp = mConfig->get_integer("Empire", "EmpireInvasionLimitDuration", -1);
	if (Temp > 0) CEmpire::mEmpireInvasionLimitDuration = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ScorePerPlanet", -1);
	if (Temp > 0) CGlobalEnding::mScorePerPlanet = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "PopulationPerScore", -1);
	if (Temp > 0) CGlobalEnding::mPopulationPerScore = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ScorePerTechLevel", -1);
	if (Temp > 0) CGlobalEnding::mScorePerTechLevel = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "AdmiralExpPerScore", -1);
	if (Temp > 0) CGlobalEnding::mAdmiralExpPerScore = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ProjectPricePerScore", -1);
	if (Temp > 0) CGlobalEnding::mProjectPricePerScore = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ScorePerSecretProject", -1);
	if (Temp > 0) CGlobalEnding::mScorePerSecretProject = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ScorePerUsedTurn", -1);
	if (Temp > 0) CGlobalEnding::mScorePerUsedTurn = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "MultiplierForPersonalEnding", -1);
	if (Temp > 0) CGlobalEnding::mMultiplierForPersonalEnding = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "MultiplierForAllKnownTechs", -1);
	if (Temp > 0) CGlobalEnding::mMultiplierForAllKnownTechs = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "MultiplierForTitle", -1);
	if (Temp > 0) CGlobalEnding::mMultiplierForTitle = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "MultiplierForSpeaker", -1);
	if (Temp > 0) CGlobalEnding::mMultiplierForSpeaker = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "MultiplierForHonor1", -1);
	if (Temp > 0) CGlobalEnding::mMultiplierForHonor1 = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "MultiplierForHonor2", -1);
	if (Temp > 0) CGlobalEnding::mMultiplierForHonor2 = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ScorePerFortressForPlayer", -1);
	if (Temp > 0) CGlobalEnding::mScorePerFortressForPlayer = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ScorePerFortressForCouncilLayer1", -1);
	if (Temp > 0) CGlobalEnding::mScorePerFortressForCouncilLayer1 = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ScorePerFortressForCouncilLayer2", -1);
	if (Temp > 0) CGlobalEnding::mScorePerFortressForCouncilLayer2 = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ScorePerFortressForCouncilLayer3", -1);
	if (Temp > 0) CGlobalEnding::mScorePerFortressForCouncilLayer3 = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ScorePerFortressForCouncilLayer4", -1);
	if (Temp > 0) CGlobalEnding::mScorePerFortressForCouncilLayer4 = Temp;

	Temp = mConfig->get_integer("Global Ending Score", "ScorePerEmpireCapitalPlanet", -1);
	if (Temp > 0) CGlobalEnding::mScorePerEmpireCapitalPlanet = Temp;

	mAdminTool = new CAdminTool();
	if (mAdminTool == NULL)
	{
		SLOG("ERROR : Couldn't allocate memory for the Admin Tool!");
	}

	TempString = mConfig->get_string("Customer Support", "AdminToolFileDir", NULL);
	if (TempString != NULL) ADMIN_TOOL->set_data_file_directory(TempString);

	TempString = mConfig->get_string("Customer Support", "CSMailAddress", NULL);
	if (TempString != NULL) ADMIN_TOOL->set_CS_mail_address(TempString);

	TempString = mConfig->get_string("Customer Support", "AccuseMailName", NULL);
	if (TempString != NULL) ADMIN_TOOL->set_accuse_mail_name(TempString);

	TempString = mConfig->get_string("Customer Support", "SEDPath", NULL);
	if (TempString != NULL) CAdminTool::mSEDPath = TempString;

	if (!script_table())
	{
		SLOG("Could not load tables from scripts");
		return false;
	}

	if (!database_table())
	{
		SLOG("Could not load tables from the database");
		return false;
	}

	CMySQL
		MySQL;
	char
		*Host = CONFIG->get_string("Database", "Host", NULL),
		*User = CONFIG->get_string("Database", "User", NULL),
		*Password = CONFIG->get_string("Database", "Password", NULL),
		*Database = CONFIG->get_string("Database", "Database", NULL);
	if (Host == NULL || User == NULL || Password == NULL || Database == NULL) return false;

	if (MySQL.open(Host, User, Password, Database) == false) return false;

	EMPIRE->initialize(&MySQL);

	MySQL.close();

	mInitialDesign1.set_design_id( 1 );
	mInitialDesign1.set_name( "Patrol Boat Mk.I" );
	mInitialDesign1.set_body( 4001 );
	mInitialDesign1.set_armor( 5101 );
	mInitialDesign1.set_engine( 5401 );
	mInitialDesign1.set_computer( 5201 );
	mInitialDesign1.set_shield( 5301 );
	mInitialDesign1.set_weapon( 0, 6101 );
	mInitialDesign1.set_weapon_number( 0, 5 );
	mInitialDesign1.set_build_time( time(0) );
	mInitialDesign1.set_build_cost( 1000 );

	mInitialDesign2.set_design_id( 2 );
	mInitialDesign2.set_name( "Star Corvette Mk.I" );
	mInitialDesign2.set_body( 4003 );
	mInitialDesign2.set_armor( 5101 );
	mInitialDesign2.set_engine( 5401 );
	mInitialDesign2.set_computer( 5201 );
	mInitialDesign2.set_shield( 5301 );
	mInitialDesign2.set_weapon( 0, 6301 );
	mInitialDesign2.set_weapon_number( 0, 6 );
	mInitialDesign2.set_weapon( 1, 6201 );
	mInitialDesign2.set_weapon_number( 1, 10 );
	mInitialDesign2.set_build_time( time(0) );
	mInitialDesign2.set_build_cost( 9363 );

	mServerStartTime = time(0);

/*
	if (mGameStartTime > mSecondPerTurn*2) 
		mGameStartTime -= mSecondPerTurn*2;
	else
		mGameStartTime = 0;
*/

	SLOG("Server Start:%d, Game Start:%d, Game Time:%d, Turn:%d",
		mServerStartTime, mGameStartTime, get_game_time(), mSecondPerTurn);

	CString
		ClearDir;
	ClearDir.clear();
	ClearDir.format( "%s/%d", mConfig->get_string( "Game", "BattleLogDir" ), (as_calc_date_index(8)+1)%8 );
	if( chdir( (char*)ClearDir ) == 0 ){
		SLOG( "deleting old battle data : %s", (char*)ClearDir );
		system( "/bin/rm *" );
	}

	chdir( mConfig->get_string( "Game", "ExecDir" ) );

	if (EMPIRE->is_dead() == true)
	{
		set_global_ending_data();
	}

	if (EMPIRE->is_dead() == false)
	{
		remove_old_player();
	}

	return true;
}

void
CGame::remove_old_player()
{
	int
		OldTotalPlayers = PLAYER_TABLE->length()-1,
		DeletedPlayers = 0;
	time_t
		Now = time(0),
		Week = 60*60*24*7;

	for (int i=PLAYER_TABLE->length()-1 ; i>=0 ; i--)
	{
		CPlayer *
			Player = (CPlayer *)PLAYER_TABLE->get(i);
		if (Player->get_game_id() == EMPIRE_GAME_ID) continue;

		if (Player->get_last_login() < Now - Week)
		{
			if (Player->is_dead() == false)
			{
				Player->set_dead((char *)format("%s has been removed since he haven't logged in for a long time.", Player->get_nick()));
			}

			Player->remove_from_database();
			Player->remove_from_memory();
			Player->remove_news_files();
			PLAYER_TABLE->remove_player(Player->get_game_id());

			DeletedPlayers++;
		}
	}

	SLOG("Remove old player %d/%d", DeletedPlayers, OldTotalPlayers);
}

bool
CGame::script_table()
{
	char
		*TechScript
				= mConfig->get_string("Game", "TechScript", NULL);

	if (!TechScript)
	{
		SLOG("Could not found tech script filename.");
		return false;
	}

	CTechScript TScript;

	if (!TScript.load(TechScript))
	{
		SLOG("Could not read tech script filename:%s", TechScript);
		return false;
	}

	mTechTable = new CTechTable();
	if (!mTechTable)
	{
		SLOG("Could not allocate tech table.");
		return false;
	}

	TScript.get(mTechTable);

	SLOG("load tech script");

	char
		*RaceScript
				= mConfig->get_string("Game", "RaceScript", NULL);
	if (!RaceScript)
	{
		SLOG("Could not found race script filename.");
		return false;
	}

	CRaceScript RScript;

	if (!RScript.load(RaceScript))
	{
		SLOG("Could not read race script filename:%s",
		RaceScript);
		return false;
	}

	mRaceTable = new CRaceTable();
	if (!mRaceTable)
	{
		SLOG("Could not allocate tech table.");
		return false;
	}

	RScript.get( mRaceTable );

	if(!race_encyclopedia())
	{
		SLOG("Could not make race encyclopedia");
		return false;
	}

	SLOG("load race script");

	char *
		AdmiralNamePath
			= mConfig->get_string("Game", "AdmiralNameScript", NULL);

	if (!AdmiralNamePath)
	{
		SLOG("Could not read admiral name list filename");
		return false;
	}

	CAdmiralNameScript
		AScript;

	if (!AScript.load(AdmiralNamePath))
	{
		SLOG("Could not read admiral name list");
		return false;
	}

	mAdmiralNameTable = new CAdmiralNameTable();
	if (!mAdmiralNameTable)
	{
		SLOG("Could not allocate admiral name table.");
		return false;
	}

	AScript.get( mAdmiralNameTable );

	SLOG("loaded admiral name script");

	char
		*ProjectScriptPath
				= mConfig->get_string("Game", "ProjectScript", NULL);

	if (!ProjectScriptPath)
	{
		SLOG("Could not found project script filename.");
		return false;
	}

	CProjectScript PScript;

	if (!PScript.load(ProjectScriptPath))
	{
		SLOG("Could not read project script filename:%s",
		ProjectScriptPath);
		return false;
	}

	mProjectTable = new CProjectTable();
	if (!mProjectTable)
	{
		SLOG("Could not allocate project table.");
		return false;
	}

	PScript.get( mProjectTable );

// start telecard 2001/03/14
	char
		*SecretProjectScriptPath
				= mConfig->get_string("Game", "SecretProjectScript", NULL);

	if (!SecretProjectScriptPath)
	{
		SLOG("Could not found secret project script filename.");
		return false;
	}

	CProjectScript SPScript;

	if (!SPScript.load(SecretProjectScriptPath))
	{
		SLOG("Could not read secret project script filename:%s",
				SecretProjectScriptPath);
		return false;
	}

	mSecretProjectTable = new CProjectTable();
	if (!mSecretProjectTable)
	{
		SLOG("Could not allocate secret project table.");
		return false;
	}

	SPScript.get( mSecretProjectTable );
// end telecard 2001/03/14

// start telecard 2001/03/27
	char
		*EndingProjectScriptPath
				= mConfig->get_string("Game", "EndingProjectScript", NULL);

	if (!EndingProjectScriptPath)
	{
		SLOG("Could not found ending project script filename.");
		return false;
	}

	CProjectScript EPScript;

	if (!EPScript.load(EndingProjectScriptPath))
	{
		SLOG("Could not read ending project script filename:%s",
				EndingProjectScriptPath);
		return false;
	}

	mEndingProjectTable = new CProjectTable();
	if (!mEndingProjectTable)
	{
		SLOG("Could not allocate ending project table.");
		return false;
	}

	EPScript.get( mEndingProjectTable );
// end telecard 2001/03/27

	if(!project_encyclopedia())
	{
		SLOG("Could not make project encyclopedia");
		return false;
	}

	SLOG("load project script");

// start telecard 2001/03/31
	char
		*EndingScriptPath
				= mConfig->get_string("Game", "EndingScript", NULL);

	if (!EndingScriptPath)
	{
		SLOG("Could not found ending script filename.");
		return false;
	}

	CPersonalEndingScript EScript;

	if (!EScript.load(EndingScriptPath))
	{
		SLOG("Could not read ending project script filename:%s",
				EndingScriptPath);
		return false;
	}

	mPersonalEndingTable = new CPersonalEndingTable();
	if (!mPersonalEndingTable)
	{
		SLOG("Could not allocate ending table.");
		return false;
	}

	EScript.get( mPersonalEndingTable );
// end telecard 2001/03/31

/* start telecard 2000/10/02 */
	 mComponentTable = new CComponentTable();

	char *
		componentScript
			= mConfig->get_string("Game", "ComponentScript", NULL);

	if (!componentScript)
	{
		SLOG("Could not found component script filename.");
		return false;
	}

	CComponentScript
		cScript;

	if (!cScript.load(componentScript))
	{
		SLOG("Could not read component script filename:%s",
											componentScript);
		return false;
	}

	cScript.get( mComponentTable );

	if(!component_encyclopedia())
	{
		SLOG("Could not make component encyclopedia");
		return false;
	}

	SLOG("load component script");
/* end telecard 2000/10/02 */

/* start telecard 2000/10/05 */
	 mShipSizeTable = new CShipSizeTable();

	char
		*shipScript
			= mConfig->get_string("Game", "ShipScript", NULL);

	if (!shipScript)
	{
		SLOG("Could not found ship script filename.");
		return false;
	}

	CShipScript
		sScript;

	if (!sScript.load(shipScript))
	{
		SLOG("Could not read ship script filename:%s",
											shipScript);
		return false;
	}

	sScript.get( mShipSizeTable );

	if(!ship_encyclopedia())
	{
		SLOG("Could not make ship encyclopedia");
		return false;
	}

	SLOG("load ship script");
/* end telecard 2000/10/05 */

	char *
		SpyScriptPath = mConfig->get_string("Game", "SpyScript", NULL);

	if (!SpyScriptPath)
	{
		SLOG("Could not found spy script filename");
		return false;
	}

	CSpyScript
		SpyScript;

	if (!SpyScript.load(SpyScriptPath))
	{
		SLOG("Could not read spy script filename:%s", SpyScriptPath);
		return false;
	}

	mSpyTable = new CSpyTable();

	SpyScript.get(mSpyTable);

	SLOG("spy script loaded");

	if (!tech_encyclopedia())
	{
		SLOG("Could not make tech encyclopedia");
		return false;
	}
	if (!spy_encyclopedia())
	{
		SLOG("Could not make spy encyclopedia");
		return false;
	}

	char *
		EventScriptPath = mConfig->get_string("Game", "EventScript", NULL);

	if (!EventScriptPath)
	{
		SLOG("Could not found event script filename");
		return false;
	}

	CEventScript
		EventScript;

	if (!EventScript.load(EventScriptPath))
	{
		SLOG("Could not read event script filename:%s", EventScriptPath);
		return false;
	}

	mEventTable = new CEventTable();

	EventScript.get(mEventTable);

	SLOG("event script loaded");

	mGalacticEventList = new CEventInstanceList();
	mGalacticEventList->load_galactic_event();

	char
		*ClusterNames
			= mConfig->get_string("Game", "ClusterNames", NULL);

	if (!ClusterNames)
	{
		SLOG("Could not read cluster name list filename");
		return false;
	}

	if (!CUniverse::load_name(ClusterNames))
	{
		SLOG("Could not read cluster name list");
		return false;
	}

	char
		*EmpireShipDesignScriptFileName
				= mConfig->get_string("Game", "EmpireShipDesignScript", NULL);
	if (EmpireShipDesignScriptFileName == NULL)
	{
		SLOG("Could not find EmpireShipDesignScript filename.");
		return false;
	}

	CEmpireShipDesignScript
		EmpireShipDesignScript;

	if (EmpireShipDesignScript.load(EmpireShipDesignScriptFileName) == false)
	{
		SLOG("Could not read EmpireShipDesignScript script filename : %s",
				EmpireShipDesignScriptFileName);
		return false;
	}

	mEmpireShipDesignTable = new CShipDesignList();
	if (!mEmpireShipDesignTable)
	{
		SLOG("Could not allocate EmpireShipDesign table.");
		return false;
	}

	EmpireShipDesignScript.get(mEmpireShipDesignTable);

	return true;
}

bool
CGame::database_table()
{
	CMySQL MySQL;

	SLOG("load database tables");

	char
		*Host = mConfig->get_string("Database", "Host", NULL ),
		*User = mConfig->get_string("Database", "User", NULL ),
		*Password = mConfig->get_string(
							"Database", "Password", NULL ),
		*Database = mConfig->get_string(
							"Database", "Database", NULL );

	if( !Host || !User || !Password || !Database ) return false;

	SLOG("open mysql");

	if( !MySQL.open(Host, User, Password, Database) ) return false;

	SLOG("create universe");

	mUniverse = new CUniverse();
	if (!mUniverse)
	{
		SLOG("Could not allocate universe");
		return false;
	}

	mUniverse->load(MySQL);

	if (mUniverse->length() < CGame::mMinAvailableCluster)
	{
		while(mUniverse->length() < CGame::mMinAvailableCluster)
			mUniverse->new_cluster();	

//		SLOG("Create two cluster in start");
	}
	CCluster *
		EmpireCluster = new CCluster(0);
	mUniverse->add_cluster(EmpireCluster);

	mCouncilTable = new CCouncilTable();
	if (!mCouncilTable)
	{
		SLOG("Could not allocate council table");
		return false;
	}
	mCouncilTable->load(MySQL);

	mPlayerTable = new CPlayerTable();
	if (!mPlayerTable)
	{
		SLOG("Could not allocate player table");
		return false;
	}
	CPlayer *
		Empire = new CPlayer(0);
	mPlayerTable->add_player(Empire);
	mPlayerTable->load(MySQL);
	SLOG("loaded player table");

	mPlanetTable = new CPlanetTable();
	if (!mPlanetTable)
	{
		SLOG("Could not allocate planet table");
		return false;
	}
	mPlanetTable->load(MySQL);
	SLOG("load planet table");

	mPlayerActionTable = new CPlayerActionTable();
	if (!mPlayerActionTable)
	{
		SLOG("Could not allocate player action table");
		return false;
	}
	mPlayerActionTable->load(MySQL);
	SLOG("load player action table");

	mCouncilActionTable = new CCouncilActionTable();
	if (!mCouncilActionTable)
	{
		SLOG("Could not allocate council action table");
		return false;
	}
	mCouncilActionTable->load(MySQL);
	SLOG("load council action table");

	mPlayerRelationTable = new CPlayerRelationTable();
	if (!mPlayerRelationTable)
	{
		SLOG("Could not allocate player relation table");
		return false;
	}
	mPlayerRelationTable->load(MySQL);
	SLOG("load player relation table");

	mCouncilRelationTable = new CCouncilRelationTable();
	if (!mCouncilRelationTable)
	{
		SLOG("Could not allocate council relation table");
		return false;
	}
	mCouncilRelationTable->load(MySQL);
	SLOG("load council relation table");

	load_project(MySQL);
	load_admission(MySQL);
	load_diplomatic_message(MySQL);
	load_council_message(MySQL);
	
	mBattleRecordTable = new CBattleRecordTable();
	if (!mBattleRecordTable)
	{
		SLOG("Could not allocate battle record table");
		return false;
	}
	mBattleRecordTable->load(MySQL);

	mAdminTool->initialize(&MySQL);

	MySQL.close();

	mPlayerTable->verify_player();

	for(int i=0; i<mCouncilTable->length(); i++)
	{
		CCouncil
			*Council = (CCouncil *)mCouncilTable->get(i);
		Council->process_vote();
		Council->set_project_list();
	}

	for (int i=0 ; i<mPlayerTable->length() ; i++)
	{
		CPlayer *
			Player = (CPlayer *)mPlayerTable->get(i);
		if (Player->get_game_id() == EMPIRE_GAME_ID) continue;
		if (Player->is_dead() == TRUE) continue;

		Player->set_available_tech_list();
		Player->set_project_list();
		Player->build_control_model();
	}

	PLAYER_TABLE->refresh_rank_table();
	COUNCIL_TABLE->refresh_rank_table();

	return true;
}

bool 
CGame::load_project(CMySQL& aMySQL)
{
	int
		MaxID;

	if( PLAYER_TABLE->length() > 0 )
		MaxID = ((CPlayer*)PLAYER_TABLE->get(PLAYER_TABLE->length()-1))->get_game_id();
	else
		return true;

	#define ROW(x) atoi(aMySQL.row( x ))

	enum
	{
		FIELD_OWNER = 0,
		FIELD_PROJECT_ID,
		FIELD_TYPE
	};

	SLOG("Project loading");
	int PCount = 0;

	aMySQL.query("LOCK TABLE project READ");

	for( int i = 0; i < MaxID+1000; i += 1000 ){
		CString
			Query;
		Query.format("SELECT owner, project_id, type FROM project WHERE owner > %d AND owner <= %d", i, i+1000);
		aMySQL.query( (char*)Query );

		aMySQL.use_result();

		while(aMySQL.fetch_row())
		{
			CPurchasedProject *
				Project = new CPurchasedProject;

			if (!Project->initialize(ROW(FIELD_OWNER),
									ROW(FIELD_PROJECT_ID),
									ROW(FIELD_TYPE)))
			{
				SLOG( "WRONG PROJECT LOADING : NO SUCH PROJECT"
					" : ProjectID = %d Owner = %d Type = %d",
						ROW(FIELD_PROJECT_ID), ROW(FIELD_OWNER));
				delete Project;
				continue;
			}

			if (Project->get_type() == TYPE_COUNCIL)
			{
				CCouncil
					*Owner = COUNCIL_TABLE->get_by_id(Project->get_owner());
				if (!Owner)
				{
					SLOG( "WRONG COUNCIL PROJECT LOADING : NO OWNER"
						" : ProjectID = %d Owner = %d",
						ROW(FIELD_PROJECT_ID), ROW(FIELD_OWNER));
					Project->type(QUERY_DELETE);
					STORE_CENTER->store(*Project);
					delete Project;
					continue;
				}
				Owner->add_project(Project);
			} else {
				CPlayer
					*Owner = PLAYER_TABLE->get_by_game_id(Project->get_owner());
				if (!Owner)
				{
					SLOG( "WRONG PLAYER PROJECT LOADING : NO OWNER"
						" : ProjectID = %d Owner = %d",
						ROW(FIELD_PROJECT_ID), ROW(FIELD_OWNER));
					Project->type(QUERY_DELETE);
					STORE_CENTER->store(*Project);
					delete Project;
					continue;
				}
				Owner->add_project(Project);
			}
			PCount++;
		}

		aMySQL.free_result();
	}

	aMySQL.query("UNLOCK TABLES");

	SLOG("%d Projects are loaded", PCount);

	return true;
}

bool 
CGame::load_admission(CMySQL& aMySQL)
{
	// load admission
	aMySQL.query( "LOCK TABLE admission READ" );
	aMySQL.query( "SELECT player, council, time, content FROM admission" );

	aMySQL.use_result();

	while(aMySQL.fetch_row())
	{
		CAdmission
			*Admission = new CAdmission(aMySQL.row());

		if(!Admission->get_player()) 
		{
			Admission->type(QUERY_DELETE);
			STORE_CENTER->store(*Admission);
			delete Admission;
		}
	}

	aMySQL.free_result();
	aMySQL.query( "UNLOCK TABLES" );
	SLOG("load admission");

	return true;
}

bool
CGame::load_diplomatic_message(CMySQL &aMySQL)
{
	aMySQL.query("LOCK TABLE diplomatic_message READ");
	aMySQL.query("SELECT * FROM diplomatic_message");

	aMySQL.use_result();

	while(aMySQL.fetch_row())
	{
		CDiplomaticMessage *
			Message = new CDiplomaticMessage(aMySQL.row());
		CPlayer *
			Receiver = Message->get_receiver();
		if (Receiver == NULL)
		{
			SLOG("ERROR : Receiver is NULL in CGame::load_diplomatic_message(), Message->get_id() = %d", Message->get_id());
			Message->type(QUERY_DELETE);
			STORE_CENTER->store(*Message);
		}
		else
		{
			Message->get_receiver()->add_diplomatic_message(Message);
		}
	}

	SLOG("load diplomatic_message");
	return true;
}

bool
CGame::load_council_message(CMySQL &aMySQL)
{
	aMySQL.query("LOCK TABLE council_message READ");
	aMySQL.query("SELECT * FROM council_message");

	aMySQL.use_result();

	while(aMySQL.fetch_row())
	{
		CCouncilMessage *
			Message = new CCouncilMessage(aMySQL.row());
		Message->get_receiver()->add_council_message(Message);
	}

	SLOG("load council_message");
	return true;
}

CPlayer *
CGame::create_new_player(int aPortalID, const char *aName, int aRace)
{
	int GameID;
	CPlayer* Player;
	CPlanet* Planet;
	CCluster* Cluster;
	CCouncil* Council;
	int Weight;
	int Range = 0;
	int Available = 0;
	int Dice;

	// check available room
	for (int i=0 ; i<UNIVERSE->length() ; i++)
	{
		Cluster = (CCluster *)UNIVERSE->get(i);
		if (Cluster->get_id() == EMPIRE_CLUSTER_ID) continue;

		if (Cluster->get_player_count() > MAX_PLAYER_PER_CLUSTER)
		{
			Cluster->set_accept_new_player(false);
			continue;
		}

		if (Cluster->get_planet_count() > (int)(MAX_PLANET_PER_CLUSTER*0.8))
		{
			Cluster->set_accept_new_player(false);
			continue;
		}
	
		Cluster->set_accept_new_player(true);
		Available++;
	}

	if (Available < CGame::mMinAvailableCluster)
	{
		CCluster *
			NewCluster = mUniverse->new_cluster();	

		CMagistrate *
			Magistrate = new CMagistrate();
		Magistrate->initialize(NewCluster->get_id());

		CMagistrateList *
			MagistrateList = EMPIRE->get_magistrate_list();
		MagistrateList->add_magistrate(Magistrate);
	}

	// calculate weight
	Range = 0;
	for(int i=1; i<UNIVERSE->length(); i++)
	{
		Cluster = (CCluster*)UNIVERSE->get(i);
		if (Cluster->get_id() == EMPIRE_CLUSTER_ID) continue;

		int PlayerCount = Cluster->get_player_count()/10;
		int PlanetCount = Cluster->get_planet_count()/100;
		if (PlayerCount && PlanetCount) 
		{
			Weight = (int)(MAX_PLAYER_PER_CLUSTER*MAX_PLANET_PER_CLUSTER/1000
							/ PlayerCount / PlanetCount);
			Cluster->set_weight(Weight);
			Range += Weight;
		} else {
			Weight = (int)(MAX_PLAYER_PER_CLUSTER*
											MAX_PLANET_PER_CLUSTER/1000);
			Cluster->set_weight(Weight);
			Range += Weight;
		}
/*		SLOG("Cluster Name:%s, Weight:%d, Range:%d", 
						Cluster->get_name(), Weight, Range);*/
	}

	// throw dice
	Dice = number(Range)-1;
//	SLOG("Throw dice %d:%d", Dice, Range);

	// select cluster
	Range = 0;
	for(int i=1; i<UNIVERSE->length(); i++)
	{
		Cluster = (CCluster*)UNIVERSE->get(i);
		if (Cluster->get_id() == EMPIRE_CLUSTER_ID) continue;

		if (Dice >= Range && Dice < Range+Cluster->get_weight())
			break;

		Range += Cluster->get_weight();
	}

	SLOG("Select Cluster %s", Cluster->get_name());


	Range = 0;
	Available = 0;

// for debug and make council
	if (COUNCIL_TABLE->length() < CGame::mMinCouncilCount)
	{
		Council = create_new_council(Cluster);
	}
	else
	{
		for (int i=0 ; i<COUNCIL_TABLE->length() ; i++)
		{
			Council = (CCouncil *)COUNCIL_TABLE->get(i);

			if (Council->get_auto_assign() == false) continue;
			if (Council->get_number_of_members() > (int)(MAX_COUNCIL_MEMBER*0.8)) continue;
			if (Council->get_number_of_members() >= Council->max_member()) continue;

			if (Council->has_cluster() && !Council->has_cluster(Cluster->get_id())) 
				continue;
	
			Available++;
			Weight = MAX_COUNCIL_MEMBER*2 - Council->get_number_of_members();
			Range += Weight;
		}

		if (Available < CGame::mMinCouncilCount)
		{
			Council = create_new_council(Cluster);
		}
		else
		{
			Dice = number(Range)-1;

			Range = 0;
			for (int i=0 ; i<COUNCIL_TABLE->length() ; i++)
			{
				Council = (CCouncil *)COUNCIL_TABLE->get(i);

				if (Council->get_auto_assign() == false) continue;
				if (Council->get_number_of_members() > (int)(MAX_COUNCIL_MEMBER*0.8)) continue;
				if (Council->get_number_of_members() >= Council->max_member()) continue;
				if (Council->has_cluster() && !Council->has_cluster(Cluster->get_id())) 
					continue;
	
				Weight = MAX_COUNCIL_MEMBER*2 - Council->get_number_of_members();

				if (Dice >= Range && Dice < Range+Weight) break;
				Range += Weight;
			}
		}
	}

	GameID = PLAYER_TABLE->get_max_id()+1;

	Player = new CPlayer(aPortalID, GameID, aName, aRace, Council);
	PLAYER_TABLE->add_player(Player);

	Player->set_home_cluster_id(Cluster->get_id());

	Planet = new CPlanet();
	Planet->set_order(0);
	Planet->initialize(Player->get_race_data());
	Planet->set_owner(Player);
	Planet->set_cluster(Cluster);
	Planet->set_name(Cluster->get_new_planet_name());
	Planet->change_population( 50000 );
	Planet->init_planet_news_center();

	PLANET_TABLE->add_planet(Planet);
	Player->add_planet(Planet);
	Player->new_planet_news(Planet);

	Planet->type(QUERY_INSERT);
	STORE_CENTER->store(*Planet);

	CKnownTechList *
		KnownTechList = Player->get_tech_list();
	KnownTechList->type(QUERY_INSERT);	
	STORE_CENTER->store(*KnownTechList);

	// tech, race, admiral init
	Player->discover_basic_techs();
	Player->init_race_innate();
	Player->init_admiral_innate();

	// set initial design
	CShipDesign
		*InitialDesign1 = new CShipDesign(),
		*InitialDesign2 = new CShipDesign();

	*InitialDesign1 = mInitialDesign1;
	*InitialDesign2 = mInitialDesign2;
	InitialDesign1->set_owner( Player->get_game_id() );
	InitialDesign2->set_owner( Player->get_game_id() );
	InitialDesign1->type( QUERY_INSERT );
	InitialDesign2->type( QUERY_INSERT );

	*STORE_CENTER << *InitialDesign1 << *InitialDesign2;
	Player->get_ship_design_list()->add_ship_design(InitialDesign1);
	Player->get_ship_design_list()->add_ship_design(InitialDesign2);

	// set initial fleet
	CFleet
		*Fleet = new CFleet;
	CAdmiral
		*Admiral = (CAdmiral*)Player->get_admiral_pool()->get(0);
	Fleet->set_id(1);
	Fleet->set_owner( Player->get_game_id() );
	Fleet->set_name( "1st Royal Guard Fleet" );
	Fleet->set_admiral( Admiral->get_id() );
	Fleet->set_ship_class( InitialDesign1 );
	Fleet->set_max_ship( 6 );
	Fleet->set_current_ship( 6 );
	Fleet->set_exp( 50 );
	Player->get_fleet_list()->add_fleet(Fleet);

	Fleet->type(QUERY_INSERT);
	*STORE_CENTER << *Fleet;

	Admiral->set_fleet_number(1);
	Player->get_admiral_pool()->remove_without_free_admiral(Admiral->get_id());
	Player->get_admiral_list()->add_admiral(Admiral);

	Admiral->type(QUERY_UPDATE);
	*STORE_CENTER << *Admiral;

	Fleet = new CFleet;
	Admiral = (CAdmiral*)Player->get_admiral_pool()->get(0);
	Fleet->set_id(2);
	Fleet->set_owner( Player->get_game_id() );
	Fleet->set_name( "2nd Royal Guard Fleet" );
	Fleet->set_admiral( Admiral->get_id() );
	Fleet->set_ship_class( InitialDesign1 );
	Fleet->set_max_ship( 6 );
	Fleet->set_current_ship( 6 );
	Fleet->set_exp( 50 );
	Player->get_fleet_list()->add_fleet(Fleet);

	Fleet->type(QUERY_INSERT);
	*STORE_CENTER << *Fleet;

	Admiral->set_fleet_number(2);
	Player->get_admiral_pool()->remove_without_free_admiral(Admiral->get_id());
	Player->get_admiral_list()->add_admiral(Admiral);

	Admiral->type(QUERY_UPDATE);
	*STORE_CENTER << *Admiral;

	Fleet = new CFleet;
	Admiral = (CAdmiral*)Player->get_admiral_pool()->get(0);
	Fleet->set_id(3);
	Fleet->set_owner( Player->get_game_id() );
	Fleet->set_name( "3rd Royal Guard Fleet" );
	Fleet->set_admiral( Admiral->get_id() );
	Fleet->set_ship_class( InitialDesign2 );
	Fleet->set_max_ship( 6 );
	Fleet->set_current_ship( 6 );
	Fleet->set_exp( 50 );
	Player->get_fleet_list()->add_fleet(Fleet);

	Fleet->type(QUERY_INSERT);
	*STORE_CENTER << *Fleet;

	Admiral->set_fleet_number(3);
	Player->get_admiral_pool()->remove_without_free_admiral(Admiral->get_id());
	Player->get_admiral_list()->add_admiral(Admiral);
	Fleet->init_mission(CMission::MISSION_STATION_ON_PLANET, Planet->get_id());

	Admiral->type(QUERY_UPDATE);
	*STORE_CENTER << *Admiral;

	Player->change_reserved_production( 50000 );

	// save player
	Player->type(QUERY_INSERT);
	STORE_CENTER->store(*Player);

	PLAYER_TABLE->add_player_rank(Player);
	COUNCIL_TABLE->add_council_rank(Council);
	COUNCIL_TABLE->refresh_rank_table();

	// error handling routine should be added.

	return Player;
}

CCouncil *
CGame::create_new_council(CCluster *aCluster, char *aName )
{
	if (!aCluster) return NULL;

	CCouncil
		*Council = new CCouncil();

	if (aName != NULL)
		Council->set_name(aName);
	else 
		Council->set_name("Untitled");

	Council->set_home_cluster_id(aCluster->get_id());

	COUNCIL_TABLE->add_council(Council);

	Council->type(QUERY_INSERT);
	STORE_CENTER->store(*Council);

	// added by thedaz for create council forum ->
/*
	game.cc -> 임의로 새로운 council 생김 -> 기본 speaker 없음

	catagories 에 COUNCIL_ID 입력ed
	forums 에 COUNCIL_ID 입력ed
	forum_mods 에 dummy moderator id 입력ed (user_id = -1)
	-> 임의로 council 이 생기는 상황이기때문에 speaker 가 없는 상태
	page_header 에서 IS_SPEAKER 가 YES 라면 users 에 user_id = GAME_ID 로, dummy moderator 를 GAME_ID 로 변경 

	moderator 가 없으므로 users 의 user_id 에 -1, username 에 "No speaker"
*/
	SLOG("THEDAZ: Create new council");

	char query[256];
	char str[256];
	char *council_name;

	council_name = add_slashes(Council->get_name());

	MYSQL db;

	mysql_init(&db);

	/* ## DB CONNECT ## */
	if (!mysql_real_connect(&db, "localhost", "space", "rlaclrnr", "CouncilForum", 0, NULL, 0))
	{ 
		sprintf(str, "THEDAZ: Failed to connect to database: Error: %s (game.cc)\n", mysql_error(&db));
		SLOG(str);
	}

	sprintf(query, "INSERT INTO catagories (cat_id, cat_title, cat_order) VALUES ('%d', '%s', '1')", Council->get_id(), council_name);

	/* ## INSERT catagories ## */
	if (mysql_query(&db, query) == -1)
	{ 
		sprintf(str, "THEDAZ: Failed to write database: Error: %s (game.cc)\n", mysql_error(&db));
		SLOG(str);
	}
	else
	{ 
		sprintf(str, "THEDAZ: Successed to write cat table (game.cc) -\t\t%d %s", Council->get_id(), council_name);
		SLOG(str);
	}

	sprintf(query, "INSERT INTO forums (forum_id, forum_name, forum_access, cat_id) VALUES ('%d', 'free board', '1', '%d')", Council->get_id(), Council->get_id());

	/* ## INSERT forums ## */
	if (mysql_query(&db, query) == -1)
	{
		sprintf(str, "THEDAZ: Failed to write database: Error: %s (game.cc)\n", mysql_error(&db));
		SLOG(str);
	}
	else
	{ 
		sprintf(str, "THEDAZ: Successed to write forums table (game.cc) -\t\t%d %s", Council->get_id(), council_name);
		SLOG(str);
	}

	sprintf(query, "INSERT INTO forum_mods (forum_id, user_id, cat_id) VALUES ('%d', '-1', '%d')", Council->get_id(), Council->get_id());

	/* ## INSERT forum_mods ## */
	if (mysql_query(&db, query) == -1)
	{
		sprintf(str, "THEDAZ: Failed to write datebate: Error: %s (game.cc)\n", mysql_error(&db));
		SLOG(str);
	}
	else
	{
		sprintf(str, "THEDAZ: Successed to write forum_mods tables (game.cc) -\t%d %s", Council->get_id(), council_name);
		SLOG(str);
	}

	sprintf(query, "INSERT INTO users (user_id, username, user_viewemail, user_level, cat_id) VALUES ('-1', 'No speaker', '0', '2', '%d')", Council->get_id());

	/* ## INSERT users ## */
	if (mysql_query(&db, query) == -1)
	{
		sprintf(str, "THEDAZ: Failed to write database: Error: %s (game.cc)\n", mysql_error(&db));
		SLOG(str);
	}
	else
	{
		sprintf(str, "THEDAZ: Successed to write users table (game.cc) -\t\t%d %s", Council->get_id(), council_name);
		SLOG(str);
	}

	mysql_close(&db);
	// added by thedaz for create council forum <-

	return Council;
}

bool
CGame::set_global_ending_data()
{
	if (EMPIRE->is_dead() == false) return false;

	CRankTable
		*PlayerScoreList = mGlobalEndingData.get_player_score_list(),
		*CouncilScoreList = mGlobalEndingData.get_council_score_list();

	CRankTable
		TempCouncilScoreList;

	for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
	{
		CPlayer *
			Player = (CPlayer *)PLAYER_TABLE->get(i);
		if (Player->get_game_id() == EMPIRE_GAME_ID) continue;
		if (Player->is_dead() == true) continue;

		CPlanetList *
			PlanetList = Player->get_planet_list();
		CKnownTechList *
			TechList = Player->get_tech_list();
		CAdmiralList *
			AdmiralList = Player->get_admiral_list();
		CAdmiralList *
			AdmiralPool = Player->get_admiral_pool();
		CPurchasedProjectList *
			ProjectList = Player->get_purchased_project_list();

		int
			Score = 0;

		Score += PlanetList->length() * CGlobalEnding::mScorePerPlanet;
		Score += Player->calc_population() / CGlobalEnding::mPopulationPerScore;

		for (int i=0 ; i<TechList->length() ; i++)
		{
			CKnownTech *
				Tech = (CKnownTech *)TechList->get(i);
			Score += Tech->get_level() * CGlobalEnding::mScorePerTechLevel;
		}

		for (int i=0 ; i<AdmiralList->length() ; i++)
		{
			CAdmiral *
				Admiral = (CAdmiral *)AdmiralList->get(i);
			Score += Admiral->get_exp() / CGlobalEnding::mAdmiralExpPerScore;
		}

		for (int i=0 ; i<AdmiralPool->length() ; i++)
		{
			CAdmiral *
				Admiral = (CAdmiral *)AdmiralPool->get(i);
			Score += Admiral->get_exp() / CGlobalEnding::mAdmiralExpPerScore;
		}

		for (int i=0 ; i<ProjectList->length() ; i++)
		{
			CPurchasedProject *
				Project = (CPurchasedProject *)ProjectList->get(i);
			if (PROJECT_TABLE->get_by_id(Project->get_id()) != NULL)
			{
				Score += Project->get_cost() / CGlobalEnding::mProjectPricePerScore;
			}
			else if (SECRET_PROJECT_TABLE->get_by_id(Project->get_id()) != NULL)
			{
				Score += CGlobalEnding::mScorePerSecretProject;
			}
			else
			{
				SLOG("ERROR : Wrong project ID in CGame::calc_ending_score(), Project->get_id() = %d, Player->get_game_id() = %d", Project->get_id(), Player->get_game_id());
				continue;
			}
		}

		Score += Player->get_turn() * CGlobalEnding::mScorePerUsedTurn;

		int
			Multiplier = 100;

		for (int i=0 ; i<ENDING_PROJECT_TABLE->length() ; i++)
		{
			CProject *
				EndingProject = (CProject *)ENDING_PROJECT_TABLE->get(i);
			if (ProjectList->get_by_id(EndingProject->get_id()) != NULL)
			{
				Multiplier += CGlobalEnding::mMultiplierForPersonalEnding;
				break;
			}
		}

		if (TECH_TABLE->length() == TechList->length())
		{
			Multiplier += CGlobalEnding::mMultiplierForAllKnownTechs;
		}

		if (Player->get_court_rank() != CPlayer::CR_NONE)
		{
			Multiplier += CGlobalEnding::mMultiplierForTitle;
		}

		if (Player->get_council()->get_speaker_id() == Player->get_game_id())
		{
			Multiplier += CGlobalEnding::mMultiplierForSpeaker;
		}

		int
			MultiplierByHonor = (Player->get_honor() - CGlobalEnding::mMultiplierForHonor1)/CGlobalEnding::mMultiplierForHonor2;
		if (MultiplierByHonor > 10) MultiplierByHonor = 10;
		if (MultiplierByHonor < -10) MultiplierByHonor = -10;

		Multiplier += MultiplierByHonor;

		Score += Score * Multiplier / 100;

		CRank *
			PlayerScore = new CRank();
		PlayerScore->set_id(Player->get_game_id());
		PlayerScore->set_power(Score);

		mGlobalEndingData.add_player_score(PlayerScore);
	}

	for (int i=0 ; i<COUNCIL_TABLE->length() ; i++)
	{
		CCouncil *
			Council = (CCouncil *)COUNCIL_TABLE->get(i);

		CRank *
			TempCouncilScore = new CRank();
		TempCouncilScore->set_id(Council->get_id());
		TempCouncilScore->set_power(0);

		TempCouncilScoreList.add_rank(TempCouncilScore);

		CRank *
			CouncilScore = new CRank();
		CouncilScore->set_id(Council->get_id());
		CouncilScore->set_power(0);

		mGlobalEndingData.add_council_score(CouncilScore);
	}

	for (int i=0 ; i<FORTRESS_LIST->length() ; i++)
	{
		CFortress *
			Fortress = (CFortress *)FORTRESS_LIST->get(i);
		if (Fortress->get_owner_id() == EMPIRE_GAME_ID) continue;

		CPlayer *
			Owner = PLAYER_TABLE->get_by_game_id(Fortress->get_owner_id());
		CCouncil *
			Council = Owner->get_council();

		CRank *
			PlayerScore = PlayerScoreList->get_by_id(Owner->get_game_id());
		PlayerScore->change_power(CGlobalEnding::mScorePerFortressForPlayer);

		CRank *
			TempCouncilScore = TempCouncilScoreList.get_by_id(Council->get_id());
		if (Fortress->get_layer() == 1)
		{
			TempCouncilScore->change_power(CGlobalEnding::mScorePerFortressForCouncilLayer1);
		}
		else if (Fortress->get_layer() == 2)
		{
			TempCouncilScore->change_power(CGlobalEnding::mScorePerFortressForCouncilLayer2);
		}
		else if (Fortress->get_layer() == 3)
		{
			TempCouncilScore->change_power(CGlobalEnding::mScorePerFortressForCouncilLayer3);
		}
		else if (Fortress->get_layer() == 4)
		{
			TempCouncilScore->change_power(CGlobalEnding::mScorePerFortressForCouncilLayer4);
		}
		else
		{
			SLOG("ERROR : Wrong layer in CGame::set_global_ending_data(), Fortress->get_layer() = %d, Fortress->get_sector() = %d, Fortress->get_order() = %d", Fortress->get_layer(), Fortress->get_sector(), Fortress->get_order());
			continue;
		}
	}

	for (int i=0 ; i<TempCouncilScoreList.length() ; i++)
	{
		CRank *
			TempCouncilScore = (CRank *)TempCouncilScoreList.get(i);
		if (TempCouncilScore->get_power() == 0) continue;

		CCouncil *
			Council = COUNCIL_TABLE->get_by_id(TempCouncilScore->get_id());
		CPlayerList *
			MemberList = Council->get_members();

		for (int j=0 ; j<MemberList->length() ; j++)
		{
			CPlayer *
				Member = (CPlayer *)MemberList->get(j);
			CRank *
				PlayerScore = PlayerScoreList->get_by_id(Member->get_game_id());
			PlayerScore->change_power(TempCouncilScore->get_power() / MemberList->length());
		}
	}

	for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
	{
		CPlayer *
			Player = (CPlayer *)PLAYER_TABLE->get(i);
		if (Player->get_game_id() == EMPIRE_GAME_ID) continue;
		if (Player->is_dead() == true) continue;

		CCouncil *
			Council = Player->get_council();
		CRank *
			PlayerScore = PlayerScoreList->get_by_id(Player->get_game_id());
		CRank *
			CouncilScore = CouncilScoreList->get_by_id(Council->get_id());

		CouncilScore->change_power(PlayerScore->get_power());
	}

	PlayerScoreList->quick_sort();
	CouncilScoreList->quick_sort();

	mGlobalEndingData.set_final_score();

	return true;
}

bool
CGame::tech_encyclopedia()
{
	CEncyclopediaTechIndex Index;
	CEncyclopediaTechPage Page;
	CEncyclopediaTechIndexGame IndexGame;
	CEncyclopediaTechPageGame PageGame;

/* <====================--------------------
	if (!Index.read("tech/index.html"))
	{
		SLOG("Could not found tech encyclopedia index form file");
		return false;
	}
	--------------------====================> */		// Omitted by YOSHIKI(2001/01/03)

	if (!Page.read("tech/page.html"))
	{
		SLOG("Could not found tech encyclopedia page form file");
		return false;
	}

	if (!PageGame.read("tech/page_game.html"))
	{
		SLOG("Could not found tech encyclopedia page form file");
		return false;
	}
/* <====================--------------------
	for (int i=0 ; i<CTech::TYPE_MAX ; i++)
	{
		Index.set(i);

		if (!Index.write())
		{
			SLOG("Could not write tech encyclopedia index page");
			return false;
		}
	}
	--------------------====================> */		// Omitted by YOSHIKI(2001/01/03)

	for(int i=0; i<mTechTable->length(); i++)
	{
		Page.set((CTech *)mTechTable->get(i));

		if (!Page.write())
		{
			SLOG("Could not write tech encyclopedia page");
			return false;
		}
	}

	for(int i=0; i<mTechTable->length(); i++)
	{
		PageGame.set((CTech *)mTechTable->get(i));

		if (!PageGame.write())
		{
			SLOG("Could not write tech encyclopedia page");
			return false;
		}
	}

	return true;
}


bool
CGame::race_encyclopedia()
{
	CEncyclopediaRaceIndex
		Index;
	CEncyclopediaRacePage
		Page;

	CEncyclopediaRacePageGame
		PageGame;

	if (!Index.read("race/index.html"))
	{
		SLOG("Could not found race encyclopedia index form file");
		return false;
	}

	if (!Page.read("race/page.html"))
	{
		SLOG("Could not found race encyclopedia page form file");
		return false;
	}

	if (!PageGame.read("race/page_game.html"))
	{
		SLOG("Could not found race encyclopedia page form file");
		return false;
	}

	Index.set(mRaceTable);

	if (!Index.write())
	{
		SLOG("Could not write race encyclopedia index page");
		return false;
	}

	for(int i=0; i<mRaceTable->length(); i++)
	{
		Page.set((CRace *)mRaceTable->get(i));

		if (!Page.write())
		{
			SLOG("Could not write race encyclopedia page");
			return false;
		}
	}

	for(int i=0; i<mRaceTable->length(); i++)
	{
		PageGame.set((CRace *)mRaceTable->get(i));

		if (!PageGame.write())
		{
			SLOG("Could not write race encyclopedia page");
			return false;
		}
	}

	return true;
}

bool
CGame::project_encyclopedia()
{
//	CEncyclopediaProjectIndex Index;
	CEncyclopediaProjectPage Page;
	CEncyclopediaProjectPageGame PageGame;
/*
	if (!Index.read("project/index.html"))
	{
		SLOG("Could not found project encyclopedia index form file");
		return false;
	}*/

	if (!Page.read("project/page.html"))
	{
		SLOG("Could not found project encyclopedia page form file");
		return false;
	}

	if (!PageGame.read("project/page_game.html"))
	{
		SLOG("Could not found project encyclopedia page form file");
		return false;
	}

/*
	Index.set(mProjectTable);

	for (int i=CProject::TYPE_BEGIN ; i<CProject::TYPE_MAX ; i++)
	{
		Index.set(i);

		if (!Index.write())
		{
			SLOG("Could not write project encyclopedia index page");
			return false;
		}
	}
*/

	for (int i=0 ; i<PROJECT_TABLE->length(); i++)
	{
		CProject *
			Project = (CProject *)PROJECT_TABLE->get(i);

		Page.set(Project);

		if (!Page.write())
		{
			SLOG("Could not write project encyclopedia page");
			return false;
		}
	}

	for (int i=0 ; i<PROJECT_TABLE->length(); i++)
	{
		CProject *
			Project = (CProject *)PROJECT_TABLE->get(i);

		PageGame.set(Project);

		if (!PageGame.write())
		{
			SLOG("Could not write project encyclopedia page");
			return false;
		}
	}

	return true;
}

/* start telecard 2000/10/02 */
bool
CGame::component_encyclopedia()
{
//	CEncyclopediaComponentIndex
//		index;
	CEncyclopediaComponentPage
		ArmorPage,
		ComputerPage,
		ShieldPage,
		EnginePage,
		DevicePage,
		WeaponPage;

	CEncyclopediaComponentPageGame
		ArmorPageGame,
		ComputerPageGame,
		ShieldPageGame,
		EnginePageGame,
		DevicePageGame,
		WeaponPageGame;

/* <====================--------------------
	if (!index.read("component/index.html"))
	{
		SLOG("Could not found component encyclopedia index form file");
		return false;
	}
	--------------------====================> */		// Omitted by YOSHIKI(2001/01/03)

	if (!ArmorPage.read("component/page_armor.html") ||
		!ComputerPage.read("component/page_computer.html") ||
		!ShieldPage.read("component/page_shield.html") ||
		!EnginePage.read("component/page_engine.html") ||
		!DevicePage.read("component/page_device.html") ||
		!WeaponPage.read("component/page_weapon.html"))
	{
		SLOG("Could not found component encyclopedia page form file");
		return false;
	}

	if (!ArmorPageGame.read("component/page_armor_game.html") ||
		!ComputerPageGame.read("component/page_computer_game.html") ||
		!ShieldPageGame.read("component/page_shield_game.html") ||
		!EnginePageGame.read("component/page_engine_game.html") ||
		!DevicePageGame.read("component/page_device_game.html") ||
		!WeaponPageGame.read("component/page_weapon_game.html"))
	{
		SLOG("Could not found component encyclopedia page form file");
		return false;
	}

/* <====================--------------------
	for (int i=0 ; i<CComponent::CC_MAX ; i++)
	{
		index.set(i);

		if (!index.write())
		{
			SLOG("Could not write component encyclopedia index page");
			return false;
		}
	}
	--------------------====================> */		// Omitted by YOSHIKI(2001/01/03)

	for(int i=0; i<mComponentTable->length(); i++)
	{
		CComponent *
			Component = (CComponent *)COMPONENT_TABLE->get(i);

		switch(Component->get_category())
		{
			case CComponent::CC_ARMOR :
			{
				ArmorPage.set(Component);
				if (!ArmorPage.write())
				{
					SLOG("Could not write component - armor encyclopedia page");
					return false;
				}
				break;
			}

			case CComponent::CC_COMPUTER :
			{
				ComputerPage.set(Component);
				if (!ComputerPage.write())
				{
					SLOG("Could not write component - computer encyclopedia page");
					return false;
				}
				break;
			}

			case CComponent::CC_SHIELD :
			{
				ShieldPage.set(Component);
				if (!ShieldPage.write())
				{
					SLOG("Could not write component - shield encyclopedia page");
					return false;
				}
				break;
			}

			case CComponent::CC_ENGINE :
			{
				EnginePage.set(Component);
				if (!EnginePage.write())
				{
					SLOG("Could not write component - engine encyclopedia page");
					return false;
				}
				break;
			}

			case CComponent::CC_DEVICE :
			{
				DevicePage.set(Component);
				if (!DevicePage.write())
				{
					SLOG("Could not write component - device encyclopedia page");
					return false;
				}
				break;
			}

			case CComponent::CC_WEAPON :
			{
				WeaponPage.set(Component);
				if (!WeaponPage.write())
				{
					SLOG("Could not write component - weapon encyclopedia page");
					return false;
				}
				break;
			}
		}
	}

	for(int i=0; i<mComponentTable->length(); i++)
	{
		CComponent *
			Component = (CComponent *)COMPONENT_TABLE->get(i);

		switch(Component->get_category())
		{
			case CComponent::CC_ARMOR :
			{
				ArmorPageGame.set(Component);
				if (!ArmorPageGame.write())
				{
					SLOG("Could not write component - armor encyclopedia page");
					return false;
				}
				break;
			}

			case CComponent::CC_COMPUTER :
			{
				ComputerPageGame.set(Component);
				if (!ComputerPageGame.write())
				{
					SLOG("Could not write component - computer encyclopedia page");
					return false;
				}
				break;
			}

			case CComponent::CC_SHIELD :
			{
				ShieldPageGame.set(Component);
				if (!ShieldPageGame.write())
				{
					SLOG("Could not write component - shield encyclopedia page");
					return false;
				}
				break;
			}

			case CComponent::CC_ENGINE :
			{
				EnginePageGame.set(Component);
				if (!EnginePageGame.write())
				{
					SLOG("Could not write component - engine encyclopedia page");
					return false;
				}
				break;
			}

			case CComponent::CC_DEVICE :
			{
				DevicePageGame.set(Component);
				if (!DevicePageGame.write())
				{
					SLOG("Could not write component - device encyclopedia page");
					return false;
				}
				break;
			}

			case CComponent::CC_WEAPON :
			{
				WeaponPageGame.set(Component);
				if (!WeaponPageGame.write())
				{
					SLOG("Could not write component - weapon encyclopedia page");
					return false;
				}
				break;
			}
		}
	}

	return true;
}
/* end telecard 2000/10/02 */

/* start telecard 2000/10/05 */
bool
CGame::ship_encyclopedia()
{
//	CEncyclopediaShipIndex
//		index;
	CEncyclopediaShipPage
		page;

//	CEncyclopediaShipIndexGame
//		indexGame;
	CEncyclopediaShipPageGame
		pageGame;
/*
	if (!index.read("ship/index.html"))
	{
		SLOG("Could not found ship encyclopedia index form file");
		return false;
	}

	if (!index.read("ship/index_game.html"))
	{
		SLOG("Could not found ship encyclopedia index form file");
		return false;
	}
*/
	if (!page.read("ship/page.html"))
	{
		SLOG("Could not found ship encyclopedia page form file");
		return false;
	}

	if (!pageGame.read("ship/page_game.html"))
	{
		SLOG("Could not found ship encyclopedia page form file");
		return false;
	}
/*
	index.set(mShipSizeTable);
	indexGame.set(mShipSizeTable);

	if (!index.write())
	{
		SLOG("Could not write ship encyclopedia index page");
		return false;
	}

	if (!indexGame.write())
	{
		SLOG("Could not write ship encyclopedia index page");
		return false;
	}
*/
	for(int i=0; i<mShipSizeTable->length(); i++)
	{
		page.set( (CShipSize *)mShipSizeTable->get(i) );

		if (!page.write())
		{
			SLOG("Could not write ship encyclopedia page");
			return false;
		}
	}

	for(int i=0; i<mShipSizeTable->length(); i++)
	{
		pageGame.set( (CShipSize *)mShipSizeTable->get(i) );

		if (!pageGame.write())
		{
			SLOG("Could not write ship encyclopedia page");
			return false;
		}
	}

	return true;
}

bool
CGame::spy_encyclopedia()
{
	CEncyclopediaSpyIndex
		Index;
	CEncyclopediaSpyPage
		Page;

	CEncyclopediaSpyIndexGame
		IndexGame;
	CEncyclopediaSpyPageGame
		PageGame;

	if (!Index.read("special_ops/index.html"))
	{
		SLOG("Could not found spy encyclopedia index form file");
		return false;
	}

	if (!Page.read("special_ops/page.html"))
	{
		SLOG("Could not found spy encyclopedia page form file");
		return false;
	}

	if (!IndexGame.read("special_ops/index_game.html"))
	{
		SLOG("Could not found spy encyclopedia index form file");
		return false;
	}

	if (!PageGame.read("special_ops/page_game.html"))
	{
		SLOG("Could not found spy encyclopedia page form file");
		return false;
	}

	Index.set(mSpyTable);
	if (!Index.write())
	{
		SLOG("Could not write spy encyclopedia index page");
		return false;
	}

	IndexGame.set(mSpyTable);
	if (!IndexGame.write())
	{
		SLOG("Could not write spy encyclopedia index page");
		return false;
	}

	for (int i=0 ; i<mSpyTable->length() ; i++)
	{
		CSpy *
			Spy = (CSpy *)mSpyTable->get(i);
		Page.set(Spy);

		if (!Page.write())
		{
			SLOG("Could not write spy encyclopedia page");
			return false;
		}
	}

	for (int i=0 ; i<mSpyTable->length() ; i++)
	{
		CSpy *
			Spy = (CSpy *)mSpyTable->get(i);
		PageGame.set(Spy);

		if (!PageGame.write())
		{
			SLOG("Could not write ship encyclopedia page");
			return false;
		}
	}

	return true;
}

void
CGame::lock()
{
	pth_mutex_acquire(&mMutex, FALSE, NULL);
}

void
CGame::unlock()
{
	pth_mutex_release(&mMutex);
	pth_nap((pth_time_t){0, 1});
}

void
CGame::spawn_update_thread()
{
/*	pth_attr_t Attribute;

	Attribute = pth_attr_new();

	pth_attr_set(Attribute, PTH_ATTR_NAME, "Update Thread");
	pth_attr_set(Attribute, PTH_ATTR_STACK_SIZE, 32*1024);
	pth_attr_set(Attribute, PTH_ATTR_JOINABLE, FALSE);*/

	mUpdateThread = pth_spawn(PTH_ATTR_DEFAULT, CPlayerTable::update, NULL);
	if (mUpdateThread == NULL) SLOG("Could not spawn update thread");

	SLOG("Spawn Update Thread");
}

void 
CGame::kill_update_thread()
{
	pth_cancel(mUpdateThread);
}
