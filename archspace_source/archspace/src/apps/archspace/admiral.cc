#include "common.h"
#include "util.h"
#include "admiral.h"
#include "player.h"
#include "archspace.h"
#include "game.h"
#include <cstdlib>


TZone gAdmiralZone =
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CAdmiral),
	0,
	0,
	NULL,
	"Zone CAdmiral"
};

int CAdmiral::mMaxID = 0;

int
CAdmiral::mExpLevelTable[MAX_LEVEL+1] = {
	0,			// level 0
	1000,		// level 1
	3000,
	6000,
	10000,
	15000,		// level 5
	21000,
	28000,
	36000,
	45000,
	55000,		// level 10
	66000,
	78000,
	91000,
	105000,
	120000,		// level 15
	136000,
	153000,
	171000,
	190000,
	210000		// level 20
};

int
CAdmiral::mPlusFleetCommanding[MAX_LEVEL+1] =
{
	0,	// level 0
	0,	// level 1
	3,
	3,
	3,
	2,	// level 5
	2,
	2,
	2,
	2,
	1,	// level 10
	1,
	1,
	1,
	1,
	1,	// level 15
	1,
	1,
	1,
	1,
	0	// level 20
};

int
CAdmiral::mArmadaCommandingData[CAdmiral::AC_MAX][NUMBER_OF_ADMIRAL_SKILL_LEVEL] =
{
	{-2, -2, -2, -1, -1, 0, 0, 0, 1, 1, 2, 2, 2, 3, 3, 4, 4, 4, 5, 5, 6, 6, 6, 7, 7, 8},
	{-2, -2, -1, -1, -1, 0, 0, 0, 0, 1, 1, 1, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 5, 5, 5, 6},
	{-2, -1, -1, -1, -1, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 2, 2, 2, 2, 2, 3, 3, 3, 3, 3, 4},
	{-1, -1, -1, -1, -1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2}
};

char *CAdmiral::mStartingCircumstanceName[] =
{
	"Supercommander",
	"Excellent",
	"Very good",
	"Good",
	"Average",
	"Poor",
	"Bad",
	"Very Bad",
	"Cannon Fodder"
};

char *CAdmiral::mSpecialAbilityName[] =
{
	"Engineering Specialist",
	"Shield System Specialist",
	"Missile Specialist",
	"Ballistic Expert",
	"Energy System Specialist",
	"Veteran Fighter Pilot"
};

char *CAdmiral::mRacialAbilityName[] =
{
	"Irrational Tactics",
	"Intuition",
	"Lone Wolf",
	"DNA Poison Replicater",
	"Breeder Male",
	"Clonal Double",
	"Xenophobic Fanatic",
	"Mental Giant",
	"Artifact Crystal",
	"Psychic Progenitor",
	"Artifact Cooling Engine",
	"Lying Dormant",
	"Missile Craters",
	"Meteor Drones",
	"Cyber Scan Unit",
	"Jury Rigger",
	"Pattern Broadcaster",
	"Famous Privateer",
	"Commerce King",
	"Retreat Shield",
	"Genetic Throwback",
	"Rigid Thinking",
	"Scavenger",
	"Blitzkreig",
	// --- CVS-merge: names for the 9 appended racial abilities (same order) ---
	"Trajectory Augmentation",
	"Management Protocol",
	"Tactical Genius",
	"Shield Disrupter",
	"Defensive Matrix",
	"Consciousness Crystal",
	"Crusader",
	"Impingement Neutralization Field",
	"Armada Synergy Specialist"
};

// QoL tooltips: player-facing STAT lines for the commander abilities, in the
// SAME order as mSpecialAbilityName[] / mRacialAbilityName[] above (and the
// SA_*/RA_* enums). Kept lockstep with those arrays -- a short array would read
// out of bounds. These are mechanical stats (not prose) and carry literal <BR>
// separators, so get_ability_html() emits them RAW into data-tip -- they must
// contain no double-quote character (they don't). "/lvl" = per commander level.
char *CAdmiral::mSpecialAbilityDescription[] =
{
	"Armor DR +2%/lvl <BR> Repair Speed +2%/lvl <BR> Impenetrable Armor +2/lvl <BR> In-battle repair 1-3%/10t",
	"Shield Strength +10 +2%/lvl <BR> Recharge +10 +2%/lvl <BR> Impenetrable Shield +10 +2/lvl <BR> Solidity +2..+5",
	"Missile AR +2%/lvl <BR> Missile Damage +2%/lvl <BR> Missile Cooling -2%/lvl",
	"Projectile AR +2%/lvl <BR> Projectile Damage +2%/lvl <BR> Projectile Cooling -2%/lvl",
	"Beam AR +2%/lvl <BR> Beam Damage +2%/lvl <BR> Beam Cooling -2%/lvl",
	"Veteran combat pilot"
};

