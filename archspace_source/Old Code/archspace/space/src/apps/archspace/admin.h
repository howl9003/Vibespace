#if !defined(__ARCHSPACE_ADMIN_H__)
#define __ARCHSPACE_ADMIN_H__

#include "council.h"

class CPortalUserData
{
	private:
		int
			mID;
		CString
			mName,
			mPassword,
			mEMail,
			mFirstName,
			mLastName;
		int
			mICQ;
		CString
			mGender;
		int
			mAge;
		CString
			mCountry,
			mJob,
			mHowKnowUs;
		time_t
			mCreatedTime,
			mLastLogin;
		bool
			mIsAdmin;
	public:
		CPortalUserData();
		~CPortalUserData();

		int get_id() { return mID; }
		void set_id(int aID) { mID = aID; }

		char *get_name() { return (char *)mName; }
		void set_name(char *aName) { mName = aName; }

		char *get_password() { return (char *)mPassword; }
		void set_password(char *aPassword) { mPassword = aPassword; }

		char *get_email() { return (char *)mEMail; }
		void set_email(char *aEMail) { mEMail = aEMail; }

		char *get_first_name() { return (char *)mFirstName; }
		void set_first_name(char *aFirstName) { mFirstName = aFirstName; }

		char *get_last_name() { return (char *)mLastName; }
		void set_last_name(char *aLastName) { mLastName = aLastName; }

		int get_icq() { return mICQ; }
		void set_icq(int aICQ) { mICQ = aICQ; }

		char *get_gender() { return (char *)mGender; }
		void set_gender(char *aGender) { mGender = aGender; }

		int get_age() { return mAge; }
		void set_age(int aAge) { mAge = aAge; }

		char *get_country() { return (char *)mCountry; }
		void set_country(char *aCountry) { mCountry = aCountry; }

		char *get_job() { return (char *)mJob; }
		void set_job(char *aJob) { mJob = aJob; }

		char *get_how_know_us() { return (char *)mHowKnowUs; }
		void set_how_know_us(char *aHowKnowUs) { mHowKnowUs = aHowKnowUs; }

		time_t get_created_time() { return mCreatedTime; }
		void set_created_time(time_t aCreatedTime) { mCreatedTime = aCreatedTime; }

		time_t get_last_login() { return mLastLogin; }
		void set_last_login(time_t aLastLogin) { mLastLogin = aLastLogin; }

		bool get_is_admin() { return mIsAdmin; }
		void set_is_admin(bool aIsAdmin) { mIsAdmin = aIsAdmin; }
};

extern TZone gDetachmentPlayerPlayerZone;

class CDetachmentPlayerPlayer: public CStore
{
	public:
		CDetachmentPlayerPlayer();
		virtual ~CDetachmentPlayerPlayer() {}

		virtual const char *table() { return "detachment_player_player"; }
		virtual CString &query();

		int get_id() { return mID; }
		void set_id(int aID) { mID = aID; }

		CPlayer *get_player1() { return mPlayer1; }
		void set_player1(CPlayer *aPlayer) { mPlayer1 = aPlayer; }

		CPlayer *get_player2() { return mPlayer2; }
		void set_player2(CPlayer *aPlayer) { mPlayer2 = aPlayer; }

		bool initialize(MYSQL_ROW aRow);

	private:
		int
			mID;
		CPlayer
			*mPlayer1,
			*mPlayer2;
};

class CDetachmentPlayerPlayerTable: public CSortedList
{
	public:
		CDetachmentPlayerPlayerTable() {}
		virtual ~CDetachmentPlayerPlayerTable();

		bool add_detachment(CDetachmentPlayerPlayer *aDetachment);
		bool remove_detachment(int aID);

		CDetachmentPlayerPlayer *get_by_player_player(CPlayer *aPlayer1, CPlayer *aPlayer2);

		int get_new_id();

		bool load(CMySQL *aMySQL);

		char *get_restricted_player_list_html(CPlayer *aPlayer);

	protected:
		virtual bool free_item(TSomething aItem);
		virtual int compare(TSomething aItem1, TSomething aItem2) const;
		virtual int compare_key(TSomething aItem, TConstSomething aKey) const;
};

