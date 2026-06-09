#include "common.h"
#include "util.h"
#include "database.h"
#include "store.h"

TZone gSQLZone = 
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CSQL),
	0,
	0,
	NULL,   
	"Zone CSQL",
};

CSQL::CSQL()
{
	mCount = 0;
}

CSQL::~CSQL()
{
}

void
CSQL::set_table(const char *aTable)
{
	mTable.clear();
	mTable.append(aTable);
}

void 
CSQL::set_query(const char *aQuery)
{
	mQuery.clear();
	mQuery.append(aQuery);
}
