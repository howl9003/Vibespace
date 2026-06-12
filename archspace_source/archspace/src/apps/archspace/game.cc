#include "game.h"
#include "race.h"
#include "council.h"
#include "archspace.h"
#include <cstdlib>
#include <cstdio>
#include <cstring>
#include "script.h"
#include "ending.h"
#include "battle.h"
#include "encyclopedia.h"
#include "banner.h"
#include "admin.h"
#include "bounty.h"
#include "fleet.h"

CGame::ELanguage CGame::mLanguage = CGame::LANG_EN;
CString CGame::mImageServerURL;
CString CGame::mForumServerURL;

time_t CGame::mGameStartTime = 0;
time_t CGame::mServerStartTime = 0;
bool   CGame::mUpdateTurn = true;
float CGame::mTechRate = 0;
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
    mPortalUserTable = NULL; 
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
    if (mPortalUserTable)
        delete mPortalUserTable;
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
	// Modern UTF-8 for all languages (the adapter also emits a UTF-8 HTTP
	// Content-Type); avoids the legacy iso-8859-1/euc-kr mojibake.
	return "charset=utf-8";
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

	CGame::mUpdateTurn = mConfig->get_boolean("Game", "StartGame", true);
	
	mTechRate = 1;

	// Same-origin assets: an empty ImageServerURL is allowed and means images
	// are served from this origin as /image/... (we collapsed the original
	// separate image server). Default to empty rather than aborting.
	TempString = mConfig->get_string("Game", "ImageServerURL", NULL);
	mImageServerURL = (TempString == NULL) ? "" : TempString;

	// The in-game forum link is inert in this build; an unset ForumServerURL
	// is fine.
	TempString = mConfig->get_string("Game", "ForumServerURL", NULL);
	mForumServerURL = (TempString == NULL) ? "" : TempString;

	Temp = mConfig->get_integer("Game", "PeriodPlayerCouncilDonation", -1);
	if (Temp >= -1) CAction::mPeriodPlayerCouncilDonation = Temp;

	Temp = mConfig->get_integer("Game", "PeriodPlayerBreakPact", -1);
	if (Temp >= -1) CAction::mPeriodPlayerBreakPact = Temp;

	Temp = mConfig->get_integer("Game", "PeriodPlayerBreakAlly", -1);
	if (Temp >= -1) CAction::mPeriodPlayerBreakAlly = Temp;

	Temp = mConfig->get_integer("Game", "PeriodCouncilDeclareTotalWar", -1);
	if (Temp >= -1) CAction::mPeriodCouncilDeclareTotalWar = Temp;

	Temp = mConfig->get_integer("Game", "PeriodCouncilDeclareWar", -1);
	if (Temp >= -1) CAction::mPeriodCouncilDeclareWar = Temp;

	Temp = mConfig->get_integer("Game", "PeriodCouncilBreakPact", -1);
	if (Temp >= -1) CAction::mPeriodCouncilBreakPact = Temp;

	Temp = mConfig->get_integer("Game", "PeriodCouncilBreakAlly", -1);
	if (Temp >= -1) CAction::mPeriodCouncilBreakAlly = Temp;

	Temp = mConfig->get_integer("Game", "PeriodCouncilImproveRelation", -1);
	if (Temp >= -1) CAction::mPeriodCouncilImproveRelation = Temp;

	Temp = mConfig->get_integer("Game", "PeriodCouncilMergingOffer", -1);
	if (Temp >= -1) CAction::mPeriodCouncilMergingOffer = Temp;

	Temp = mConfig->get_integer("Game", "HonorIncreaseTurns", -1);
	if (Temp > 0) CPlayer::mHonorIncreaseTurns = Temp;

	Temp = mConfig->get_integer("Game", "PeriodCreateAdmiral", -1);
	if (Temp > 0) CPlayer::mPeriodCreateAdmiral = Temp;
	
	Temp = mConfig->get_integer("Game", "AdmiralMilitaryBonus", -1);
	if (Temp > 0) CPlayer::mAdmiralMilitaryBonus = Temp;
	
	Temp = mConfig->get_integer("Game", "MaxPeriodCreateAdmiral", -1);
	if (Temp > 0) CPlayer::mMaxPeriodCreateAdmiral = Temp;
	
	Temp = mConfig->get_integer("Game", "MaxAdmirals", -1);
	if (Temp > 0) CPlayer::mMaxAdmirals = Temp;

	Temp = mConfig->get_integer("Game", "MinFleetExpTrain", -1);
	if (Temp > 0) CMission::mFleetExpMinTrain = Temp;

	Temp = mConfig->get_integer("Game", "MinAdmiralExpTrain", -1);
	if (Temp > 0) CMission::mAdmiralExpMinTrain = Temp;

	Temp = mConfig->get_integer("Game", "MinAdmiralExpPrivateer", -1);
	if (Temp > 0) CMission::mAdmiralExpMinPrivateer = Temp;
	
	Temp = mConfig->get_integer("Game", "MaxAdmiralExpPrivateer", -1);
	if (Temp > 0) CMission::mAdmiralExpMaxPrivateer = Temp;
	
	Temp = mConfig->get_integer("Game", "MaxPrivateerCapacity", -1);
	if (Temp > 0) CMission::mMaxPrivateerCapacity = Temp;

	Temp = mConfig->get_integer("Game", "AdmiralExpExpedition", -1);
	if (Temp > 0) CMission::mAdmiralExpExpedition = Temp;

	Temp = mConfig->get_integer("Game", "AdmiralExpRaid", -1);
	if (Temp > 0) CMission::mAdmiralExpRaid = Temp;
	
	Temp = mConfig->get_integer("Game", "AdmiralExpRaidMultiplier", -1);
	if (Temp > 0) CMission::mAdmiralExpRaidMultiplier = Temp;
	
	Temp = mConfig->get_integer("Game", "AdmiralExpStation", -1);
	if (Temp > 0) CMission::mAdmiralExpStation = Temp;
	
	Temp = mConfig->get_integer("Game", "AdmiralExpPatrol", -1);
	if (Temp > 0) CMission::mAdmiralExpPatrol = Temp;
	
	Temp = mConfig->get_integer("Game", "AdmiralExpBattle", -1);
	if (Temp > 0) CMission::mAdmiralExpBattle = Temp;
	
	Temp = mConfig->get_integer("Game", "AdmiralExpDetect", -1);
	if (Temp > 0) CMission::mAdmiralExpDetect = Temp;

	Temp = mConfig->get_integer("Game", "ResourceMultiplierUltraPoor", -1);
	if (Temp > 0) CPlanet::mRatioUltraPoor = Temp;
	
	Temp = mConfig->get_integer("Game", "ResourceMultiplierPoor", -1);
	if (Temp > 0) CPlanet::mRatioPoor = Temp;
	
	Temp = mConfig->get_integer("Game", "ResourceMultiplierNormal", -1);
	if (Temp > 0) CPlanet::mRatioNormal = Temp;

	Temp = mConfig->get_integer("Game", "ResourceMultiplierRich", -1);
	if (Temp > 0) CPlanet::mRatioRich = Temp;
	
	Temp = mConfig->get_integer("Game", "ResourceMultiplierUltraRich", -1);
	if (Temp > 0) CPlanet::mRatioUltraRich = Temp;

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

	Temp = mConfig->get_integer("Game", "RetireTimeLimit", -1);
	if (Temp > 0) CPlayer::mRetireTimeLimit = Temp;

	Temp = mConfig->get_integer("Game", "AdmissionTimeLimit", -1);
	if (Temp > 0) CPlayer::mAdmissionLimitTime = Temp;

	Temp = mConfig->get_integer("Game", "FusionTimeLimit", -1);
	if (Temp > 0) CCouncil::mFusionTimeLimit = Temp;

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

    mMySQLPool = new CMySQLPool();
    mMySQLPool->initialize(Host,User,Password,Database,8);
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
	mInitialDesign1.set_weapon_number( 0, 4 );
	mInitialDesign1.set_build_time( time(0) );
	mInitialDesign1.set_build_cost( 1000 );

	mInitialDesign2.set_design_id( 2 );
	mInitialDesign2.set_name( "Star Corvette Mk.I" );
	mInitialDesign2.set_body( 4002 );
	mInitialDesign2.set_armor( 5101 );
	mInitialDesign2.set_engine( 5401 );
	mInitialDesign2.set_computer( 5201 );
	mInitialDesign2.set_shield( 5301 );
	mInitialDesign2.set_weapon( 0, 6301 );
	mInitialDesign2.set_weapon_number( 0, 2 );
	mInitialDesign2.set_weapon( 1, 6201 );
	mInitialDesign2.set_weapon_number( 1, 4 );
	mInitialDesign2.set_build_time( time(0) );
	mInitialDesign2.set_build_cost( 3097 );

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
	else
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
//		Now = time(0),
        Now = CGame::get_game_time(),
		Week = 60*60*24*7;

	for (int i=PLAYER_TABLE->length()-1 ; i>=0 ; i--)
	{
		CPlayer *
			Player = (CPlayer *)PLAYER_TABLE->get(i);
		if (Player->get_game_id() == EMPIRE_GAME_ID) continue;
		// TODO: make this set by config
		if (Player->get_tick() < Now - Week)
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
/*	
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
*/
// end telecard 2001/03/14

// start telecard 2001/03/27
/*
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
*/
// end telecard 2001/03/27

	if(!project_encyclopedia())
	{
		SLOG("Could not make project encyclopedia");
		return false;
	}

	SLOG("load project script");

// start telecard 2001/03/31
/*	
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
*/
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

    SLOG("Loading Portal Users from DB");
    mPortalUserTable = new CPortalUserTable();
    if (!mPortalUserTable)
    {
        SLOG("Could not allocate portal user table");
        return false;
    }
    mPortalUserTable->load(MySQL);
    SLOG("Loaded Portal User Table");

	mPlayerTable = new CPlayerTable();
	if (!mPlayerTable)
	{
		SLOG("Could not allocate player table");
		return false;
	}

    SLOG("Creating Empire Player");
	CPlayer *
		Empire = new CPlayer(0);
	mPlayerTable->add_player(Empire);
    SLOG("Loading Players from DB");
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

    SLOG("Loading Bounty Table");
    mBountyTable = new CBountyTable();
    if (!mBountyTable)
    {
        SLOG("Could not allocate bounty table");
        return false;
    }
    mBountyTable->load(MySQL);
    SLOG("Loaded Bounty Table");

    SLOG("Loading Offered Bounty Table");
    mOfferedBountyTable = new COfferedBountyTable();
    SLOG("Loaded Offered Bounty Table");


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

	//aMySQL.query("LOCK TABLE project READ");

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

	//aMySQL.query("UNLOCK TABLES");

	SLOG("%d Projects are loaded", PCount);

	return true;
}