char *CAdmiral::mRacialAbilityDescription[] =
{
	"DR +10..+30% (by lvl) <BR> Armor DR +1%/lvl <BR> Maneuver -1..-2",
	"Critical Hit +5 +0.5/lvl <BR> Detection +1..+6 <BR> Sees weak cloaking (lvl 5+)",
	"Berserk +5 <BR> Mobility +5 +1%/lvl <BR> Stealth +1%/lvl <BR> Maneuver +2..+7 <BR> Reduced fleet morale loss",
	"Projectile Damage +1%/lvl <BR> Missile Damage +1%/lvl",
	"Repair Speed +20 +1%/lvl <BR> Fleet Commanding +1 @ lvl 2/6/10/13/16/19 <BR> Maneuver +1..+2 <BR> Detection +1..+2",
	"Survives commander death 50..90% (by lvl) <BR> Efficiency -5..-10% (early lvls)",
	"Berserk +5 <BR> Morale -5 <BR> Mobility +5 +0.5%/lvl <BR> PSI Defense -5..-20% <BR> Attack +1..+2 (high lvl)",
	"PSI Attack +1%/lvl <BR> Detection +3..+12 <BR> Sees cloaking (lvl 10+) <BR> Morale -5 <BR> Berserk -5",
	"Beam Damage +2%/lvl <BR> Shield Recharge +2%/lvl <BR> Speed +2%/lvl",
	"PSI Attack +20 +4%/lvl <BR> Morale +10 <BR> Berserk +5 <BR> Sees all cloaking <BR> Efficiency -5..-10%",
	"Cooling -2.5%/lvl <BR> Detection -1..-8",
	"Stealth +10 +3%/lvl <BR> Weak cloaking",
	"Cooling -2%/lvl <BR> Armor DR -1.5%/lvl",
	"Generic Defense +1%/lvl <BR> Maneuver -1..-4",
	"Attack Rating +0.5%/lvl <BR> Detection +1..+9 <BR> Sees weak cloaking",
	"In-battle repair +0.25%/lvl <BR> Fleet Commanding -1..-3",
	"Missile DR +20 +2%/lvl <BR> Misinterpret +10 +4%/lvl <BR> Stealth -10 -2%/lvl",
	"Speed +0.5%/lvl <BR> Stealth +10 +1%/lvl <BR> Weak cloaking (lvl 7+) <BR> Privateer steal bonus",
	"HP +1%/lvl <BR> Speed +1%/lvl <BR> Mobility +1%/lvl <BR> Commerce +1..+3/turn",
	"Shield Strength +5%/lvl <BR> Impenetrable Shield +5/lvl <BR> Solidity +1..+3 <BR> Efficiency -2%/lvl <BR> Morale -10 <BR> Berserk -5",
	"PSI Attack +5%/lvl <BR> Attack +1..+5 <BR> Sees cloaking (lvl 10+) <BR> Morale +5 <BR> Defense -1",
	"PSI Defense +10 +2%/lvl <BR> DR +1%/lvl <BR> Critical Hit -0.25/lvl <BR> Morale -10 <BR> Berserk -5",
	"Repair Speed +10 +2%/lvl <BR> Armor DR +1%/lvl <BR> In-battle repair 1-3%/10t",
	"Damage +5 +0.5%/lvl <BR> Critical Hit +0.25/lvl <BR> Maneuver +2..+7 <BR> Defense -1",
	"Projectile AR +1.5%/lvl <BR> Projectile Damage +1%/lvl <BR> Efficiency +10 +1/lvl",
	"Speed +10 +2%/lvl <BR> PSI Defense +2%/lvl <BR> Attack Rating +1%/lvl <BR> Efficiency +1/lvl <BR> Fleet Commanding +1 @ lvl 2/7/12/17 <BR> Morale -10",
	"Armada command effect +50% <BR> Generic Defense +0.5%/lvl <BR> Impenetrable Armor +1.5/lvl <BR> Morale -15 <BR> Berserk -5",
	"Attack Rating +1.5%/lvl <BR> 2nd-chance shield distortion",
	"Generic Defense +1.25%/lvl <BR> Morale -10 <BR> Berserk -10",
	"Beam Damage +1.25%/lvl <BR> PSI Attack +2%/lvl <BR> Attack Rating +0.75%/lvl <BR> PSI Damage +0.75%/lvl",
	"Damage +2.5%/lvl <BR> Detection +0.5/lvl <BR> Sees all cloaking <BR> Cooling +1.5%/lvl <BR> Berserk +10 <BR> Morale -20",
	"Generic Defense +1.5%/lvl <BR> Impenetrable Armor +1.5/lvl <BR> Shield Integrity +1.5/lvl <BR> Impenetrable Shield +1.5/lvl <BR> Morale -30 <BR> Berserk -30",
	"Fleet Commanding +1/lvl <BR> Efficiency +2/lvl <BR> Morale -40"
};

int
CAdmiral::mPossibleRacialSkill[MAX_RACE][MAX_RACIAL_SKILL] =
{
	// --- CVS-merge: reworked per-race commander-ability assignments + row 11
	// (Trabotulin). Every ability referenced exists in the merged RA_ enum. ---
	{	// 1 Human
		RA_IRRATIONAL_TACTICS,
		RA_INTUITION,
		RA_LONE_WOLF
	},
	{	// 2 Targoid
		RA_DNA_POISON_REPLICATER,
		RA_BREEDER_MALE,
		RA_CLONAL_DOUBLE
	},
	{	// 3 Buckaneer
		RA_SHIELD_DISRUPTER,
		RA_FAMOUS_PRIVATEER,
		RA_COMMERCE_KING
	},
	{	// 4 Tecanoid
		RA_CYBER_SCAN_UNIT,
		RA_TRAJECTORY_AUGMENTATION,
		RA_PATTERN_BROADCASTER
	},
	{	// 5 Evintos
		RA_CYBER_SCAN_UNIT,
		RA_RIGID_THINKING,
		RA_MANAGEMENT_PROTOCOL
	},
	{	// 6 Agerus
		RA_LYING_DORMANT,
		RA_MISSILE_CRATERS,
		RA_METEOR_DRONES
	},
	{	// 7 Bosalian
		RA_MENTAL_GIANT,
		RA_RETREAT_SHIELD,
		RA_GENETIC_THROWBACK
	},
	{	// 8 Xeloss
		RA_XENOPHOBIC_FANATIC,
		RA_MENTAL_GIANT,
		RA_CONSCIOUSNESS_CRYSTAL
	},
	{	// 9 Xerusian
		RA_ARTIFACT_CRYSTAL,
		RA_TACTICAL_GENIUS,
		RA_BLITZKRIEG
	},
	{	// 10 Xesperados
		RA_DEFENSIVE_MATRIX,
		RA_PSYCHIC_PROGENITOR,
		RA_ARTIFACT_COOLING_ENGINE
	},
	{	// 11 Trabotulin (CVS-native row)
		RA_CRUSADER,
		RA_IMPINGEMENT_NEUTRALIZATION_FIELD,
		RA_ARMADA_SYNERGY_SPECIALIST
	}
};


