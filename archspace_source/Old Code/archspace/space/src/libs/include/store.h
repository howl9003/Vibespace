#if !defined(__ARCHSPACE_STORE_H__)
#define __ARCHSPACE_STORE_H__

#include "common.h"
#include "util.h"
#include "net.h"

extern TZone gSQLZone;
extern TZone gStoreCenterZone;

#define MESSAGE_SQL_SEND		0x7001
#define MESSAGE_PUSH_REQUEST	0x7003
#define QUERY_NONE 				0
#define QUERY_INSERT	 		1
#define QUERY_UPDATE			2
#define QUERY_DELETE			3

/**
*/
class CStore: public CBase
{
	public:
		CStore();
		virtual ~CStore();

		virtual const char *table() = 0;

		virtual CString &query() = 0;

		int type(int aType = -1);

		bool is_changed();

	/* For Test */
	public:
		bool set_all_changed();

	protected:
		int
			mQueryType;
		CCommandSet
			mStoreFlag;
};

/**
*/
class CSQL: public CBase
{
	public:
		CSQL();
		virtual ~CSQL();

		void set_table(const char *aTable);
		void set_query(const char *aQuery);
		void set_count(unsigned int aCount) { mCount = aCount; }

		const char* get_table() { return mTable.get_data(); }
		const char* get_query() { return mQuery.get_data(); }
		unsigned int get_count() { return mCount; }

	protected:
		unsigned int 
			mCount;

		CString 
			mTable,
			mQuery;

	RECYCLE(gSQLZone);
};

class CStoreCenter: public CSortedList, public CFIFO
{
	public:
		CStoreCenter();
		virtual ~CStoreCenter();

		bool initialize(const char* aFIFOName);

		bool store(CStore &aStore);

		int push();
		int send();

		virtual int read();
		virtual int write();

		bool query(const char *aTable, const char *aQuery);
	protected:
		CCollection
			mOutput;

		virtual bool free_item(TSomething aItem);
		virtual int compare(TSomething aItem1, TSomething aItem2) const;
		virtual int compare_key(TSomething aItem, TConstSomething aKey) const;

		void stack_queue(CSQL& aSQL);

		unsigned int count();

		virtual const char *debug_info() const { return "store center"; }

	RECYCLE(gStoreCenterZone);
};

#endif
