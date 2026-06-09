#if !defined(__ARCHSPACE_FLEET_H__)
#define __ARCHSPACE_FLEET_H__

#include "ship.h"

class CAdmiralList;
class CAdmiral;

/**
*/
class CMission: public CBase
{
	public:
		enum EMissionType 
		{
			MISSION_NONE = 0,
			MISSION_TRAIN,
			MISSION_STATION_ON_PLANET,
			MISSION_PATROL,
			MISSION_EXPEDITION,
			MISSION_DISPATCH_TO_ALLY,
			MISSION_RETURNING_WITH_PLANET,
			MISSION_SORTIE,
			MISSION_RETURNING,
			MISSION_PRIVATEER
		};

	private :
		EMissionType mMission;
		int mTarget;
		time_t mTerminateTime;  // By Second

	public:
		static time_t mTrainMissionTime;
		static time_t mPatrolMissionTime;
		static time_t mDispatchToAllyMissionTime;
		static time_t mExpeditionMissionTime;
		static time_t mReturningWithPlanetMissionTime;

	public :
		CMission();
		virtual ~CMission();

		inline EMissionType get_mission();
		int get_target() { return mTarget; }
		inline const char *get_mission_name();
		inline time_t get_terminate_time();

		inline void set_mission(EMissionType aMission);
		inline void set_target(int aTarget);
		inline void set_terminate_time(time_t aTime);

		bool is_over();

		static const char *get_mission_name(EMissionType aMission);

		const char *get_query();

	public:
		static char *select_mission_html();
};

extern TZone gFleetZone;
/**
*/
class CFleet: public CShipDesign
{
	public:
		enum EFleetStatus {
			FLEET_STAND_BY = 0,
			FLEET_UNDER_OPERATION,
			FLEET_UNDER_MISSION,
			FLEET_DEACTIVATED,
			FLEET_PRIVATEER,
			FLEET_COMMANDER_ABSENT
		};
	protected:
		int
			mID;
		int
			mAdmiralID;
		int
			mExp,
			mMaxShip,
			mCurrentShip,
			mKilledShip,
			mKilledFleet;
		int
			mStatus;
		CString
			mName;
		CString
			mKey;

	private:
		CMission mMission;

	public:
		CFleet();
		CFleet(CShipDesignList *aList, MYSQL_ROW aRow);
		virtual ~CFleet() {}

		virtual const char *table() { return "fleet"; }
		virtual CString& query();

	public:
		enum
		{
			STORE_OWNER = 0,
			STORE_ID,
			STORE_NAME,
			STORE_ADMIRAL,
			STORE_EXP,
			STORE_STATUS,
			STORE_MAX_SHIP,
			STORE_CURRENT_SHIP,
			STORE_SHIP_CLASS,
			STORE_MISSION,
			STORE_MISSION_TARGET,
			STORE_MISSION_TERMINATE_TIME,
			STORE_KILLED_SHIP,
			STORE_KILLED_FLEET
		};
	private:
		static const char 
			*mStatusString[];
	public:
		inline int get_id();
		char *get_key() { return (char *)mKey; }
		const char *get_name();
		const char *get_nick();
		const char *get_class_name() { return CShipDesign::get_name(); }
		const char *get_size_name();
		inline int get_admiral_id() { return mAdmiralID; }
		inline int get_exp();
		inline int get_status();
		inline const char *get_status_string();
		static const char* get_status_string(int aStatus);
		const char *get_detailed_status_string();

		inline int get_max_ship();
		inline int get_current_ship();
		inline int get_ship_class();
		int get_power();
		inline CMission& get_mission();
		inline int get_killed_ship();
		inline int get_killed_fleet();