extern TZone gDetachmentPlayerCouncilZone;

class CDetachmentPlayerCouncil: public CStore
{
	public:
		CDetachmentPlayerCouncil();
		virtual ~CDetachmentPlayerCouncil() {}

		virtual const char *table() { return "detachment_player_council"; }
		virtual CString &query();

		int get_id() { return mID; }
		void set_id(int aID) { mID = aID; }

		CPlayer *get_player() { return mPlayer; }
		void set_player(CPlayer *aPlayer) { mPlayer = aPlayer; }

		CCouncil *get_council() { return mCouncil; }
		void set_council(CCouncil *aCouncil) { mCouncil = aCouncil; }

		bool initialize(MYSQL_ROW aRow);

	private:
		int
			mID;
		CPlayer *
			mPlayer;
		CCouncil *
			mCouncil;
};

class CDetachmentPlayerCouncilTable: public CSortedList
{
	public:
		CDetachmentPlayerCouncilTable() {}
		virtual ~CDetachmentPlayerCouncilTable();

		bool add_detachment(CDetachmentPlayerCouncil *aDetachment);
		bool remove_detachment(int aID);

		CDetachmentPlayerCouncil *get_by_player_council(CPlayer *aPlayer, CCouncil *aCouncil);

		int get_new_id();

		bool load(CMySQL *aMySQL);

		bool remove_from_db_memory(CCouncil *aCouncil);

		char *get_restricted_council_list_html(CPlayer *aPlayer);
		char *get_restricted_player_list_html(CCouncil *aCouncil);

	protected:
		virtual bool free_item(TSomething aItem);
		virtual int compare(TSomething aItem1, TSomething aItem2) const;
		virtual int compare_key(TSomething aItem, TConstSomething aKey) const;
};

extern TZone gDetachmentCouncilCouncilZone;

class CDetachmentCouncilCouncil: public CStore
{
	public:
		enum ERestrictionType
		{
			TYPE_NONE = 0,
			TYPE_SUBMISSION,
			TYPE_MERGING,
			TYPE_ALLIANCE,
			TYPE_WAR
		};
	public:
		CDetachmentCouncilCouncil();
		virtual ~CDetachmentCouncilCouncil() {}

		virtual const char *table() { return "detachment_council_council"; }
		virtual CString &query();

		int get_id() { return mID; }
		void set_id(int aID) { mID = aID; }

		int get_type() { return mType; }
		bool set_type(int aType);

		CCouncil *get_council1() { return mCouncil1; }
		void set_council1(CCouncil *aCouncil) { mCouncil1 = aCouncil; }

		CCouncil *get_council2() { return mCouncil2; }
		void set_council2(CCouncil *aCouncil) { mCouncil2 = aCouncil; }

		bool initialize(MYSQL_ROW aRow);

	private:
		int
			mID;
		int
			mType;
		CCouncil
			*mCouncil1,
			*mCouncil2;
};

class CDetachmentCouncilCouncilTable: public CSortedList
{
	public:
		CDetachmentCouncilCouncilTable() {}
		virtual ~CDetachmentCouncilCouncilTable();

		bool add_detachment(CDetachmentCouncilCouncil *aDetachment);
		bool remove_detachment(int aID);

		CDetachmentCouncilCouncil *get_by_council_council(CCouncil *aCouncil1, CCouncil *aCouncil2);

		int get_new_id();

		bool load(CMySQL *aMySQL);

		bool remove_from_db_memory(CCouncil *aCouncil);

		char *get_restricted_council_list_html(CCouncil *aCouncil);

	protected:
		virtual bool free_item(TSomething aItem);
		virtual int compare(TSomething aItem1, TSomething aItem2) const;
		virtual int compare_key(TSomething aItem, TConstSomething aKey) const;
};

class CAdminData
{
	private :
		CString
			mAdminType;
		int
			mPortalID;

	public:
		CAdminData();
		CAdminData(char *aAdminType, int aPortalID);
		virtual ~CAdminData();