static int
GeniusRatio[CAdmiral::SC_MAX][21] =
{
	{ 0,  0,  0,  0,  0,  1,  2,  3,  4,  5,  6,  7,  8,  9, 11, 12, 13, 14, 15, 16, 17},
	{ 0,  0,  1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19},
	{ 5,  6,  7,  8,  9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25},
	{15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35},
	{25, 27, 28, 29, 30, 30, 30, 29, 28, 27, 25, 23, 21, 19, 16, 16, 16, 16, 16, 15, 15},
	{25, 24, 23, 22, 21, 20, 19, 18, 17, 16, 15, 14, 13, 12, 11,  8, 8, 7, 7, 6, 6},
	{15, 14, 13, 12, 11, 10,  9,  8,  7,  6,  5,  4,  3,  2,  1,  0, 0, 0, 0, 0, 0},
	{ 9,  8,  7,  6,  5,  4,  3,  2,  1,  0,  0,  0,  0,  0,  0,  0, 0, 0, 0 ,0 ,0},
	{ 6,  5,  4,  3,  2,  1,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0, 0 ,0 ,0 ,0 ,0}
};

int
CAdmiral::mRaceInitialSkill[MAX_RACE][MAX_SKILL] =
{
	// CVS-merge: all-zero 4-skill model (OFFENSE, DEFENSE, MANEUVER, DETECTION).
	// 11 rows (MAX_RACE), neutral skill bias for every race.
	{ 0,  0,  0,  0},
	{ 0,  0,  0,  0},
	{ 0,  0,  0,  0},
	{ 0,  0,  0,  0},
	{ 0,  0,  0,  0},
	{ 0,  0,  0,  0},
	{ 0,  0,  0,  0},
	{ 0,  0,  0,  0},
	{ 0,  0,  0,  0},
	{ 0,  0,  0,  0},
	{ 0,  0,  0,  0}
};

CAdmiral::CAdmiral(CPlayer *aPlayer)
{
	extern void scramble_10( int skill[] );

	static int
		InitialSkill[MAX_ADMIRAL_TYPE][CAdmiral::MAX_SKILL] =
	{
		{  1,  2,  3,  4},
		{  0,  1,  2,  3},
		{ -1,  0,  1,  2},
		{ -1,  0,  0,  1},
		{ -1, -1,  0,  1},
		{ -2, -1, -1,  0},
		{ -3, -2, -1,  0},
		{ -4, -3, -2, -1},
		{ -5, -4, -3, -2}
	};


	mRace = aPlayer->get_race();
	CControlModel *
		ControlModel = aPlayer->get_control_model();
	mStartingCircumstance = get_starting_circumstance_number(ControlModel->get_genius());
	mName = ADMIRAL_NAME_TABLE->get_random_name(mRace);
	mOwner = aPlayer->get_game_id();
	mExp = 0;
	mLevel = 1;
	mID = ++mMaxID;
	mFleetNumber = 0;

	// command abilities
	// default : 0 to 3. It's never changed.
	mArmadaCommanding = number(4) - 1;
	// default : 6 to 11
	mFleetCommanding = number(6) + 5;
	// default : 25 to 55%, min : 0%, max : 100%
	mEfficiency = number(31) + 24;

	static int
		SkillIndex[MAX_SKILL] = {  1,  3,  5,  7 };
	scramble_10( SkillIndex );

	for( int i = 0; i < MAX_SKILL; i++ ){
		mSkill[SkillIndex[i]][LEVEL] = InitialSkill[mStartingCircumstance][i] + mRaceInitialSkill[mRace-1][i];
	}

	static int
		IncreaseRate[] = { 1,  2, 3, 4 };
	scramble_10( SkillIndex );

	for (int i=0 ; i<MAX_SKILL ; i++)
	{
		mSkill[SkillIndex[i]][SKILL_UP_LEVEL] = IncreaseRate[i];
	}

	clear_level_by_effect();

	mSpecialAbility = number(SA_MAX)-1;
	mRacialAbility = mPossibleRacialSkill[mRace-1][number(3)-1];

	mAcademy = false;
}

