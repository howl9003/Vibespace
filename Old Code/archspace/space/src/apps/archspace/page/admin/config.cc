#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include <stdlib.h>

bool
CPageConfig::handler(CPlayer *aPlayer)
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

	CString
		SEDTest;
	SEDTest.format("%s s/dummy/dummy/g /dev/null", (char *)CAdminTool::mSEDPath);

	if (system((char *)SEDTest) != 0)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("SED invoking test failed. Please ask Archspace Development Team."));
		return output("admin/admin_error.html");
	}

	int
		OldSecondPerTurn = CGame::mSecondPerTurn;

	QUERY("SECS_PER_TURN", SecsPerTurnString);
	if (SecsPerTurnString != NULL)
	{
		int
			SecsPerTurn = as_atoi(SecsPerTurnString);
		if (SecsPerTurn > 0)
		{
			int
				OldSecsPerTurn = CGame::mSecondPerTurn;
			CGame::mSecondPerTurn = SecsPerTurn;
			CString
				SED;
			SED	.clear();
			SED.format("%s s/\"SecondPerTurn[[:blank:]]*=[[:blank:]]*%d\"/\"SecondPerTurn = %d\"/ /space/space/game/etc/config > /space/space/game/bin/temp", (char *)CAdminTool::mSEDPath, OldSecsPerTurn, CGame::mSecondPerTurn);
			system((char *)SED);
			system("cp --reply=yes /space/space/game/bin/temp /space/space/game/etc/config");
			system("rm /space/space/game/bin/temp");
		}
	}

	QUERY("MAX_PLAYER", MaxPlayerString);
	if (MaxPlayerString != NULL)
	{
		int
			MaxPlayer = as_atoi(MaxPlayerString);
		if (MaxPlayer > 0)
		{
			int
				OldMaxPlayer = CGame::mMaxUser;
			CGame::mMaxUser = MaxPlayer;
			CString
				SED;
			SED	.clear();
			SED.format("%s s/\"MaxUser[[:blank:]]*=[[:blank:]]*%d\"/\"MaxUser = %d\"/ /space/space/game/etc/config > /space/space/game/bin/temp", (char *)CAdminTool::mSEDPath, OldMaxPlayer, CGame::mMaxUser);
			system((char *)SED);
			system("cp --reply=yes /space/space/game/bin/temp /space/space/game/etc/config");
			system("rm /space/space/game/bin/temp");
		}
	}

	QUERY("TURNS_FOR_TRAIN", TurnsForTrainString);
	if (TurnsForTrainString != NULL)
	{
		int
			TurnsForTrain = as_atoi(TurnsForTrainString);
		if (TurnsForTrain > 0)
		{
			int
				OldTurnsForTrain = CMission::mTrainMissionTime / OldSecondPerTurn;
			CMission::mTrainMissionTime = TurnsForTrain * CGame::mSecondPerTurn;
			CString
				SED;
			SED	.clear();
			SED.format("%s s/\"TrainMissionTime[[:blank:]]*=[[:blank:]]*%d\"/\"TrainMissionTime = %d\"/ /space/space/game/etc/config > /space/space/game/bin/temp", (char *)CAdminTool::mSEDPath, OldTurnsForTrain, CMission::mTrainMissionTime / CGame::mSecondPerTurn);
			system((char *)SED);
			system("cp --reply=yes /space/space/game/bin/temp /space/space/game/etc/config");
			system("rm /space/space/game/bin/temp");
		}
	}

	QUERY("TURNS_FOR_PATROL", TurnsForPatrolString);
	if (TurnsForPatrolString != NULL)
	{
		int
			TurnsForPatrol = as_atoi(TurnsForPatrolString);
		if (TurnsForPatrol > 0)
		{
			int
				OldTurnsForPatrol = CMission::mPatrolMissionTime / OldSecondPerTurn;
			CMission::mPatrolMissionTime = TurnsForPatrol * CGame::mSecondPerTurn;
			CString
				SED;
			SED	.clear();
			SED.format("%s s/\"PatrolMissionTime[[:blank:]]*=[[:blank:]]*%d\"/\"PatrolMissionTime = %d\"/ /space/space/game/etc/config > /space/space/game/bin/temp", (char *)CAdminTool::mSEDPath, OldTurnsForPatrol, CMission::mPatrolMissionTime / CGame::mSecondPerTurn);
			system((char *)SED);
			system("cp --reply=yes /space/space/game/bin/temp /space/space/game/etc/config");
			system("rm /space/space/game/bin/temp");
		}
	}

	QUERY("TURNS_FOR_DISPATCH", TurnsForDispatchString);
	if (TurnsForDispatchString != NULL)
	{
		int
			TurnsForDispatch = as_atoi(TurnsForDispatchString);
		if (TurnsForDispatch > 0)
		{
			int
				OldTurnsForDispatch = CMission::mDispatchToAllyMissionTime / OldSecondPerTurn;
			CMission::mDispatchToAllyMissionTime = TurnsForDispatch * CGame::mSecondPerTurn;
			CString
				SED;
			SED	.clear();
			SED.format("%s s/\"DispatchToAllyMissionTime[[:blank:]]*=[[:blank:]]*%d\"/\"DispatchToAllyMissionTime = %d\"/ /space/space/game/etc/config > /space/space/game/bin/temp", (char *)CAdminTool::mSEDPath, OldTurnsForDispatch, CMission::mDispatchToAllyMissionTime / CGame::mSecondPerTurn);
			system((char *)SED);
			system("cp --reply=yes /space/space/game/bin/temp /space/space/game/etc/config");
			system("rm /space/space/game/bin/temp");
		}
	}

	QUERY("TURNS_FOR_EXPEDITION", TurnsForExpeditionString);
	if (TurnsForExpeditionString != NULL)
	{
		int
			TurnsForExpedition = as_atoi(TurnsForExpeditionString);
		if (TurnsForExpedition > 0)
		{
			int
				OldTurnsForExpedition = CMission::mExpeditionMissionTime / OldSecondPerTurn;
			CMission::mExpeditionMissionTime = TurnsForExpedition * CGame::mSecondPerTurn;
			CString
				SED;
			SED	.clear();
			SED.format("%s s/\"ExpeditionMissionTime[[:blank:]]*=[[:blank:]]*%d\"/\"ExpeditionMissionTime = %d\"/ /space/space/game/etc/config > /space/space/game/bin/temp", (char *)CAdminTool::mSEDPath, OldTurnsForExpedition, CMission::mExpeditionMissionTime / CGame::mSecondPerTurn);
			system((char *)SED);
			system("cp --reply=yes /space/space/game/bin/temp /space/space/game/etc/config");
			system("rm /space/space/game/bin/temp");
		}
	}

	QUERY("TURNS_FOR_RETURNING", TurnsForReturningString);
	if (TurnsForReturningString != NULL)
	{
		int
			TurnsForReturning = as_atoi(TurnsForReturningString);
		if (TurnsForReturning > 0)
		{
			int
				OldTurnsForReturning = CMission::mReturningWithPlanetMissionTime / OldSecondPerTurn;
			CMission::mReturningWithPlanetMissionTime = TurnsForReturning * CGame::mSecondPerTurn;
			CString
				SED;
			SED	.clear();
			SED.format("%s s/\"ReturningWithPlanetMissionTime[[:blank:]]*=[[:blank:]]*%d\"/\"ReturningWithPlanetMissionTime = %d\"/ /space/space/game/etc/config > /space/space/game/bin/temp", (char *)CAdminTool::mSEDPath, OldTurnsForReturning, CMission::mReturningWithPlanetMissionTime / CGame::mSecondPerTurn);
			system((char *)SED);
			system("cp --reply=yes /space/space/game/bin/temp /space/space/game/etc/config");
			system("rm /space/space/game/bin/temp");
		}
	}

	QUERY("BLACK_MARKET_ITEM_REGEN", BlackMarketItemRegenString);
	if (BlackMarketItemRegenString != NULL)
	{
		int
			BlackMarketItemRegen = as_atoi(BlackMarketItemRegenString);
		if (BlackMarketItemRegen > 0)
		{
			int
				OldBlackMarketItemRegen = CBlackMarket::mBlackMarketItemRegen;
			CBlackMarket::mBlackMarketItemRegen = BlackMarketItemRegen;
			CString
				SED;
			SED	.clear();
			SED.format("%s s/\"BlackMarketItemRegen[[:blank:]]*=[[:blank:]]*%d\"/\"BlackMarketItemRegen = %d\"/ /space/space/game/etc/config > /space/space/game/bin/temp", (char *)CAdminTool::mSEDPath, OldBlackMarketItemRegen, CBlackMarket::mBlackMarketItemRegen);
			system((char *)SED);
			system("cp --reply=yes /space/space/game/bin/temp /space/space/game/etc/config");
			system("rm /space/space/game/bin/temp");
		}
	}

	QUERY("BID_EXPIRE_TIME", BidExpireTimeString);
	if (BidExpireTimeString != NULL)
	{
		int
			BidExpireTime = as_atoi(BidExpireTimeString);
		if (BidExpireTime > 0)
		{
			int
				OldBidExpireTime = CBlackMarket::mBidExpireTime;
			CBlackMarket::mBidExpireTime = BidExpireTime;
			CString
				SED;
			SED	.clear();
			SED.format("%s s/\"BidExpireTime[[:blank:]]*=[[:blank:]]*%d\"/\"BidExpireTime = %d\"/ /space/space/game/etc/config > /space/space/game/bin/temp", (char *)CAdminTool::mSEDPath, OldBidExpireTime, CBlackMarket::mBidExpireTime);
			system((char *)SED);
			system("cp --reply=yes /space/space/game/bin/temp /space/space/game/etc/config");
			system("rm /space/space/game/bin/temp");
		}
	}

	ITEM("STRING_GAME_CONFIGURATION", GETTEXT("Game Configuration"));

	ITEM("STRING_SECS_PER_TURN", GETTEXT("Secs Per Turn"));
	ITEM("SECS_PER_TURN", CGame::mSecondPerTurn);

	ITEM("STRING_MAX__NUMBER_OF_PLAYERS", GETTEXT("Max. Number Of Players"));
	ITEM("MAX_PLAYER", CGame::mMaxUser);

	ITEM("STRING_TURNS_FOR_TRAIN", GETTEXT("Turns For Train"));
	ITEM("TURNS_FOR_TRAIN", CMission::mTrainMissionTime/CGame::mSecondPerTurn);

	ITEM("STRING_TURNS_FOR_PATROL", GETTEXT("Turns For Patrol"));
	ITEM("TURNS_FOR_PATROL", CMission::mPatrolMissionTime/CGame::mSecondPerTurn);

	ITEM("STRING_TURNS_FOR_DISPATCH", GETTEXT("Turns For Dispatch"));
	ITEM("TURNS_FOR_DISPATCH", CMission::mDispatchToAllyMissionTime/CGame::mSecondPerTurn);

	ITEM("STRING_TURNS_FOR_EXPEDITION", GETTEXT("Turns For Expedition"));
	ITEM("TURNS_FOR_EXPEDITION", CMission::mExpeditionMissionTime/CGame::mSecondPerTurn);

	ITEM("STRING_TURNS_FOR_RETURNING", GETTEXT("Turns For Returning"));
	ITEM("TURNS_FOR_RETURNING",
			CMission::mReturningWithPlanetMissionTime/CGame::mSecondPerTurn);

	ITEM("STRING_TIME_FOR_BLACK_MARKET_ITEM_REGEN__IN_SECS_",
			GETTEXT("Time For Black Market Item Regen.(in secs)"));
	ITEM("BLACK_MARKET_ITEM_REGEN", CBlackMarket::mBlackMarketItemRegen);

	ITEM("STRING_BLACK_MARKET_BID_EXPIRE_TIME_IN_MINS_",
			GETTEXT("Black Market Bid Expire Time"));
	ITEM("BID_EXPIRE_TIME", CBlackMarket::mBidExpireTime);

//	system_log("end page handler %s", get_name());

	return output("admin/config.html");
}