	public:
		char *get_admin_type() { return (char *)mAdminType; }
		bool set_admin_type(char *aAdminType);

		int get_portal_id() { return mPortalID; }
		bool set_portal_id(int aPortalID);
};

class CAdminDataList : public CSortedList
{
	public:
		CAdminDataList();
		virtual ~CAdminDataList();

		bool add_admin_data(CAdminData *aAdminData);
		bool remove_admin_data(CAdminData *aAdminData);
		bool remove_admin_data(int aPortalID);

		bool load();
		bool save();

	protected :
		virtual bool free_item(TSomething aItem);
		virtual int compare(TSomething aItem1, TSomething aItem2) const;
		virtual int compare_key(TSomething aItem, TConstSomething aKey) const;
};

class CAdminTool
{
	public:
		CAdminTool();
		virtual ~CAdminTool();

	public:
		bool initialize(CMySQL *aMySQL);

		bool set_data_file_directory(char *aDirectory);
		bool set_CS_mail_address(char *aAddress);
		bool set_accuse_mail_name(char *aName);

		bool add_new_player_log(char *aLogString);
		bool add_dead_player_log(char *aLogString);
		bool add_restarting_player_log(char *aLogString);
		bool add_login_player_log(char *aLogString);

		bool add_bankrupt_report_log(char *aLogString);

		bool add_player_relation_log(char *aLogString);
		bool add_spy_log(char *aLogString);

		bool add_speaker_log(char *aLogString);
		bool add_financial_aid_log(char *aLogString);
		bool add_donation_log(char *aLogString);
		bool add_emigration_log(char *aLogString);
		bool add_independence_log(char *aLogString);
		bool add_council_relation_log(char *aLogString);

		bool add_new_bid_log(char *aLogString);
		bool add_winning_bid_log(char *aLogString);

		bool add_siege_planet_log(char *aLogString);
		bool add_blockade_log(char *aLogString);
		bool add_raid_log(char *aLogString);
		bool add_privateer_log(char *aLogString);

		bool add_invade_magistrate_log(char *aLogString);
		bool add_invade_empire_planet_log(char *aLogString);
		bool add_invade_fortress_log(char *aLogString);
		bool add_invade_empire_capital_planet_log(char *aLogString);

		bool add_punishment_log(char *aLogString);

		bool accuse_message(CDiplomaticMessage *aMessage);
		bool accuse_message(CCouncilMessage *aMessage);
		bool accuse_message(CAdmission *aAdmission);

		CDetachmentPlayerPlayerTable *get_detachment_player_player_table()
				{ return &mDetachmentPlayerPlayerTable; }
		CDetachmentPlayerCouncilTable *get_detachment_player_council_table()
				{ return &mDetachmentPlayerCouncilTable; }
		CDetachmentCouncilCouncilTable *get_detachment_council_council_table()
				{ return &mDetachmentCouncilCouncilTable; }

		bool are_they_restricted(CPlayer *aPlayer1, CPlayer *aPlayer2);
		bool are_they_restricted(CPlayer *aPlayer, CCouncil *aCouncil);
		bool are_they_restricted(CCouncilMessage::EType aType, CCouncil *aCouncil1, CCouncil *aCouncil2);

		CAdminDataList *get_admin_list() { return &mAdminDataList; }
		bool save_admin_list();
		bool add_admin_data(CAdminData *aAdminData);

		CIPList *get_banned_ip_list() { return &mBannedIPList; }
		bool load_banned_ip_list();
		bool save_banned_ip_list();
		bool add_banned_ip(char *aBannedIP);

		char *admin_list_html();
		char *banned_ip_list_html();

	protected :
		CDetachmentPlayerPlayerTable
			mDetachmentPlayerPlayerTable;
		CDetachmentPlayerCouncilTable
			mDetachmentPlayerCouncilTable;
		CDetachmentCouncilCouncilTable
			mDetachmentCouncilCouncilTable;

		CAdminDataList
			mAdminDataList;
		CIPList
			mBannedIPList;

		CString
			mDataFileDirectory,
			mCSMailAddress,
			mAccuseMailName;

	public :
		static char *
			mSEDPath;
};

#endif