CAdmiral::CAdmiral(MYSQL_ROW aRow)
{

	mID = atoi(aRow[STORE_ID]);
	mOwner = atoi(aRow[STORE_OWNER]);
	mRace = atoi(aRow[STORE_RACE]);
	mStartingCircumstance = atoi(aRow[STORE_TYPE]);
	mName = (char *)aRow[STORE_NAME];
	mExp = atoi(aRow[STORE_EXP]);
	mLevel = atoi(aRow[STORE_LEVEL]);
	mFleetNumber = atoi(aRow[STORE_FLEET_NUMBER]);

	// command abilities
	mArmadaCommanding = atoi(aRow[STORE_ARMADA_COMMANDING]);
	mFleetCommanding = atoi(aRow[STORE_FLEET_COMMANDING]);
	mEfficiency = atoi(aRow[STORE_EFFICIENCY]);

	// combat abilities (CVS-merge 4-skill model)
	mSkill[OFFENSE][LEVEL]
			= atoi(aRow[STORE_OFFENSE]);

	mSkill[OFFENSE][SKILL_UP_LEVEL]
			= atoi(aRow[STORE_OFFENSE_UP_LEVEL]);

	mSkill[DEFENSE][LEVEL]
			= atoi(aRow[STORE_DEFENSE]);

	mSkill[DEFENSE][SKILL_UP_LEVEL]
			= atoi(aRow[STORE_DEFENSE_UP_LEVEL]);

	// effectiveness abilities
	mSkill[MANEUVER][LEVEL]
			= atoi(aRow[STORE_MANEUVER]);

	mSkill[MANEUVER][SKILL_UP_LEVEL]
			= atoi(aRow[STORE_MANEUVER_UP_LEVEL]);

	mSkill[DETECTION][LEVEL]
			= atoi(aRow[STORE_DETECTION]);

	mSkill[DETECTION][SKILL_UP_LEVEL]
			= atoi(aRow[STORE_DETECTION_UP_LEVEL]);

	clear_level_by_effect();

	mSpecialAbility = atoi(aRow[STORE_COMMON_ABILITY]);
	mRacialAbility = atoi(aRow[STORE_RACE_ABILITY]);

	mAcademy = (atoi(aRow[STORE_ACADEMY]) != 0);

	if (mID > mMaxID) mMaxID = mID;

	type(QUERY_NONE);
}

CAdmiral::CAdmiral(int aLevel, int geniusLevel, int aFleetCommandingBonus, int aRace)
{
	if (aLevel < 1) aLevel = 1;
	if (aLevel > 20) aLevel = 20;

	extern void scramble_10( int skill[] );
	static int
		InitialSkill[MAX_ADMIRAL_TYPE][CAdmiral::MAX_SKILL] =
	{
		{  1,  2,  3,  4},
		{  0,  1,  2,  3},
		{ -1,  0,  1,  2},
		{ -1,  0,  0,  1},
		{ -1, -1,  0,  1},
		{ -2, -1, -1,  0},
		{ -3, -2, -1,  0},
		{ -4, -3, -2, -1},
		{ -5, -4, -3, -2}
	};

	mOwner = 0;
	mID = ++mMaxID;
	if( aRace < 0 )
	{
		// Bug fix: was number(10), which yields races 1..10 and can NEVER produce
		// race 11 (Trabotulin) -- so every random-race NPC / black-market commander
		// excluded Trabotulin's three racial abilities (Armada Synergy Specialist,
		// Crusader, Impingement). Restore CVSRoot's number(MAX_RACE) with a single
		// re-roll on Trabotulin (== MAX_RACE) so it stays rare but reachable.
		mRace = number( MAX_RACE );
		if( mRace == MAX_RACE )
			mRace = number( MAX_RACE );
	}
	else
		mRace = aRace;
	mName = ADMIRAL_NAME_TABLE->get_random_name( mRace );
	mStartingCircumstance = get_starting_circumstance_number( geniusLevel);
	mExp = 0;
	mLevel = 1;
	mFleetNumber = 0;

	// command abilities
	// default : 0 to 3. It's never changed.
	mArmadaCommanding = number(4) - 1;
	// default : 6 to 11
	mFleetCommanding = number(6) + 5 + aFleetCommandingBonus;
	// default : 25 to 55%, min : 0%, max : 100%
	mEfficiency = number(31) + 24;

	static int
		SkillIndex[MAX_SKILL] = { 1, 3, 5, 7};
	scramble_10(SkillIndex);

	for (int i = 0 ; i<MAX_SKILL ; i++)
	{
		mSkill[SkillIndex[i]][LEVEL] = InitialSkill[mStartingCircumstance][i] + mRaceInitialSkill[mRace-1][i];
	}

	static int
		IncreaseRate[] = {1, 2, 3, 4 };
	scramble_10(SkillIndex);

	for (int i=0 ; i<MAX_SKILL ; i++)
	{
		mSkill[SkillIndex[i]][SKILL_UP_LEVEL] = IncreaseRate[i];
	}

	clear_level_by_effect();

	mSpecialAbility = number(SA_MAX)-1;
	mRacialAbility = mPossibleRacialSkill[mRace-1][number(3)-1];

	mAcademy = false;

	// give_level(N) ADDS N levels; from mLevel=1 we add aLevel-1 to reach exactly
	// level aLevel. (Passing aLevel overshot to 1+aLevel, and for aLevel==20 tripped
	// give_level's `mLevel+aLevel>20` guard -> 0 levels granted -> bots/NPCs stuck at 1.)
	give_level(aLevel - 1);
}

char *
CAdmiral::get_nick()
{
	static CString
		Nick;
	Nick.clear();

	Nick.format("%s(#%d)", get_name(), get_id());

	return (char *)Nick;
}

int
CAdmiral::get_starting_circumstance_number(int aGenius)
{
	int
		Random = number(100);
	int
		Total = 0;

	if( aGenius < -5 ) aGenius = -5;
	if( aGenius > 15 ) aGenius = 15;

	for(int i=0; i<MAX_ADMIRAL_TYPE; i++)
	{
		Total += GeniusRatio[i][aGenius+5];
		if (Total >= Random)
			return i;
	}

	return 4;
}

int
CAdmiral::get_fleet_commanding()
{
	int
		Skill = mFleetCommanding;

	if( mRacialAbility == RA_JURY_RIGGER ){
		if( mLevel <= 5 )
			Skill--;
		else if( mLevel <= 12 )
			Skill -= 2;
		else
			Skill -= 3;
	}

	// Armada Synergy: +1 fleet-commanding per level, computed LIVE here (was a
	// stored per-level increment in level_up/give_level). Live so it applies to
	// EXISTING commanders too -- a commander that gained its levels before the
	// bonus logic existed never accumulated the stored value, and a stored bonus
	// cannot be applied retroactively. (mLevel-1) is the +1-per-level total.
	if( mRacialAbility == RA_ARMADA_SYNERGY_SPECIALIST )
		Skill += mLevel - 1;

	if( Skill > 100 )
		Skill = 100;

	return Skill;
}

