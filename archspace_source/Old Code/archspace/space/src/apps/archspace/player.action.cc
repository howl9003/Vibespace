#include "common.h"
#include "util.h"
#include "action.h"
#include <cstdlib>
#include "define.h"
#include "archspace.h"
#include "game.h"

TZone gPlayerActionZone =
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CPlayerAction),
	0,
	0,
	NULL,
	"Zone CPlayerAction"
};

int CPlayerAction::mMaxID = 0;

CPlayerAction::CPlayerAction():CAction()
{
	mMaxID++;

	mOwner = NULL;
	mID = (mMaxID) ? mMaxID:1;
}

void
CPlayerAction::load_from_database(MYSQL_ROW aRow)
{
	enum
	{
		FIELD_ID = 0,
		FIELD_START_TIME,
		FIELD_TYPE,
		FIELD_OWNER,
		FIELD_ARGUMENT
	};

	mID = atoi(aRow[FIELD_ID]);
	mStartTime = (time_t)atoi(aRow[FIELD_START_TIME]);		
	mOwner = PLAYER_TABLE->get_by_game_id(atoi(aRow[FIELD_OWNER]));
	mArgument = atoi(aRow[FIELD_ARGUMENT]);

	if (mID > mMaxID) mMaxID = mID;
}

CPlayerAction::~CPlayerAction()
{
}

CString&
CPlayerAction::query()
{
	static CString Query;

	Query.clear();

	switch(type())
	{
		case QUERY_INSERT:
			Query = "INSERT INTO player_action "
					"(id, action, start_time, owner, argument) "
					"VALUES ";
			Query.format("( %d, %d, %d, %d, %d )", 
					mID, get_type(), mStartTime, 
					mOwner->get_game_id(), 
					mArgument);
			break;
		case QUERY_UPDATE:
			break;
		case QUERY_DELETE:
			Query.format("DELETE FROM player_action WHERE id = %d", 
																mID);
			break;
	}

	mStoreFlag.clear();

	return Query;
}

time_t
CPlayerActionCouncilDonation::get_period()
{
	return (PERIOD_PLAYER_COUNCIL_DONATION * CGame::mSecondPerTurn);
}

void
CPlayerActionCouncilDonation::feedback()
{
	SLOG("expire remove_council_donation_timer %s", mOwner->get_nick());
}

time_t
CPlayerActionBreakAlly::get_period()
{
	return (PERIOD_PLAYER_BREAK_ALLY * CGame::mSecondPerTurn);
}

void 
CPlayerActionBreakAlly::feedback()
{
	SLOG("expire action_player_break_ally %s", mOwner->get_nick());
}

time_t
CPlayerActionBreakPact::get_period()
{
	return (PERIOD_PLAYER_BREAK_PACT * CGame::mSecondPerTurn);
}

void 
CPlayerActionBreakPact::feedback()
{
	SLOG("expire action_player_break_pact %s", mOwner->get_nick());
}

time_t
CPlayerActionSiegeBlockadeRestriction::get_period()
{
	return CGame::mSiegeBlockadeRestrictionDuration;
}

void 
CPlayerActionSiegeBlockadeRestriction::feedback()
{
	SLOG("expire action_player_siege_blockade_restriction %s", mOwner->get_nick());
}

time_t
CPlayerActionSiegeBlockadeProtection::get_period()
{
	return CGame::mSiegeBlockadeProtectionDuration;
}

void 
CPlayerActionSiegeBlockadeProtection::feedback()
{
	SLOG("expire action_player_siege_blockade_protection %s", mOwner->get_nick());
}

time_t
CPlayerActionEmpireInvasionHistory::get_period()
{
	return CEmpire::mEmpireInvasionLimitDuration;
}

void
CPlayerActionEmpireInvasionHistory::feedback()
{
	SLOG("expire action_player_empire_invasion_history %s", mOwner->get_nick());
}