bool 
CGame::load_admission(CMySQL& aMySQL)
{
	// load admission
	//aMySQL.query( "LOCK TABLE admission READ" );
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
	//aMySQL.query( "UNLOCK TABLES" );
	SLOG("load admission");

	return true;
}

bool
CGame::load_diplomatic_message(CMySQL &aMySQL)
{
	//aMySQL.query("LOCK TABLE diplomatic_message READ");
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
	//aMySQL.query("LOCK TABLE council_message READ");
	aMySQL.query("SELECT * FROM council_message");

	aMySQL.use_result();

	while(aMySQL.fetch_row())
	{
		CCouncilMessage *
			Message = new CCouncilMessage(aMySQL.row());
		if (Message->get_receiver() != NULL) 
		{
			Message->get_receiver()->add_council_message(Message);
		}
		else
		{
			SLOG("Council Message reciever = NULL");
			Message->type(QUERY_DELETE);
			STORE_CENTER->store(*Message);
		}
	}

	SLOG("load council_message");
	return true;
}

bool 
CGame::load_bounty(CMySQL& aMySQL)
{
	mBountyTable->load(aMySQL);
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

    // add portal info if nonexistant
    // TODO: make this happen on registration if server running instead
    if (PORTAL_TABLE->get_by_portal_id(aPortalID) == NULL)
    {
     if (!PORTAL_TABLE->load_new_by_id(aPortalID))
        SLOG("game.cc -- create_new_player(): "
             "Could not add Portal User: %d "
             "Player: %d", aPortalID, GameID);    
    }
    
	Player->set_home_cluster_id(Cluster->get_id());

	Planet = new CPlanet();
	Planet->set_order(0);
	Planet->initialize(Player->get_race_data());
	Planet->set_owner(Player);
	Planet->set_cluster(Cluster);
	Planet->set_name(Cluster->get_new_planet_name());
	Planet->change_population( 50000 );
	Planet->init_planet_news_center();
	Planet->normalize();

	PLANET_TABLE->add_planet(Planet);
	Player->add_planet(Planet);
	Player->new_planet_news(Planet);
	Player->set_planet_invest_pool(0);

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

// Build a ship design from the best components the bot's tech has unlocked
// (mirrors page/fleet/ship_design_register.cc, picking get_best_component() per
// category). aShipSize is the hull (1..10). Returns the registered design, or
// NULL if a core slot can't be filled. Stored on the player's design list.
static CShipDesign *
make_best_bot_design(CPlayer *aPlayer, int aShipSize)
{
	if (aShipSize < 1) aShipSize = 1;
	if (aShipSize > 10) aShipSize = 10;

	CShipSize *Body = (CShipSize *)SHIP_SIZE_TABLE->get_by_id(4000 + aShipSize);
	if (Body == NULL) return NULL;

	CComponentList *CompList = aPlayer->get_component_list();
	CComponent *Computer = CompList->get_best_component(CComponent::CC_COMPUTER);
	CComponent *Engine   = CompList->get_best_component(CComponent::CC_ENGINE);
	CComponent *Shield   = CompList->get_best_component(CComponent::CC_SHIELD);
	CComponent *Armor    = CompList->get_best_component(CComponent::CC_ARMOR);
	CComponent *Weapon   = CompList->get_best_component(CComponent::CC_WEAPON);
	if (!Computer || !Engine || !Shield || !Armor || !Weapon) return NULL;

	CShipDesignList *DesignList = aPlayer->get_ship_design_list();
	CShipDesign *Design = new CShipDesign;
	Design->set_name((char *)format("BOT Class %d", aShipSize));
	Design->set_design_id(DesignList->max_design_id() + 1);
	Design->set_owner(aPlayer->get_game_id());
	Design->set_body(4000 + aShipSize);
	Design->set_build_cost(Body->get_cost());
	Design->set_build_time((int)time((time_t *)0));
	Design->set_computer(Computer->get_id());
	Design->set_engine(Engine->get_id());
	Design->set_shield(Shield->get_id());
	Design->set_armor(Armor->get_id());

	// fill every weapon slot the hull has with the best weapon (count = how many
	// fit per slot); leave the rest empty.
	int WeaponSpace = ((CWeapon *)Weapon)->get_space();
	if (WeaponSpace < 1) WeaponSpace = 1;
	for (int i=0 ; i<WEAPON_MAX_NUMBER ; i++)
	{
		if (i < Body->get_weapon() && Body->get_slot() >= WeaponSpace)
		{
			Design->set_weapon(i, Weapon->get_id());
			Design->set_weapon_number(i, Body->get_slot() / WeaponSpace);
		}
		else
		{
			Design->set_weapon(i, 0);
			Design->set_weapon_number(i, 0);
		}
	}

	// one best device (slot 0) if the hull has a device slot and it fits this
	// size; devices must be distinct, so we don't repeat it across slots.
	CComponent *Device = CompList->get_best_component(CComponent::CC_DEVICE);
	for (int i=0 ; i<DEVICE_MAX_NUMBER ; i++)
	{
		if (i == 0 && i < Body->get_device() && Device != NULL &&
			((CDevice *)Device)->get_min_class() <= aShipSize &&
			((CDevice *)Device)->get_max_class() >= aShipSize)
			Design->set_device(i, Device->get_id());
		else
			Design->set_device(i, 0);
	}

	DesignList->add_ship_design(Design);
	Design->type(QUERY_INSERT);
	*STORE_CENTER << *Design;
	return Design;
}

// Build a ship design for a SPECIFIC hull (1..10) at a SPECIFIC component level
// (1..5), independent of the owner's tech: components are selected straight from
// COMPONENT_TABLE by level, exactly the way the black market builds its named
// "<Hull> Mk. <level>" designs (blackmarket.cc). Names it the same way so a tier's
// ships read like a recognizable class. The random weapon/device picks vary call
// to call, so calling it a few times yields an assortment of distinct designs.
// Registered (owner = aPlayer) on the player's design list. NULL if a core slot
// can't be filled for that level.
static CShipDesign *
make_design_at_level(CPlayer *aPlayer, int aHull, int aLevel)
{
	if (aHull < 1)  aHull = 1;
	if (aHull > 10) aHull = 10;
	if (aLevel < 1) aLevel = 1;
	if (aLevel > 5) aLevel = 5;

	CShipSize *Body = (CShipSize *)SHIP_SIZE_TABLE->get_by_id(4000 + aHull);
	if (Body == NULL) return NULL;

	// Core components at exactly this level; weapons at this level; devices up to
	// this level (mirrors CBlackMarket::create_new_fleet's selection).
	int Armor = 0, Engine = 0, Computer = 0, Shield = 0;
	CComponent *WeaponComp[256]; int WeaponN = 0;
	CComponent *DeviceComp[256]; int DeviceN = 0;
	for (int i=0 ; i<COMPONENT_TABLE->length() ; i++)
	{
		CComponent *C = (CComponent *)COMPONENT_TABLE->get(i);
		if (C == NULL) continue;
		int Cat = C->get_category();
		int Lvl = C->get_level();
		if (Lvl == aLevel)
		{
			switch (Cat)
			{
				case CComponent::CC_ARMOR:    Armor    = C->get_id(); break;
				case CComponent::CC_ENGINE:   Engine   = C->get_id(); break;
				case CComponent::CC_COMPUTER: Computer = C->get_id(); break;
				case CComponent::CC_SHIELD:   Shield   = C->get_id(); break;
				case CComponent::CC_WEAPON:
					if (WeaponN < 256) WeaponComp[WeaponN++] = C;
					break;
			}
		}
		if (Cat == CComponent::CC_DEVICE && Lvl <= aLevel && DeviceN < 256)
			DeviceComp[DeviceN++] = C;
	}
	if (!Armor || !Engine || !Computer || !Shield || WeaponN == 0) return NULL;

	CShipDesignList *DesignList = aPlayer->get_ship_design_list();
	CShipDesign *Design = new CShipDesign;
	CString Name = Body->get_name();
	Name.format(" Mk. %d", aLevel);          // -> "Frigate Mk. 3" etc.
	Design->set_name((char *)Name);
	Design->set_design_id(DesignList->max_design_id() + 1);
	Design->set_owner(aPlayer->get_game_id());
	Design->set_body(4000 + aHull);
	Design->set_build_cost(Body->get_cost());
	Design->set_build_time((int)time((time_t *)0));
	Design->set_armor(Armor);
	Design->set_engine(Engine);
	Design->set_computer(Computer);
	Design->set_shield(Shield);

	// fill the hull's weapon slots with random level-matched weapons
	for (int i=0 ; i<WEAPON_MAX_NUMBER ; i++)
	{
		if (i < Body->get_weapon())
		{
			CWeapon *W = (CWeapon *)WeaponComp[number(WeaponN) - 1];
			int Space = W->get_space();
			if (Space < 1) Space = 1;
			int Num = Body->get_slot() / Space;
			if (Num < 1) Num = 1;
			Design->set_weapon(i, W->get_id());
			Design->set_weapon_number(i, Num);
		}
		else
		{
			Design->set_weapon(i, 0);
			Design->set_weapon_number(i, 0);
		}
	}

	// fill the hull's device slots with distinct random devices (<= this level)
	int DevicePlaced[DEVICE_MAX_NUMBER]; int DevicePlacedN = 0;
	for (int i=0 ; i<DEVICE_MAX_NUMBER ; i++)
	{
		int Chosen = 0;
		if (i < Body->get_device() && DeviceN > 0)
		{
			for (int tries=0 ; tries<8 && Chosen==0 ; tries++)
			{
				int Id = DeviceComp[number(DeviceN) - 1]->get_id();
				bool Dup = false;
				for (int d=0 ; d<DevicePlacedN ; d++)
					if (DevicePlaced[d] == Id) { Dup = true; break; }
				if (!Dup) Chosen = Id;
			}
		}
		Design->set_device(i, Chosen);
		if (Chosen) DevicePlaced[DevicePlacedN++] = Chosen;
	}

	DesignList->add_ship_design(Design);
	Design->type(QUERY_INSERT);
	*STORE_CENTER << *Design;
	return Design;
}

// Add one full fleet flying aDesign to aPlayer under a fresh level-aLevel admiral
// of the bot's race, crewed to the admiral's capacity, at aExp experience; persist
// fleet + admiral. aExpedition launches it on an auto-repeat expedition (the one
// permanent expedition fleet every bot keeps); otherwise it stands by to defend.
// Shared by create_bot_player and the regen cron (see player.h).
CFleet *
bot_add_fleet(CPlayer *aPlayer, CShipDesign *aDesign, int aLevel, int aExp, bool aExpedition)
{
	if (aPlayer == NULL || aDesign == NULL) return NULL;

	CFleetList   *FleetList   = aPlayer->get_fleet_list();
	CAdmiralList *AdmiralList = aPlayer->get_admiral_list();

	// a fresh commander of the bot's own race at the requested level
	CAdmiral *Admiral = new CAdmiral(aLevel, 0, 0, aPlayer->get_race());
	Admiral->set_owner(aPlayer->get_game_id());
	AdmiralList->add_admiral(Admiral);

	int Capacity = Admiral->get_fleet_commanding();
	if (Capacity < 1) Capacity = 1;

	CFleet *Fleet = new CFleet();
	Fleet->set_id(FleetList->get_new_fleet_id());
	Fleet->set_owner(aPlayer->get_game_id());
	Fleet->set_name((char *)format("BOT Fleet(%d)", Fleet->get_id()));
	Fleet->set_admiral(Admiral->get_id());
	Fleet->set_ship_class(aDesign);
	Fleet->set_max_ship(Capacity);
	Fleet->set_current_ship(Capacity);
	Fleet->set_exp(aExp);
	FleetList->add_fleet(Fleet);

	Admiral->set_fleet_number(Fleet->get_id());

	// target=1 -> auto-repeat: the expedition relaunches each time it returns.
	if (aExpedition)
		Fleet->init_mission(CMission::MISSION_EXPEDITION, 1);

	Admiral->type(QUERY_INSERT);
	STORE_CENTER->store(*Admiral);
	Fleet->type(QUERY_INSERT);
	STORE_CENTER->store(*Fleet);
	return Fleet;
}

// Per-race faction / collective names, each flavoured after THAT race's own lore
// (see the encyclopedia race descriptions) so a name always lands on a fitting
// race -- a Targoid never reads as an "Evintos Foundry". Indexed by race id via
// sRaceFaction[] below. Keep each <= player.name's width (40).
static const char *sFactionHuman[] = {        // Classism; fast-breeding idealist expansionists
	"Terran Ascendancy", "Solar Concord", "Concord of Ideals", "Human Imperium",
	"Terran Republic", "Sol Dominion", "United Earth Worlds", "Humanity's Vanguard",
	"Terran Hegemony", "The Idealist Compact", "Earthborn Federation", "Manifest Concord",
};
static const char *sFactionTargoid[] = {      // Totalism; one mother-body hive, DNA-bred
	"The Targoid Brood", "Mother-Body Collective", "The Hive Totality", "Targoid Swarm",
	"The Brood Collective", "Gestalt Hive", "Targoid Hegemony", "One-Body Dominion",
	"The Spawning Throng", "Broodmind Union", "The Teeming Hive", "Carapace Collective",
};
static const char *sFactionBuckaneer[] = {    // Personalism; roaming trader-pirates
	"Buckaneer Free Companies", "Corsair Confederacy", "The Freebooter Cartel",
	"Buckaneer Trade Combine", "The Gypsy Fleets", "Privateer League", "The Roving Compact",
	"Buckaneer Merchant Guild", "The Vagrant Armada", "Smuggler's Concord",
	"The Wayfarer Cartel", "Corsair Free Fleets",
};
static const char *sFactionTecanoid[] = {     // Classism; cyborg elite, infiltrators
	"Tecanoid Directorate", "The Synthetic Elite", "Cybernetic Conclave",
	"Tecanoid Ascendancy", "Bionic Dominion", "The Augmented Order", "Machine-Flesh Hegemony",
	"Tecanoid Datasphere", "The Upgraded", "Circuit Imperium", "The Cyber Directorate",
	"Grafted Union",
};
static const char *sFactionEvintos[] = {      // Totalism; silicon-gold machine-minds, mass production
	"Evintos Foundry", "The Silicon Hegemony", "Goldforged Union", "The Auric Assembly",
	"Evintos Collective", "Crystalline Order", "The Forge Totality", "Silicon-Gold Compact",
	"Latticework Dominion", "Evintos Manufactorum", "The Argent Foundry", "Goldsilicon Concord",
};
static const char *sFactionAgerus[] = {       // Totalism; secretive planet-beings, defensive
	"Agerus Worldmind", "The Sleeping Worlds", "Communion of Planets", "Agerus Wardens",
	"The Spore-Born", "Planetary Bastion", "The Mother-Planet", "Worldspawn Compact",
	"Agerus Bulwark", "The Quiet Worlds", "The Dreaming Spheres", "Geosentient Communion",
};
static const char *sFactionBosalian[] = {     // Personalism; pacifist psychics, mediators
	"The Bosalian Accord", "Psionic Concord", "Aurora Communion", "Bosalian Mediators",
	"The Peacekeeper League", "The Serene Compact", "Psychic Aurora", "Bosalian Harmony",
	"The Impartial Order", "Mindlight Union", "The Tranquil Accord", "Auroral Concord",
};
static const char *sFactionXeloss[] = {       // Totalism; fanatical psychic zealots
	"Xeloss Theocracy", "Crusade of the One God", "The Devout Swarm", "Xeloss Inquisition",
	"The Zealot Host", "The God-Sworn", "Martyr's Crusade", "Xeloss Faithful",
	"The Blood Liturgy", "Sanctified Dominion", "The Fervent Host", "Xeloss Convocation",
};
static const char *sFactionXerusian[] = {     // Classism; ancient elite militarists
	"Xerusian Imperium", "The Iron Legion", "Vanguard of Xerus", "Xerusian War-Host",
	"The Elite Cohort", "Matter-Energy Legion", "Xerusian Bastion", "The Old Guard",
	"Warforged Imperium", "Xerusian High Command", "The Adamant Legion", "Xerus Praetorian",
};
static const char *sFactionXesperados[] = {   // Personalism; merged rebel exiles, open to all
	"Xesperados Coalition", "The Free Banners", "Union of Exiles", "Rebel Confederacy",
	"The Open Compact", "Xesperados Alliance", "Banner-Host of Exiles", "The Gathered Banners",
	"Renegade League", "Xesperados Concord", "The Exile Coalition", "Freeborn Alliance",
};

struct CRaceFactionPool { const char **mNames; int mCount; };
#define FPOOL(a) { a, (int)(sizeof(a)/sizeof((a)[0])) }
// Indexed by (race id - RACE_HUMAN), i.e. race 1..10 -> 0..9.
static const CRaceFactionPool sRaceFaction[] = {
	FPOOL(sFactionHuman), FPOOL(sFactionTargoid), FPOOL(sFactionBuckaneer),
	FPOOL(sFactionTecanoid), FPOOL(sFactionEvintos), FPOOL(sFactionAgerus),
	FPOOL(sFactionBosalian), FPOOL(sFactionXeloss), FPOOL(sFactionXerusian),
	FPOOL(sFactionXesperados),
};
#undef FPOOL
static const int sRaceFactionCount =
	sizeof(sRaceFaction) / sizeof(sRaceFaction[0]);

// Rank words used both as commander-name prefixes and to recognise commander
// names in bot_name_fits_race.
static const char *sBotRankNames[] =
	{ "Ensign", "Captain", "Commodore", "Admiral", "Grand Admiral", "Supreme Admiral" };
static const int sBotRankCount =
	sizeof(sBotRankNames) / sizeof(sBotRankNames[0]);

// True if some LIVING player already holds aName (case-insensitive). Bots are
// ordinary players, so this also stops a new bot taking an existing bot's name --
// and, since registration uses the same get_by_name, a human can never take one.
static bool
bot_name_taken(const char *aName)
{
	return PLAYER_TABLE->get_by_name(aName) != NULL;
}

// Write into aOut a name based on aBase that no living player holds. If aBase is
// free, use it; otherwise append a roman ordinal ("aBase II", "aBase III", ...).
// Returns false only if even the ordinals are somehow exhausted.
static bool
unique_name_from(const char *aBase, char *aOut, int aOutSize)
{
	if (!bot_name_taken(aBase))
	{
		snprintf(aOut, aOutSize, "%.40s", aBase);
		return true;
	}
	static const char *Roman[] =
		{ "II","III","IV","V","VI","VII","VIII","IX","X","XI","XII","XIII","XIV","XV",
		  "XVI","XVII","XVIII","XIX","XX" };
	int RomanCount = (int)(sizeof(Roman) / sizeof(Roman[0]));
	for (int i=0 ; i<RomanCount ; i++)
	{
		char Buf[64];
		snprintf(Buf, sizeof(Buf), "%.34s %s", aBase, Roman[i]);
		if (!bot_name_taken(Buf))
		{
			snprintf(aOut, aOutSize, "%.40s", Buf);
			return true;
		}
	}
	return false;
}

// Fill aOut with a UNIQUE faction name appropriate to aRace, drawn from that
// race's own pool. Tries every pool entry (random rotation) for a free one, then
// falls back to ordinal-extending a random entry. Returns false if aRace has no
// pool (caller then uses a commander name instead).
bool
CGame::make_bot_faction_name(int aRace, char *aOut, int aOutSize)
{
	int Idx = aRace - CRace::RACE_HUMAN;
	if (Idx < 0 || Idx >= sRaceFactionCount) return false;
	const char **Names = sRaceFaction[Idx].mNames;
	int Count = sRaceFaction[Idx].mCount;
	if (Count <= 0) return false;

	int Start = number(Count) - 1;
	for (int i=0 ; i<Count ; i++)
	{
		const char *Base = Names[(Start + i) % Count];
		if (!bot_name_taken(Base))
		{
			snprintf(aOut, aOutSize, "%.40s", Base);
			return true;
		}
	}
	return unique_name_from(Names[number(Count) - 1], aOut, aOutSize);
}

// True if aName is already an appropriate name for aRace: either a commander name
// (starts with a rank word) or one of aRace's own faction names (optionally
// ordinal-extended). The backfill uses this to leave already-correct bots alone.
bool
CGame::bot_name_fits_race(int aRace, const char *aName)
{
	if (!aName || !*aName) return false;

	for (int i=0 ; i<sBotRankCount ; i++)
	{
		int L = (int)strlen(sBotRankNames[i]);
		if (strncmp(aName, sBotRankNames[i], L) == 0 && aName[L] == ' ') return true;
	}

	int Idx = aRace - CRace::RACE_HUMAN;
	if (Idx < 0 || Idx >= sRaceFactionCount) return false;
	const char **Names = sRaceFaction[Idx].mNames;
	int Count = sRaceFaction[Idx].mCount;
	for (int i=0 ; i<Count ; i++)
	{
		int L = (int)strlen(Names[i]);
		if (strcmp(aName, Names[i]) == 0) return true;          // exact pool name
		if (strncmp(aName, Names[i], L) == 0 && aName[L] == ' ') return true; // "<name> II"
	}
	return false;
}

void
CGame::make_bot_name(int aRace, int aBand, char *aOut, int aOutSize)
{
	if (aBand < 0) aBand = 0;
	if (aBand >= NUM_BOT_BANDS) aBand = NUM_BOT_BANDS - 1;
	if (aRace < CRace::RACE_HUMAN)      aRace = CRace::RACE_HUMAN;
	if (aRace > CRace::RACE_XESPERADOS) aRace = CRace::RACE_XESPERADOS;

	// Two name styles: a FACTION name drawn from aRace's own lore pool (e.g. a
	// Xeloss "Xeloss Theocracy", a Buckaneer "Corsair Confederacy") most of the
	// time, or a single COMMANDER "<Rank> <Name>" so individual captains still
	// appear. Default 80% faction, tunable via the Game/BotFactionNamePct INI key.
	// Both are guaranteed unique among living players.
	int FactionPct =
		ARCHSPACE->configuration().get_integer("Game", "BotFactionNamePct", 80);

	if (number(100) <= FactionPct && make_bot_faction_name(aRace, aOut, aOutSize))
		return;

	// bands 0-3: a random rank scaled to the band; bands 4-5: a fixed signature
	// rank shared by every bot in the band.
	int Rank = (aBand >= 4) ? aBand : number(aBand + 1) - 1;
	const char *Commander = ADMIRAL_NAME_TABLE->get_random_name(aRace);
	if (!Commander || !*Commander) Commander = "Bot";
	if ((int)strlen(Commander) > 30)
	{
		const char *Space = strrchr(Commander, ' ');
		if (Space && *(Space + 1)) Commander = Space + 1;
	}
	char Candidate[41];
	snprintf(Candidate, sizeof(Candidate), "%s %.30s", sBotRankNames[Rank], Commander);
	if (!unique_name_from(Candidate, aOut, aOutSize))
		snprintf(aOut, aOutSize, "%.40s", Candidate);
}

// Scrap a bot's existing ships and rebuild its tier roster from scratch. Deletes
// every fleet (and its commander), any bench commanders, and every ship design,
// then builds the tier's 3 named designs and seeds cull_to starting fleets (one
// on a permanent auto-repeat expedition, the rest defenders). At spawn the bot is
// empty so the scrap is a no-op; for an existing bot this swaps its old ships out
// for the tier's. The bot's planets/tech are left untouched.
void
CGame::build_bot_roster(CPlayer *aPlayer, int aBand)
{
	if (aPlayer == NULL) return;
	if (aBand < 0) aBand = 0;
	if (aBand >= NUM_BOT_BANDS) aBand = NUM_BOT_BANDS - 1;

	CFleetList      *FleetList   = aPlayer->get_fleet_list();
	CAdmiralList    *AdmiralList = aPlayer->get_admiral_list();
	CAdmiralList    *AdmiralPool = aPlayer->get_admiral_pool();
	CShipDesignList *DesignList  = aPlayer->get_ship_design_list();

	// scrap every fleet and its commander (backward scan -- delete shrinks the list)
	for (int i=FleetList->length()-1 ; i>=0 ; i--)
	{
		CFleet *F = (CFleet *)FleetList->get(i);
		if (F == NULL) continue;
		CAdmiral *A = AdmiralList->get_by_id(F->get_admiral_id());
		int FID = F->get_id();
		F->type(QUERY_DELETE);
		STORE_CENTER->store(*F);
		FleetList->remove_fleet(FID);
		if (A != NULL)
		{
			A->set_fleet_number(0);
			A->type(QUERY_DELETE);
			STORE_CENTER->store(*A);
			AdmiralList->remove_admiral(A->get_id());
		}
	}
	// scrap any leftover bench commanders
	for (int i=AdmiralPool->length()-1 ; i>=0 ; i--)
	{
		CAdmiral *A = (CAdmiral *)AdmiralPool->get(i);
		if (A == NULL) continue;
		A->type(QUERY_DELETE);
		STORE_CENTER->store(*A);
		AdmiralPool->remove_admiral(A->get_id());
	}
	// scrap every existing ship design (fleets referencing them are already gone)
	for (int i=DesignList->length()-1 ; i>=0 ; i--)
	{
		CShipDesign *D = (CShipDesign *)DesignList->get(i);
		if (D == NULL) continue;
		int DID = D->get_design_id();
		D->type(QUERY_DELETE);
		STORE_CENTER->store(*D);
		DesignList->remove_ship_design(DID);
	}

	// build the tier's 3 designs (fall back to a best-components design) and seed
	// cull_to fleets: the first on a permanent auto-repeat expedition, rest defend.
	const CBotTierSpec &Spec = bot_tier_spec(aBand);
	CShipDesign *TierDesign[3] = { NULL, NULL, NULL };
	int TierDesignCount = 0;
	for (int v=0 ; v<3 ; v++)
	{
		CShipDesign *D = make_design_at_level(aPlayer, Spec.mHull, Spec.mLevel);
		if (D != NULL) TierDesign[TierDesignCount++] = D;
	}
	if (TierDesignCount == 0)
	{
		CShipDesign *D = make_best_bot_design(aPlayer, Spec.mHull);
		if (D != NULL) TierDesign[TierDesignCount++] = D;
	}
	for (int n=0 ; n<Spec.mCullTo && TierDesignCount>0 ; n++)
	{
		CShipDesign *D = TierDesign[number(TierDesignCount) - 1];
		bot_add_fleet(aPlayer, D, 20, 100, (n == 0));   // first fleet -> expedition
	}
	aPlayer->refresh_power();
}

// Create a bot (NPC) player in tier aBand (0..NUM_BOT_BANDS-1). Builds on
// create_new_player(): grants band-scaled tech, adds planets, builds the tier's
// ship roster (a distinct hull at a fixed tech level, see bot_tier_spec /
// make_design_at_level), and seeds the bot with cull_to starting fleets -- one on
// a permanent auto-repeat expedition, the rest standing by to defend. The regen
// cron (trigger/crontab.bot.cc) then grows and culls it over time. The tier is
// encoded in the portal id (see player.h); nothing extra is persisted.
CPlayer *
CGame::create_bot_player(int aBand)
{
	if (aBand < 0) aBand = 0;
	if (aBand >= NUM_BOT_BANDS) aBand = NUM_BOT_BANDS - 1;

	// next free portal id within this band's reserved range
	int RangeLo = BOT_PORTAL_BASE + aBand * BOT_BAND_STRIDE;
	int RangeHi = RangeLo + BOT_BAND_STRIDE;
	int MaxID = RangeLo;
	for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
	{
		CPlayer *P = (CPlayer *)PLAYER_TABLE->get(i);
		if (P == NULL) continue;
		int PID = P->get_portal_id();
		if (PID >= RangeLo && PID < RangeHi && PID > MaxID) MaxID = PID;
	}
	int PortalID = MaxID + 1;
	if (PortalID >= RangeHi) return NULL;   // band space exhausted (1M slots)

	// Bot name: a band-scaled rank prefix + a commander-style name for a random
	// race (see make_bot_name). Built BEFORE create_new_player, which reuses the
	// name generator's static buffer when it makes the bot's admirals.
	int Race = number(10);                       // 1..10 (valid race id)
	char Name[41];
	make_bot_name(Race, aBand, Name, sizeof(Name));

	CPlayer *Player = create_new_player(PortalID, Name, Race);
	if (Player == NULL) return NULL;

	CPlanetList    *PlanetList     = Player->get_planet_list();
	CFleetList     *FleetList      = Player->get_fleet_list();

	// --- tech: grant every tech up to the band's level -----------------------
	// Discovering in ascending level order satisfies prerequisites and unlocks the
	// matching ship components, keeping a bot's tech thematically in step with its
	// tier (the tier ship designs pick components by level directly, so this is for
	// flavour/power, not a hard requirement).
	//   bands 0-3 -> level 3/5/7/9; band 4 (Grand Admiral) -> all level-9 techs;
	//   band 5 (Supreme Admiral) -> every tech (the tree tops out at level 12).
	int TechLevel;
	if (aBand <= 3)      TechLevel = 3 + aBand * 2;
	else if (aBand == 4) TechLevel = 9;
	else                 TechLevel = 99;
	for (int L=1 ; L<=TechLevel ; L++)
	{
		for (int i=0 ; i<TECH_TABLE->length() ; i++)
		{
			CTech *Tech = (CTech *)TECH_TABLE->get(i);
			if (Tech == NULL) continue;
			if (Tech->get_level() == L) Player->discover_tech(Tech->get_id());
		}
	}

	// --- planets: scale with band (mirrors the NPC-seed planet-claim block) ---
	int TargetPlanets = 4 + aBand * 4;
	int PlanetGuard = 0;
	while (PlanetList->length() < TargetPlanets && PlanetGuard++ < TargetPlanets * 4)
	{
		int ClusterID = Player->find_new_planet(true);
		if (ClusterID == -1)
		{
			for (int i=0 ; i<UNIVERSE->length() ; i++)
			{
				CCluster *Cluster = (CCluster *)UNIVERSE->get(i);
				if (Cluster->get_id() == EMPIRE_CLUSTER_ID) continue;
				if (Cluster->get_player_count()*20 < Cluster->get_planet_count()) continue;
				if (PlanetList->count_planet_from_cluster(Cluster->get_id()) > 20) continue;
				ClusterID = Cluster->get_id();
				break;
			}
		}
		if (ClusterID == -1)
		{
			CCluster *Cluster = UNIVERSE->new_cluster();
			ClusterID = Cluster->get_id();
			CMagistrate *Magistrate = new CMagistrate();
			EMPIRE->get_magistrate_list()->add_magistrate(Magistrate);
			Magistrate->initialize(Cluster->get_id());
		}

		CCluster *Cluster = UNIVERSE->get_by_id(ClusterID);
		if (Cluster == NULL) break;

		CPlanet *Planet = new CPlanet();
		Planet->initialize();
		Planet->set_cluster(Cluster);
		Planet->set_name(Cluster->get_new_planet_name());
		Player->add_planet(Planet);
		PLANET_TABLE->add_planet(Planet);
		Planet->start_terraforming();
		Player->new_planet_news(Planet);

		Planet->type(QUERY_INSERT);
		STORE_CENTER->store(*Planet);
	}

	// --- tier ship roster: the tier's named designs + cull_to starting fleets ----
	// (one on a permanent auto-repeat expedition, the rest defenders). The regen
	// cron then grows the bot +1 fleet/hour to max_fleets and culls back to cull_to.
	build_bot_roster(Player, aBand);

	Player->set_last_login(time(0));
	Player->type(QUERY_UPDATE);
	STORE_CENTER->store(*Player);

	SLOG("SYSTEM : bot %s created (band %d, portal %d, power %d, planets %d, fleets %d)",
			Player->get_nick(), aBand, PortalID, Player->get_power(),
			PlanetList->length(), FleetList->length());

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
				if (Project->get_type() == CProject::TYPE_BM)
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

		for (int i=0 ; i<ProjectList->length() ; i++)
		{
			CPurchasedProject *
				Project = (CPurchasedProject *)ProjectList->get(i);
			if (Project->get_type() == CProject::TYPE_ENDING)
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

/**
*/
bool
CGame::tech_encyclopedia()
{
	CEncyclopediaTechIndexGame Index;
	CEncyclopediaTechPageGame PageGame;


	if (!Index.read("tech/index.html"))
	{
		SLOG("Could not found tech encyclopedia index form file");
		return false;
	}

	if (!PageGame.read("tech/page_game.html"))
	{
		SLOG("Could not found tech encyclopedia page form file");
		return false;
	}

    // load and write index
	for (int i=0 ; i<CTech::TYPE_MAX ; i++)
	{
		Index.set(i);

		if (!Index.write())
		{
			SLOG("Could not write tech encyclopedia index page");
			return false;
		}
	}

    // load and write tech pages
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

/**
*/
bool
CGame::race_encyclopedia()
{
	CEncyclopediaRaceIndexGame
		Index;
	CEncyclopediaRacePageGame
		PageGame;

	if (!PageGame.read("race/page_game.html"))
	{
		SLOG("Could not found race encyclopedia page form file");
		return false;
	}
 	if (!Index.read("race/index.html"))
	{
		SLOG("Could not findd race encyclopedia index form file");
		return false;
	}

	Index.set(mRaceTable);

	if (!Index.write())
	{
		SLOG("Could not write race encyclopedia index page");
		return false;
	} 

    // load and write race pages
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

/**
*/
bool
CGame::project_encyclopedia()
{
	CEncyclopediaProjectIndexGame Index;
	CEncyclopediaProjectPageGame PageGame;
              
	if (!Index.read("project/index.html"))
	{
		SLOG("Could not found project encyclopedia index form file");
		return false;
	}              

	if (!PageGame.read("project/page_game.html"))
	{
		SLOG("Could not find project encyclopedia page form file");
		return false;
	}

    // Load and write the index           
	for (int i=CProject::TYPE_BEGIN ; i<CProject::TYPE_MAX ; i++)
	{
		Index.set(i);

		if (!Index.write())
		{
			SLOG("Could not write project encyclopedia index page");
			return false;
		}
	}        
    
    // Load and write the pages
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

/**
*/
bool
CGame::component_encyclopedia()
{
	CEncyclopediaComponentIndexGame
		index;

	CEncyclopediaComponentPageGame
		ArmorPageGame,
		ComputerPageGame,
		ShieldPageGame,
		EnginePageGame,
		DevicePageGame,
		WeaponPageGame;

	if (!index.read("component/index.html"))
	{
		SLOG("Could not found component encyclopedia index form file");
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

    // Load and write index page
	for (int i=0 ; i<CComponent::CC_MAX ; i++)
	{
		index.set(i);

		if (!index.write())
		{
			SLOG("Could not write component encyclopedia index page");
			return false;
		}
	}

    // Load and write component pages
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

/**
*/
bool
CGame::ship_encyclopedia()
{
	CEncyclopediaShipIndexGame
		index;
	CEncyclopediaShipPageGame
		pageGame;

	if (!index.read("ship/index.html"))
	{
		SLOG("Could not found ship encyclopedia index form file");
		return false;
	}

	if (!pageGame.read("ship/page_game.html"))
	{
		SLOG("Could not found ship encyclopedia page form file");
		return false;
	}

    // load and write index
	index.set(mShipSizeTable);

	if (!index.write())
	{
		SLOG("Could not write ship encyclopedia index page");
		return false;
	}

    // load and write ship pages
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

/**
*/
bool
CGame::spy_encyclopedia()
{
	CEncyclopediaSpyIndexGame
		IndexGame;
	CEncyclopediaSpyPageGame
		PageGame;

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

    // Load and write index
	IndexGame.set(mSpyTable);
	if (!IndexGame.write())
	{
		SLOG("Could not write spy encyclopedia index page");
		return false;
	}
    
    // Load and write spy pages
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
