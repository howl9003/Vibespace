#if !defined(__ARCHSPACE_ATTACK_PLAN_H__)
#define __ARCHSPACE_ATTACK_PLAN_H__

#include "store.h"

class CFleetList;

/*
  Attack templates: saved offence deployments, the converse of defence plans.
  Stored in their OWN tables (attack_plan / attack_fleet) and hung off the player
  in a separate list, so they never appear in the defender-side scans
  (get_optimal_plan / get_generic_plan / the bot) that walk the defence-plan list.

  Unlike defence plans there is no auto-selection: the attacker is at the board
  and picks a template by name, so the race / power-band / attack-type machinery
  is intentionally absent. A template is fleet-id keyed (like a defence plan) and
  is a shared pool usable on any of the offence deploy boards. Coordinates are
  stored in BOARD space (X 9..609, Y 226..426) -- exactly what the deploy form
  submits -- so autoload round-trips them straight back onto the board with no
  inverse transform; the offence result handler still does its own board->battle
  conversion when the attack is actually launched.
*/

extern TZone gAttackFleetZone;

class CAttackFleet: public CStore
{
	public:
		CAttackFleet() {}
		CAttackFleet( MYSQL_ROW aRow );
		virtual ~CAttackFleet() {}
	/* from CStore */
		virtual const char *table() { return "attack_fleet"; }
		virtual CString& query();
		friend CStoreCenter& operator<<(CStoreCenter& aStoreCenter, CAttackFleet& aFleet);
		enum
		{
			FIELD_OWNER = 0,
			FIELD_PLAN_ID,
			FIELD_FLEET_ID,
			FIELD_FLEET_COMMAND,
			FIELD_FLEET_X,
			FIELD_FLEET_Y
		};
	private:
		int
			mOwner,
			mPlanID,
			mCommand,
			mX,
			mY;
		int
			mFleetID;

		enum EStoreFlag
		{
			STORE_OWNER,
			STORE_PLAN_ID,
			STORE_FLEET_ID,
			STORE_COMMAND,
			STORE_X,
			STORE_Y
		};

	public:
		int get_owner() { return mOwner; }
		int get_plan_id() { return mPlanID; }
		int get_fleet_id() { return mFleetID; }
		int get_command() { return mCommand; }
		int get_x() { return mX; }
		int get_y() { return mY; }

		void set_owner( int aID ) { mStoreFlag += STORE_OWNER; mOwner = aID; }
		void set_plan_id( int aID ) { mStoreFlag += STORE_PLAN_ID; mPlanID = aID; }
		void set_fleet_id(int aID)
			{ mStoreFlag += STORE_FLEET_ID; mFleetID = aID; }
		void set_command(int aCommand)
			{ mStoreFlag += STORE_COMMAND; mCommand = aCommand; }
		void set_x(int x) { mStoreFlag += STORE_X; mX = x; }
		void set_y(int y) { mStoreFlag += STORE_Y; mY = y; }

	RECYCLE(gAttackFleetZone);
};

/**
*/
class CAttackFleetList: public CSortedList
{
	public:
		CAttackFleetList() {};
		virtual ~CAttackFleetList() {};

		int add_attack_fleet(CAttackFleet *aFleet);
		bool remove_attack_fleet(int aFleetID);
		bool remove_without_free_attack_fleet(int aFleetID);
		CAttackFleet *get_by_id(int aFleetID);

	protected:
		virtual bool free_item(TSomething aItem);
		virtual int compare(TSomething aItem1, TSomething aItem2) const;
		virtual int compare_key(TSomething aItem, TConstSomething aKey) const;
		virtual const char *debug_info() const { return "attack fleet list"; }
};

extern TZone gAttackPlanZone;
/**
*/
class CAttackPlan: public CStore
{
	public:
		CAttackPlan();
		CAttackPlan( MYSQL_ROW aRow);
		virtual ~CAttackPlan() {}
	/* from CStore */
		virtual const char *table() { return "attack_plan"; }
		virtual CString& query();
		friend CStoreCenter& operator<<(CStoreCenter& aStoreCenter, CAttackPlan& aPlan);
	private:
		int
			mOwner,
			mID;
		int
			mCapital;

		CAttackFleetList
			mFleetList;
		CString
			mName;
		enum
		{
			FIELD_OWNER,
			FIELD_ID,
			FIELD_NAME,
			FIELD_CAPITAL
		};
		enum EStoreFlag
		{
			STORE_OWNER,
			STORE_ID,
			STORE_NAME,
			STORE_CAPITAL
		};

	public:
		int get_owner() { return mOwner; }
		int get_id() { return mID; }
		const char* get_name() { return (char*)mName; }
		CAttackFleetList *get_fleet_list() { return &mFleetList; }
		int get_capital() { return mCapital; }

		void set_owner( int aOwner ) { mStoreFlag += STORE_OWNER; mOwner = aOwner; }
		void set_id( int aID ) { mStoreFlag += STORE_ID; mID = aID; }
		void set_name( char *aName ) { mStoreFlag += STORE_NAME; mName = aName; }
		void set_capital(int aCapital)
			{ mStoreFlag += STORE_CAPITAL; mCapital = aCapital; }

		bool add_attack_fleet(CAttackFleet *aFleet);
		bool remove_attack_fleet(int aIndex);

		int get_fleets_number();

	RECYCLE(gAttackPlanZone);
};

/**
*/
class CAttackPlanList: public CSortedList
{
	public:
		CAttackPlanList() {};
		virtual ~CAttackPlanList() {};
		int add_attack_plan(CAttackPlan* aPlan);
		bool remove_attack_plan(int aPlanID);
		CAttackPlan *get_by_id(int aPlanID);
		int get_new_id();

		/* Newline-delimited blob the HTML5 deploy board parses to populate its
		   template picker. One "T<id>|<name>" header per template, followed by a
		   "F<fleet_id>|<command>|<x>|<y>" line per fleet (board coords; the
		   capital row carries x=y=0 and only its stance matters). */
		char *deploy_board_blob();

	protected:
		virtual bool free_item( TSomething aItem );
		virtual int compare( TSomething aItem1, TSomething aItem2) const;
		virtual int compare_key( TSomething aItem,
								TConstSomething aKey) const;
		virtual const char *debug_info() const { return "attack plan list"; }
};

#endif

/* end of file attack.plan.h */
