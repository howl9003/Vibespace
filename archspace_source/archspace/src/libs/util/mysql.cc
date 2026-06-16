#include "util.h"
#include <cstdlib>
#include <cstring>

TZone gMySQLZone = 
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CMySQL),
	0,
	0,
	NULL,   
	"Zone CMySQL"
};

CMySQL::CMySQL()
{
	mMySQL = NULL;
	mHost[0] = mUser[0] = mPassword[0] = mDatabase[0] = '\0';
}

CMySQL::~CMySQL()
{
	close();
}


bool
CMySQL::open(const char *aHost, const char *aUser,
		const char *aPassword, const char *aDatabase)
{
	if (mMySQL) return false;
//	mMySQL = (MYSQL*)malloc(sizeof(MYSQL));
	mMySQL = mysql_init(NULL);

	// Retain the connection parameters so query() can transparently
	// reconnect if the server drops an idle link (wait_timeout -> 2006).
	strncpy(mHost,     aHost     ? aHost     : "", sizeof(mHost) - 1);
	strncpy(mUser,     aUser     ? aUser     : "", sizeof(mUser) - 1);
	strncpy(mPassword, aPassword ? aPassword : "", sizeof(mPassword) - 1);
	strncpy(mDatabase, aDatabase ? aDatabase : "", sizeof(mDatabase) - 1);
	mHost[sizeof(mHost) - 1]         = '\0';
	mUser[sizeof(mUser) - 1]         = '\0';
	mPassword[sizeof(mPassword) - 1] = '\0';
	mDatabase[sizeof(mDatabase) - 1] = '\0';

	int
		Count = 0;

	while(Count < 3)
	{
		if (mysql_real_connect(mMySQL, aHost, aUser, aPassword, aDatabase, 0, NULL, 0)) break;
		Count++;
		pth_nap((pth_time_t){2, 0});
	}

	if (Count == 3)
	{
		system_log("Failed to connect to game database: Error: %s",
		mysql_error(mMySQL));
//		free(mMySQL);
		mysql_close(mMySQL);
		mMySQL = NULL;
		return false;
	}

	return true;
}

void
CMySQL::close()
{
	if (mMySQL)
	{
		mysql_close(mMySQL);
//		free(mMySQL);
		mMySQL = NULL;
		system_log("close mysql connection");
	}
}


// Re-establish a link the server has closed (idle wait_timeout etc.), reusing
// the parameters retained at open(). Same 3-try/2s-nap policy as open().
bool
CMySQL::reconnect()
{
	if (mMySQL)
	{
		mysql_close(mMySQL);
		mMySQL = NULL;
	}

	mMySQL = mysql_init(NULL);
	if (!mMySQL) return false;

	int
		Count = 0;

	while (Count < 3)
	{
		if (mysql_real_connect(mMySQL, mHost, mUser, mPassword,
				mDatabase, 0, NULL, 0))
		{
			system_log("reconnected to game database");
			return true;
		}
		Count++;
		pth_nap((pth_time_t){2, 0});
	}

	system_log("reconnect to game database failed: %s", mysql_error(mMySQL));
	mysql_close(mMySQL);
	mMySQL = NULL;
	return false;
}


int
CMySQL::query(const char *aQuery)
{
	if (!mMySQL) return -1;

	int
		Res = mysql_query(mMySQL, aQuery);

	if (Res != 0)
	{
		// 2006 CR_SERVER_GONE_ERROR / 2013 CR_SERVER_LOST: the server closed
		// our (idle) connection. Re-establish it and retry the query once, so
		// a wait_timeout drop can't wedge every page on a dead handle.
		unsigned int
			Err = mysql_errno(mMySQL);
		if (Err == 2006 || Err == 2013)
		{
			system_log("db connection lost (errno %u); reconnecting", Err);
			if (reconnect())
			{
				Res = mysql_query(mMySQL, aQuery);
				if (Res == 0) return Res;
			}
		}

		system_log("query failed: %s", aQuery);
		system_log("error string[%d]: %s", mysql_errno(mMySQL), mysql_error(mMySQL));
	}
	return Res;
}
