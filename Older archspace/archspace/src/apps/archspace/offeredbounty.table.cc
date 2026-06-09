#include "bounty.h"
#include "archspace.h"
#include "player.h"

COfferedBountyTable::COfferedBountyTable()
{
	refresh_bounties();
}

void
COfferedBountyTable::new_bounty(CPlayer *aTargetPlayer)
{
	std::map<int, COfferedBounty*>::iterator aIter = mBounties.find(aTargetPlayer->get_game_id());
	if (aIter != mBounties.end()) return;
	COfferedBounty *aBounty = new COfferedBounty(aTargetPlayer);
	mBounties[aTargetPlayer->get_game_id()] = aBounty;
} 

void
COfferedBountyTable::expire_bounties()
{
	std::map<int, COfferedBounty*>::iterator aIter;
	for (aIter = mBounties.begin(); aIter != mBounties.end(); aIter++) 
	{
		if ((aIter->second)->get_player()->get_degree_of_empire_relation() < CPlayer::DM_LIGHT_BOUNTY_OFFERED) {
			mBounties.erase(aIter);
			delete aIter->second;
		}
	}
}

void
COfferedBountyTable::refresh_bounties()
{
	//if (!CGame::mUpdateTurn) return;
	expire_bounties();
	SLOG("Searching for bounties");
	if (EMPIRE->is_dead() == false)
	{
		for (int i=PLAYER_TABLE->length() -1; i>=0 ; i--)
		{
			CPlayer *
				Player = (CPlayer *)PLAYER_TABLE->get(i);
			if (Player->get_game_id() == EMPIRE_GAME_ID) continue;
			if (Player->is_dead()) continue;
			if (Player->is_protected()) continue;
			if (Player->get_protected_mode() != CPlayer::PROTECTED_NONE) continue;
			if (Player->get_degree_of_empire_relation() < CPlayer::DM_LIGHT_BOUNTY_OFFERED) continue;
			int EmpireRelation = Player->get_empire_relation();
			EmpireRelation = abs(EmpireRelation);
			if (number(EmpireRelation) > 50) new_bounty(Player);
			new_bounty(Player);
		}
	}
}

void
COfferedBountyTable::remove_bounty_on_player(int aID)
{
	std::map<int, COfferedBounty*>::iterator aIter = mBounties.find(aID);
	if (aIter == mBounties.end()) return;
	mBounties.erase(aIter);
	delete aIter->second;
}

COfferedBounty*
COfferedBountyTable::get_by_id(int aID)
{
	std::map<int, COfferedBounty*>::iterator aIter = mBounties.find(aID);
	if (aIter == mBounties.end()) return NULL;
	return aIter->second;
}

std::vector<COfferedBounty*>*
COfferedBountyTable::get_available_bounties(CPlayer *aPlayer)
{
	std::vector<COfferedBounty*> *aResult = new std::vector<COfferedBounty*>;
	int hunterPowerMin, hunterPowerMax, currentPower;
	hunterPowerMin = (int)(aPlayer->get_power()/2);
	hunterPowerMax = (int)(aPlayer->get_power()*2);
	std::map<int, COfferedBounty*>::iterator aIter;
	for (aIter = mBounties.begin(); aIter != mBounties.end(); aIter++) 
	{
		currentPower = (aIter->second)->get_player()->get_power();
		if ((currentPower > hunterPowerMin) && (currentPower < hunterPowerMax) && (aPlayer->get_bounty_by_player_id((aIter->second)->get_player()->get_game_id()) == NULL) && ((aIter->second)->get_player()->get_game_id() != aPlayer->get_game_id())) {
			aResult->push_back(aIter->second);
		}
	}
	SLOG("Available bounties:%d",mBounties.size());
	return aResult;
}