int
CAdmiral::get_efficiency()
{
	int
		Skill = mEfficiency;

	if( mRacialAbility == RA_CLONAL_DOUBLE ){
		if( mLevel <= 7 )
			Skill -= 10;
		else if( mLevel <= 13 )
			Skill -= 5;
	}
	if( mRacialAbility == RA_PSYCHIC_PROGENITOR ){
		if( mLevel <= 12 )
			Skill -= 10;
		else if( mLevel <= 17 )
			Skill -= 5;
	}
	// cvs-merge: the +efficiency bonus belongs to Trajectory Augmentation (strict
	// CVSRoot placement), not Jury Rigger. Jury Rigger keeps its in-battle repair
	// (battle.cc) and its -fleet-commanding penalty (get_fleet_commanding).
	if( mRacialAbility == RA_TRAJECTORY_AUGMENTATION ){
		Skill += 10+mLevel;
	}
	if( mRacialAbility == RA_RETREAT_SHIELD ){
		Skill -= mLevel*2;
	}
	if( mRacialAbility == RA_BREEDER_MALE ){
		Skill += mLevel/2;

	}
	Skill += mSkillByEffect[10];

	if (Skill > 100) Skill = 100;

	return Skill;
}

int
CAdmiral::get_overall_attack()
{
	int
		Skill = mSkill[OFFENSE][LEVEL];

	CPlayer *
		Owner = PLAYER_TABLE->get_by_game_id(get_owner());

	if( Owner->has_ability(ABILITY_PACIFIST) )
		Skill -= 3;

	if( Owner->has_ability(ABILITY_TACTICAL_MASTERY) )
		Skill += 3;

	if( mRacialAbility == RA_GENETIC_THROWBACK ){
		if( mLevel <= 10 )
			Skill++;
		else if( mLevel <= 15 )
			Skill += 2;
		else if( mLevel <= 19 )
			Skill += 3;
		else
			Skill += 5;
	}
	if( mRacialAbility == RA_XENOPHOBIC_FANATIC ){
		if( mLevel <= 12 )
			;
		else if( mLevel <= 19 )
			Skill++;
		else
			Skill += 2;
	}

	return Skill;
}

int
CAdmiral::get_overall_defense()
{
	int
		Skill = mSkill[DEFENSE][LEVEL];

	CPlayer *
		Owner = PLAYER_TABLE->get_by_game_id(get_owner());

	if( Owner->has_ability(ABILITY_TACTICAL_MASTERY) )
		Skill += 3;

	if( mRacialAbility == RA_GENETIC_THROWBACK ){
		if( mLevel <= 19 )
			Skill--;
	}
	if( mRacialAbility == RA_BLITZKRIEG ){
		if( mLevel <= 19 )
			Skill--;
	}

	return Skill;
}

int
CAdmiral::get_maneuver_level()
{
	int
		Skill = mSkill[MANEUVER][LEVEL];

	if( mRacialAbility == RA_IRRATIONAL_TACTICS ){
		if( mLevel <= 7 )
			Skill -= 2;
		else if( mLevel <= 19 )
			Skill--;
	}
	if( mRacialAbility == RA_METEOR_DRONES ){
		if( mLevel <= 7 )
			Skill--;
		else if( mLevel <= 13 )
			Skill -= 2;
		else if( mLevel <= 19 )
			Skill -= 3;
		else
			Skill -= 4;
	}
	if( mRacialAbility == RA_BLITZKRIEG ){
		if( mLevel <= 5 )
			Skill += 2;
		else if( mLevel <= 9 )
			Skill += 3;
		else if( mLevel <= 12 )
			Skill += 4;
		else if( mLevel <= 19 )
			Skill += 5;
		else
			Skill += 7;
	}

	if( mRacialAbility == RA_BREEDER_MALE ){
		if( mLevel <= 12 )
			Skill++;
		else
			Skill += 2;
	}

	Skill += mSkillByEffect[MANEUVER];

	return Skill;
}

int
CAdmiral::get_detection_level()
{
	int
		Skill = mSkill[DETECTION][LEVEL];

	if( mRacialAbility == RA_MENTAL_GIANT ){
		if( mLevel <= 6 )
			Skill += 3;
		else if( mLevel <= 9 )
			Skill += 4;
		else if( mLevel <= 12 )
			Skill += 5;
		else if( mLevel <= 15 )
			Skill += 6;
		else if( mLevel <= 17 )
			Skill += 8;
		else if( mLevel <= 18 )
			Skill += 10;
		else if( mLevel <= 19 )
			Skill += 11;
		else
			Skill += 12;
	}
	if( mRacialAbility == RA_ARTIFACT_COOLING_ENGINE ){
		if( mLevel <= 5 )
			Skill -= 1;
		else if( mLevel <= 8 )
			Skill -= 2;
		else if( mLevel <= 13 )
			Skill -= 4;
		else if( mLevel <= 17 )
			Skill -= 6;
		else
			Skill -= 8;
	}
	if( mRacialAbility == RA_CYBER_SCAN_UNIT)
	{
		if( mLevel <= 3 )
			Skill += 1;
		else if( mLevel <= 5 )
			Skill += 2;
		else if( mLevel <= 8 )
			Skill += 3;
		else if( mLevel <= 12 )
			Skill += 4;
		else if( mLevel <= 15 )
			Skill += 5;
		else if( mLevel <= 17 )
			Skill += 6;
		else if( mLevel <= 19 )
			Skill += 7;
		else
			Skill += 9;
	}
	if( mRacialAbility == RA_INTUITION)
	{
		if( mLevel <= 4 )
			Skill += 1;
		else if( mLevel <= 7 )
			Skill += 2;
		else if( mLevel <= 11 )
			Skill += 3;
		else if( mLevel <= 14 )
			Skill += 4;
		else if( mLevel <= 17 )
			Skill += 5;
		else
			Skill += 6;
	}

	if( mRacialAbility == RA_BREEDER_MALE ){
		if( mLevel <= 12 )
			Skill++;
		else
			Skill += 2;
	}

	// cvs-merge: Crusader commander detection bonus (CVSRoot get_detection_level)
	if( mRacialAbility == RA_CRUSADER ){
		Skill += (int) mLevel/2;
	}

	Skill += mSkillByEffect[DETECTION];

	return Skill;
}