		inline void set_owner(int aOwner);
		inline void set_id(int aID);
		inline void set_name(const char* aName);
		inline void set_admiral(int aAdmiral);
		inline void set_exp(int aExp);
		inline void set_status(EFleetStatus aStatus);
		inline void set_max_ship(int aNo);
		inline void set_current_ship(int aNo);
		inline void set_ship_class(CShipDesign *aClass);
		inline void set_mission(CMission::EMissionType aMission);
		inline void set_target(int aTarget);
		bool delay_mission(time_t aDelayedTime = 0);

		inline void change_killed_ship( int aKill );
		inline void change_killed_fleet( int aKill );

		void change_exp( int aExp );
		inline bool under_mission();
		void end_mission();
		bool init_mission(CMission::EMissionType aMission, int aTarget, time_t aCostTime = 0);
		int calc_upkeep();
		inline bool is_changed();

		static char *select_reassignment_html();

		/* For Empire Fleet */
		bool create_as_empire_fleet(int aFleetRating, int aTechLevel, CAdmiral *aAdmiral, int aOrder);
	protected:
		void init_key();

	RECYCLE(gFleetZone);
};

/**
*/
class CFleetList: public CSortedList
{
	private:
		int
			mPower;

	/* For Empire */
	private :
		bool
			mIsVolatile;

	public:
		CFleetList();
		virtual ~CFleetList();

		bool add_fleet(CFleet *aFleet);
		bool remove_fleet(int aFleetID);
		bool remove_without_free_fleet(int aFleetID);
 
		CFleet* get_by_id(int aFleetID, int *aIndex = NULL);

		int get_new_fleet_id();
		bool is_id_available(int aID );
		int get_total_ship_number();
		int fleet_number_by_status(int aStatus);

		int get_power() { return mPower; }
		bool refresh_power();

		CFleet *get_fleet_station_on_planet(int aPlanetID);

		bool update_DB();

		char *all_fleet_list_html(CPlayer *aPlayer);
		char *all_ally_fleet_list_html();
		char *deployment_fleet_list_html(CPlayer *aPlayer);
		char *possessed_fleet_info_html();
		char *recall_fleet_list_html(CPlayer *aPlayer);
		char *reassignment_fleet_list_html(CPlayer *aPlayer);
		char *expedition_fleet_list_html(CPlayer *aPlayer);
		char *mission_fleet_list_html(CPlayer *aPlayer);
		char *disband_fleet_list_html(CPlayer *aPlayer);
		char *disband_confirm_fleet_list_html(CPlayer *aPlayer, CCommandSet *aCommandSet);
		char *siege_planet_fleet_list_html(CPlayer *aPlayer);
		char *blockade_fleet_list_html(CPlayer *aPlayer);
		char *raid_fleet_list_html(CPlayer *aPlayer);
		char *privateer_fleet_list_html(CPlayer *aPlayer);

		/* For Empire */
		bool is_volatile() { return mIsVolatile; }
		void set_volatile();
		void set_not_volatile();

		bool create_as_empire_fleet_group_static(CAdmiralList *aAdmiralList, int aFleetGroupType, int aPositionArg = -1);
		bool create_as_empire_fleet_group_volatile(CAdmiralList *aAdmiralList, int aFleetGroupType, int aPositionArg = -1);

	protected:
        virtual bool free_item( TSomething aItem );
        virtual int compare( TSomething aItem1, TSomething aItem2 ) const;
        virtual int compare_key( TSomething aItem, TConstSomething aKey ) const;
		virtual const char *debug_info() const { return "fleet list"; }
};

/**
*/
class CAllyFleetList: public CFleetList
{
    public:
		CAllyFleetList();
		virtual ~CAllyFleetList();
 
		bool add_fleet(CFleet *aFleet);
		bool remove_fleet(CPlayer *aOwner, int aFleetID);

	protected:
		virtual bool free_item(TSomething aItem);
        virtual int compare(TSomething aItem1, TSomething aItem2) const;
        virtual int compare_key(TSomething aItem, TConstSomething aKey) const;
};

#endif

/* end of file fleet.h */
