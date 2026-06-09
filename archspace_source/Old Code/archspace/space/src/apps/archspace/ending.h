#if !defined(__ARCHSPACE_ENDING_H__)
#define __ARCHSPACE_ENDING_H__

#include "rank.h"

class CRank;
class CRankTable;
class CProject;
class CRace;

class CPersonalEnding
{
	private:
		int
			mID,
			mProjectID,
			mRaceIndex;
		int
			mConditionTech,
			mConditionTech2,
			mConditionProject,
			mConditionPlanet,
			mConditionCluster,
			mConditionCommanderLevel,
			mConditionFleet,
			mConditionCouncil,
			mConditionRP,
			mConditionDA,
			mConditionDAequalORless,
			mConditionDAgreater,
			mConditionEfficiency,
			mConditionHaveDoomstar,
			mConditionCouncilProject,
			mConditionShipPool,
			mConditionPopulationIncreasedLess,
			mConditionPopulationIncreasedEqualOrMore,
			mConditionConcentrationMode;
		bool
			mConditionTechAll,
			mConditionHaveTitle,
			mConditionNoWarInCouncil,
			mConditionCouncilSpeaker;
		CString
			mName;
	public:
		CPersonalEnding();
		inline int get_id();
		inline char *get_name();
		inline int get_project_id();
		CProject* get_ending_project();
		inline int get_race_index();
		CRace* get_race();

		inline void set_id(int aID);
		inline void set_name(char* aName);
		inline void set_project_id(int aID);
		inline void set_race_index(int aIndex);
		void set_condition(char *aConditionName, int aValue);

		bool check_condition(CPlayer *aPlayer);
};

inline int
CPersonalEnding::get_id()
{
	return mID;
}

inline char *
CPersonalEnding::get_name()
{
	return (char*)mName;
}

inline int
CPersonalEnding::get_project_id()
{
	return mProjectID;
}

inline int
CPersonalEnding::get_race_index()
{
	return mRaceIndex;
}

inline void
CPersonalEnding::set_id(int aID)
{
	mID = aID;
}

inline void
CPersonalEnding::set_name(char* aName)
{
	mName = aName;
}

inline void
CPersonalEnding::set_project_id(int aID)
{
	mProjectID = aID;
}

inline void
CPersonalEnding::set_race_index(int aIndex)
{
	mRaceIndex = aIndex;
}

class CPersonalEndingTable: public CSortedList
{
	public:
		CPersonalEndingTable();
		virtual ~CPersonalEndingTable();
		
		int add_personal_ending(CPersonalEnding* PersonalEnding);
		bool remove_personal_ending(int aPersonalEndingID);

		CPersonalEnding *get_by_id(int aPersonalEndingID);
	protected:
		virtual bool free_item(TSomething aItem);
		virtual int compare(TSomething aItem1, TSomething aItem2) const;
		virtual int compare_key(TSomething aItem, TConstSomething aKey) const;
		virtual const char *debug_inf() const { return "ending table"; }
};

class CGlobalEnding
{
	public :
		static int
			mScorePerPlanet,
			mPopulationPerScore,
			mScorePerTechLevel,
			mAdmiralExpPerScore,
			mProjectPricePerScore,
			mScorePerSecretProject,
			mScorePerUsedTurn,

			mMultiplierForPersonalEnding,
			mMultiplierForAllKnownTechs,
			mMultiplierForTitle,
			mMultiplierForSpeaker,
			mMultiplierForHonor1,
			mMultiplierForHonor2,

			mScorePerFortressForPlayer,
			mScorePerFortressForCouncilLayer1,
			mScorePerFortressForCouncilLayer2,
			mScorePerFortressForCouncilLayer3,
			mScorePerFortressForCouncilLayer4,
			mScorePerEmpireCapitalPlanet;

	public :
		CGlobalEnding();
		virtual ~CGlobalEnding();

		bool add_player_score(CRank *aScore);
		CRankTable *get_player_score_list() { return &mPlayerScoreList; }

		bool add_council_score(CRank *aScore);
		CRankTable *get_council_score_list() { return &mCouncilScoreList; }

		bool is_final_score() { return mIsFinalScore; }
		void set_final_score();

	public :
		char *get_score_html(CPlayer *aPlayer);

	private :
		CRankTable
			mPlayerScoreList,
			mCouncilScoreList;

		bool
			mIsFinalScore;
};

#endif