void
CAdmiral::gain_exp(int aExp)
{
	mStoreFlag += STORE_EXP;
	mExp += aExp;
	level_up();
}

void
CAdmiral::set_fleet_number(int aFleetNumber)
{
	mFleetNumber = aFleetNumber;
	mStoreFlag += STORE_FLEET_NUMBER;
}

char *
CAdmiral::get_starting_circumstance_name()
{
	return mStartingCircumstanceName[mStartingCircumstance];
}

int
CAdmiral::get_armada_commanding()
{
	int
		Skill = mArmadaCommanding; //+mSkillByEffect[11]
	if( Skill < AC_A ) Skill = AC_A;
	if( Skill > AC_D ) Skill = AC_D;

	return Skill;
}

char *
CAdmiral::get_armada_commanding_name()
{
	switch (get_armada_commanding())
	{
		case AC_A :
			return "A";
		case AC_B :
			return "B";
		case AC_C :
			return "C";
		case AC_D :
			return "D";
		default :
			return "";
	}
}

int
CAdmiral::get_armada_commanding_effect(int aSkillType)
{
	int
		Level = mSkill[aSkillType][LEVEL];
	if( Level < -5 ) Level = -5;
	if( Level > 20 ) Level = 20;

	// cvs-merge: Tactical Genius +50% armada-commanding skill effect (CVSRoot)
	if (mRacialAbility != RA_TACTICAL_GENIUS)
		return mArmadaCommandingData[get_armada_commanding()][Level+5];
	else
		return (int)(3*mArmadaCommandingData[get_armada_commanding()][Level+5]/2);
}

int
CAdmiral::get_armada_commanding_effect_to_efficiency()
{
	// cvs-merge: Tactical Genius +5 efficiency at every command grade (CVSRoot)
	if (mRacialAbility != RA_TACTICAL_GENIUS)
	{
		switch (get_armada_commanding())
		{
			case AC_A :
				return 10;
			case AC_B :
				return 5;
			case AC_C :
				return 0;
			case AC_D :
				return -5;
			default :
				return -999;
		}
	}
	else
	{
		switch (get_armada_commanding())
		{
			case AC_A :
				return 15;
			case AC_B :
				return 10;
			case AC_C :
				return 5;
			case AC_D :
				return 0;
			default :
				return -999;
		}
	}
}

char *
CAdmiral::get_special_ability_name()
{
	return mSpecialAbilityName[mSpecialAbility];
}

char *
CAdmiral::get_racial_ability_name()
{
	return mRacialAbilityName[mRacialAbility];
}

// QoL tooltips: raw (unescaped) ability descriptions; callers escape at emit.
char *
CAdmiral::get_special_ability_description()
{
	return mSpecialAbilityDescription[mSpecialAbility];
}

char *
CAdmiral::get_racial_ability_description()
{
	return mRacialAbilityDescription[mRacialAbility];
}

char *
CAdmiral::get_ability_name()
{
	CString
		CommonAbility = get_special_ability_name(),
		RaceAbility = get_racial_ability_name();

	return (char *)format("%s, %s", (char *)CommonAbility, (char *)RaceAbility);
}

// QoL: the special + racial ability names, each wrapped in a data-tip span so the
// web tier (as-project-tooltips.js) shows a hover tooltip explaining the ability.
// Descriptions are escaped; built in two appends so only one htmlspecialchars()
// is live per format() (it returns a shared static buffer). Kept separate from
// get_ability_name() so that getter stays HTML-free for its JS-string callers.
char *
CAdmiral::get_ability_html()
{
	static CString
		Html;
	// The descriptions are now mechanical STAT lines carrying literal <BR>
	// separators, so emit them RAW into data-tip (they contain no double-quote).
	Html = (char *)format("<span data-tip=\"%s\">%s</span>, ",
			get_special_ability_description(),
			get_special_ability_name());
	Html += (char *)format("<span data-tip=\"%s\">%s</span>",
			get_racial_ability_description(),
			get_racial_ability_name());
	return (char *)Html;
}

bool
CAdmiral::level_up()
{
	if (mLevel >= MAX_LEVEL) return false;
	if (mExp < mExpLevelTable[mLevel]) return false;

	do
	{
		mLevel++;
		mStoreFlag += STORE_LEVEL;


		mFleetCommanding += mPlusFleetCommanding[mLevel];
		if(mRacialAbility == RA_BREEDER_MALE)
		{
			switch(mLevel)
			{
				// cvs-merge: match CVSRoot Breeder Male thresholds (was 2/6/11/16/20)
				case 2:
				case 6:
				case 10:
				case 13:
				case 16:
				case 19:
				{
					mFleetCommanding += 1;
					break;
				}
			}
		}
		// cvs-merge: Management Protocol +1 fleet-commanding at levels 2/7/12/17 (CVSRoot)
		if(mRacialAbility == RA_MANAGEMENT_PROTOCOL)
		{
			switch(mLevel)
			{
				case 2:
				case 7:
				case 12:
				case 17:
				{
					mFleetCommanding += 1;
					break;
				}
			}
		}
		// Armada Synergy's +1/level fleet-commanding is applied LIVE in
		// get_fleet_commanding() (so it also covers existing commanders), not stored
		// here. (The CVSRoot level-up `break` was already dropped so these commanders
		// still gain skills/efficiency.)
		// cvs-merge: match CVSRoot's fleet-commanding cap (was 45)
		if(mFleetCommanding > 100)
			mFleetCommanding = 100;

		mStoreFlag += STORE_FLEET_COMMANDING;

		if (mLevel%mSkill[OFFENSE][SKILL_UP_LEVEL] == 0)
		{
			mSkill[OFFENSE][LEVEL]++;
			mStoreFlag += STORE_OFFENSE;
		}
		if (mLevel%mSkill[DEFENSE][SKILL_UP_LEVEL] == 0)
		{
			mSkill[DEFENSE][LEVEL]++;
			mStoreFlag += STORE_DEFENSE;
		}
		if (mLevel%mSkill[MANEUVER][SKILL_UP_LEVEL] == 0)
		{
			mSkill[MANEUVER][LEVEL]++;
			mStoreFlag += STORE_MANEUVER;
		}
		if (mLevel%mSkill[DETECTION][SKILL_UP_LEVEL] == 0)
		{
			mSkill[DETECTION][LEVEL]++;
			mStoreFlag += STORE_DETECTION;
		}

		switch (mStartingCircumstance)
		{
			case SC_SUPERCOMMANDER :
				mEfficiency += dice(2, 4);
				break;
			case SC_EXCELLENT :
				mEfficiency += number(8);
				break;
			case SC_VERY_GOOD :
				mEfficiency += dice(2, 3);
				break;
			case SC_GOOD :
				mEfficiency += number(6);
				break;
			case SC_AVERAGE :
				mEfficiency += dice(2, 2);
				break;
			case SC_POOR :
				mEfficiency += number(4);
				break;
			case SC_BAD :
				mEfficiency += number(3);
				break;
			case SC_VERY_BAD :
				mEfficiency += number(2);
				break;
			case SC_CANNON_FODDER :
			default :
				mEfficiency += 1;
				break;
		}

		mStoreFlag += STORE_EFFICIENCY;
	}
	while (mExp >= mExpLevelTable[mLevel] && mLevel < MAX_LEVEL);

	return true;
}

void
CAdmiral::give_level( int aLevel )
{
	if( aLevel <= 0 || mLevel+aLevel > 20 ) return;

	for( int i = mLevel+1; i <= mLevel+aLevel; i++ ){
		for( int j = 0; j < MAX_SKILL; j++ ){
			if( i%mSkill[j][SKILL_UP_LEVEL] == 0 )
				mSkill[j][LEVEL]++;
		}
		mFleetCommanding += mPlusFleetCommanding[i];
		// cvs-merge: Breeder Male fleet-commanding growth on directly-granted levels
		// too (CVSRoot give_level Breeder block), keyed on the level `i`.
		if(mRacialAbility == RA_BREEDER_MALE)
		{
			switch(i)
			{
				case 2:
				case 6:
				case 10:
				case 13:
				case 16:
				case 19:
				{
					mFleetCommanding += 1;
					break;
				}
			}
		}
		// cvs-merge: Management Protocol / Armada Synergy fleet-commanding growth on
		// directly-granted levels too (CVSRoot give_level), keyed on the level `i`.
		if(mRacialAbility == RA_MANAGEMENT_PROTOCOL)
		{
			switch(i)
			{
				case 2:
				case 7:
				case 12:
				case 17:
				{
					mFleetCommanding += 1;
					break;
				}
			}
		}
		// Armada Synergy: applied live in get_fleet_commanding(), not stored here.
		if(mFleetCommanding > 100)
			mFleetCommanding = 100;

		int EffUp;

		switch( mStartingCircumstance ){
			case SC_SUPERCOMMANDER :
				EffUp = dice( 2, 4 );
				break;
			case SC_EXCELLENT :
				EffUp = number(8);
				break;
			case SC_VERY_GOOD :
				EffUp = dice( 2, 3 );
				break;
			case SC_GOOD :
				EffUp = number(6);
				break;
			case SC_AVERAGE :
				EffUp = dice( 2, 2 );
				break;
			case SC_POOR :
				EffUp = number(4);
				break;
			case SC_BAD :
				EffUp = number(3);
				break;
			case SC_VERY_BAD :
				EffUp = number(2);
				break;
			case SC_CANNON_FODDER :
			default :
				EffUp = 1;
				break;
		}

		mEfficiency += EffUp;
	}

	mExp = mExpLevelTable[mLevel+aLevel-1];
	mLevel += aLevel;
	mStoreFlag += STORE_LEVEL;
	mStoreFlag += STORE_FLEET_COMMANDING;	// cvs-merge: persist FC growth from granted levels (CVSRoot)
}

CString &
CAdmiral::query()
{
	static CString
		Query;

	Query.clear();

	switch (mQueryType)
	{
		case QUERY_INSERT :
			Query.format("INSERT INTO admiral "
					"(id, owner, race, type, "
					"name, exp, level, fleet_number, "
					"armada_commanding, fleet_commanding, efficiency, "
					"offense, offense_up_level, "
					"defense, defense_up_level, "
					"maneuver, maneuver_up_level, "
					"detection, detection_up_level, "
					"commonability, raceability, academy) "
					"VALUES (%d, %d, %d, %d, "
					"'%s', %d, %d, %d, "
					"%d, %d, %d, "
					"%d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d)",
					mID, mOwner, mRace, mStartingCircumstance,
					(char *)add_slashes((char *)mName), mExp, mLevel, mFleetNumber,
					mArmadaCommanding, mFleetCommanding, mEfficiency,
					mSkill[ OFFENSE ][ LEVEL ],
					mSkill[ OFFENSE ][ SKILL_UP_LEVEL ],
					mSkill[ DEFENSE ][ LEVEL ],
					mSkill[ DEFENSE ][ SKILL_UP_LEVEL ],
					mSkill[ MANEUVER ][ LEVEL ],
					mSkill[ MANEUVER ][ SKILL_UP_LEVEL ],
					mSkill[ DETECTION ][ LEVEL ],
					mSkill[ DETECTION ][ SKILL_UP_LEVEL ],
					mSpecialAbility, mRacialAbility, mAcademy);
			break;
		case QUERY_UPDATE :
			Query.format("UPDATE admiral SET exp = %d, level = %d", mExp, mLevel);

#define STORE(x, y, z) \
		if (mStoreFlag.has(x)) Query.format(y, z)

			STORE(STORE_ID, ", id = %d", mID);
			STORE(STORE_OWNER, ", owner = %d", mOwner);
			STORE(STORE_RACE, ", race = %d", mRace);
			STORE(STORE_NAME, ", name = '%s'", (char *)add_slashes((char *)mName));
			STORE(STORE_TYPE, ", type = %d", mStartingCircumstance);
			STORE(STORE_LEVEL, ", level = %d", mLevel);
			STORE(STORE_FLEET_NUMBER, ", fleet_number = %d", mFleetNumber);

			STORE(STORE_ARMADA_COMMANDING,
					", armada_commanding = %d", mArmadaCommanding);
			STORE(STORE_FLEET_COMMANDING,
					", fleet_commanding = %d", mFleetCommanding);
			STORE(STORE_EFFICIENCY,
					", efficiency = %d", mEfficiency);

			STORE(STORE_OFFENSE,
					", offense = %d",
					mSkill[ OFFENSE ][ LEVEL ]);
			STORE(STORE_OFFENSE_UP_LEVEL,
					", offense_up_level = %d",
					mSkill[ OFFENSE ][ SKILL_UP_LEVEL ]);

			STORE(STORE_DEFENSE,
					", defense = %d",
					mSkill[ DEFENSE ][ LEVEL ]);
			STORE(STORE_DEFENSE_UP_LEVEL,
					", defense_up_level = %d",
					mSkill[ DEFENSE ][ SKILL_UP_LEVEL ]);

			STORE(STORE_MANEUVER,
					", maneuver = %d",
					mSkill[ MANEUVER ][ LEVEL ]);
			STORE(STORE_MANEUVER_UP_LEVEL,
					", maneuver_up_level = %d",
					mSkill[ MANEUVER ][ SKILL_UP_LEVEL ]);
			STORE(STORE_DETECTION,
					", detection = %d",
					mSkill[ DETECTION ][ LEVEL ]);
			STORE(STORE_DETECTION_UP_LEVEL,
					", detection_up_level = %d",
					mSkill[ DETECTION ][ SKILL_UP_LEVEL ]);

			STORE(STORE_ACADEMY, ", academy = %d", mAcademy);

			Query.format( " WHERE id = %d", mID );


			mStoreFlag.clear();

			break;
		case QUERY_DELETE :
			Query.format( "DELETE FROM admiral WHERE id = %d AND owner = %d",
					mID, mOwner );
			break;
	}

	return Query;
}

bool
CAdmiral::distribute_exp()
{
	CPlayer *
		Owner = PLAYER_TABLE->get_by_game_id(mOwner);
	if (!Owner) return false;

	CAdmiralList *
		AdmiralList = Owner->get_admiral_list();
	for (int i=0 ; i<AdmiralList->length() ; i++)
	{
		CAdmiral *
			Admiral = (CAdmiral *)AdmiralList->get(i);
		if (Admiral->get_id() == mID) continue;

		int
			Exp = (mLevel - Admiral->get_level())*50;
		if (Exp > 0) Admiral->gain_exp(Exp);
	}

	CAdmiralList *
		AdmiralPool = Owner->get_admiral_pool();
	for (int i=0 ; i<AdmiralPool->length() ; i++)
	{
		CAdmiral *
			Admiral = (CAdmiral *)AdmiralPool->get(i);
		if (Admiral->get_id() == mID) continue;

		int
			Exp = (mLevel - Admiral->get_level())*50;
		if (Exp > 0) Admiral->gain_exp(Exp);
	}

	return true;
}

CStoreCenter&
operator<<(CStoreCenter& aStoreCenter, CAdmiral &aAdmiral)
{
	aStoreCenter.store(aAdmiral);
	return aStoreCenter;
}

void scramble_10( int aSkill[] )
{
	// make aSkill to 0-4 array
	for( int i = 0; i < CAdmiral::MAX_SKILL; i++ )
		aSkill[i] = i;

	// random swapping for 20 times
	for( int i = 0; i < CAdmiral::MAX_SKILL*2; i++ ){
		int j = number(4)-1, k = number(4)-1, t;

		t = aSkill[j]; aSkill[j] = aSkill[k]; aSkill[k] = t;
	}
}

void
CAdmiral::clear_level_by_effect()
{
	for( int i = 0; i < MAX_SKILL+2; i++ )
		mSkillByEffect[i] = 0;
}

void
CAdmiral::set_level_by_effect( int aAbility, int aLevel )
{
	if( aAbility < 0 || aAbility >= MAX_SKILL+2 ) return;
	mSkillByEffect[aAbility] += aLevel;
}
