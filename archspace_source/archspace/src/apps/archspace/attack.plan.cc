#include "common.h"
#include "util.h"
#include "attack.plan.h"
#include <cstdlib>
#include "define.h"
#include <libintl.h>
#include "archspace.h"
#include "game.h"

TZone gAttackFleetZone =
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CAttackFleet),
	0,
	0,
	NULL,
	"Zone CAttackFleet"
};

TZone gAttackPlanZone =
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CAttackPlan),
	0,
	0,
	NULL,
	"Zone CAttackPlan"
};

CAttackFleet::CAttackFleet( MYSQL_ROW aRow )
{
	mOwner = atoi( aRow[FIELD_OWNER] );
	mPlanID = atoi( aRow[FIELD_PLAN_ID] );
	mFleetID = atoi( aRow[FIELD_FLEET_ID] );
	mCommand = atoi( aRow[FIELD_FLEET_COMMAND] );
	mX = atoi( aRow[FIELD_FLEET_X] );
	mY = atoi( aRow[FIELD_FLEET_Y] );
}

CString &
CAttackFleet::query()
{
	static CString
		Query;
	Query.clear();

	switch(type())
	{
		case QUERY_INSERT:
			Query.format( "INSERT INTO attack_fleet ( owner, plan_id, fleet_id, command, x, y ) VALUES ( %d, %d, %d, %d, %d, %d )", mOwner, mPlanID, mFleetID, mCommand, mX, mY );
			break;
		case QUERY_UPDATE:
			Query.format("UPDATE attack_fleet SET x = %d, y = %d", mX, mY);

#define STORE(x, y, z) \
			if (mStoreFlag.has(x)) \
				Query.format(y, z)

			STORE(STORE_OWNER, ", owner = %d", mOwner);
			STORE(STORE_PLAN_ID, ", plan_id = %d", mPlanID);
			STORE(STORE_FLEET_ID, ", fleet_id = %d", mFleetID);
			STORE(STORE_COMMAND, ", command = %d", mCommand);

			break;

		case QUERY_DELETE:
			Query.format( "DELETE FROM attack_fleet WHERE owner = %d AND plan_id = %d AND fleet_id = %d", mOwner, mPlanID, mFleetID );
			break;
	}

	mStoreFlag.clear();

	return Query;
}

CStoreCenter&
operator<<(CStoreCenter& aStoreCenter, CAttackFleet& aFleet)
{
	aStoreCenter.store( aFleet );
	return aStoreCenter;
}

bool
CAttackFleetList::free_item(TSomething aItem)
{
	CAttackFleet *
		AttackFleet = (CAttackFleet *)aItem;
	if (!AttackFleet) return false;

	delete AttackFleet;
	return true;
}

int
CAttackFleetList::compare(TSomething aItem1, TSomething aItem2) const
{
	CAttackFleet *
		Fleet1 = (CAttackFleet *)aItem1;
	CAttackFleet *
		Fleet2 = (CAttackFleet *)aItem2;

	if (Fleet1->get_fleet_id() > Fleet2->get_fleet_id()) return 1;
	if (Fleet1->get_fleet_id() < Fleet2->get_fleet_id()) return -1;
	return 0;
}

int
CAttackFleetList::compare_key(TSomething aItem, TConstSomething aKey) const
{
	CAttackFleet *
		Fleet = (CAttackFleet *)aItem;

	if (Fleet->get_fleet_id() > (int)aKey) return 1;
	if (Fleet->get_fleet_id() < (int)aKey) return -1;
	return 0;
}

bool
CAttackFleetList::remove_attack_fleet(int aFleetID)
{
	int
		Index = find_sorted_key((void *)aFleetID);
	if (Index < 0) return false;

	return CSortedList::remove(Index);
}

bool
CAttackFleetList::remove_without_free_attack_fleet(int aFleetID)
{
	int
		Index = find_sorted_key((void *)aFleetID);
	if (Index < 0) return false;

	return CSortedList::remove_without_free(Index);
}

int
CAttackFleetList::add_attack_fleet(CAttackFleet *aFleet)
{
	if (!aFleet) return -1;

	if (find_sorted_key( (TConstSomething)aFleet->get_fleet_id() ) >= 0)
		return -1;
	insert_sorted(aFleet);
	return aFleet->get_fleet_id();
}

CAttackFleet *
CAttackFleetList::get_by_id(int aFleetID)
{
	if ( !length() ) return NULL;
	int
		Index = find_sorted_key( (void *)aFleetID );
	if (Index < 0) return NULL;
	return (CAttackFleet *)get(Index);
}

CAttackPlan::CAttackPlan()
{
	mOwner = -1;
	mID = -1;
	mName = "";
	mCapital = 0;
}

CAttackPlan::CAttackPlan( MYSQL_ROW aRow)
{
	mOwner = atoi( aRow[FIELD_OWNER] );
	mID = atoi( aRow[FIELD_ID] );
	mName = aRow[FIELD_NAME];
	mCapital = atoi( aRow[FIELD_CAPITAL] );
}

bool
CAttackPlan::add_attack_fleet(CAttackFleet *aFleet)
{
	if (mFleetList.length() > 20) return false;

	if (mFleetList.add_attack_fleet(aFleet) < 1) return false;
	return true;
}

bool
CAttackPlan::remove_attack_fleet(int aIndex)
{
	return mFleetList.CSortedList::remove(aIndex);
}

int
CAttackPlan::get_fleets_number()
{
	return mFleetList.length();
}

CString &
CAttackPlan::query()
{
	static CString
		Query;
	Query.clear();

	switch( type() ) {
		case QUERY_INSERT:
			if (add_slashes((char *)mName).length() == 0)
			{
				Query.format( "INSERT INTO attack_plan ( owner, id, name, capital ) VALUES ( %d, %d, '', %d )",
								mOwner, mID, mCapital );
			}
			else
			{
				Query.format( "INSERT INTO attack_plan ( owner, id, name, capital ) VALUES ( %d, %d, '%s', %d )",
								mOwner, mID, (char *)add_slashes((char *)mName), mCapital );
			}

			break;
		case QUERY_UPDATE:
			Query.format("UPDATE attack_plan SET capital = %d", mCapital);

#define STORE(x, y, z) \
			if (mStoreFlag.has(x)) \
				Query.format(y, z)

			STORE(STORE_OWNER, ", owner = %d", mOwner);
			STORE(STORE_ID, ", id = %d", mID);
			STORE(STORE_NAME, ", name = '%s'", (char *)add_slashes((char *)mName));
			Query.format(" WHERE owner = %d AND id = %d", mOwner, mID);

			break;

		case QUERY_DELETE:
			Query.format( "DELETE FROM attack_plan WHERE owner = %d AND id = %d", mOwner, mID );
			break;
	}

	mStoreFlag.clear();

	return Query;
}

CStoreCenter&
operator<<(CStoreCenter& aStoreCenter, CAttackPlan& aPlan)
{
	aStoreCenter.store( aPlan );
	return aStoreCenter;
}

bool
CAttackPlanList::free_item(TSomething aItem)
{
	CAttackPlan *
		AttackPlan = (CAttackPlan *)aItem;
	if (!AttackPlan) return false;

	delete AttackPlan;
	return true;
}

int
CAttackPlanList::compare( TSomething aItem1, TSomething aItem2 ) const
{
	CAttackPlan
		*plan1 = (CAttackPlan*)aItem1,
		*plan2 = (CAttackPlan*)aItem2;

	if ( plan1->get_id() > plan2->get_id() ) return 1;
	if ( plan1->get_id() < plan2->get_id() ) return -1;
	return 0;
}

int
CAttackPlanList::compare_key( TSomething aItem, TConstSomething aKey ) const
{
	CAttackPlan
		*plan = (CAttackPlan*)aItem;

	if ( plan->get_id() > (int)aKey ) return 1;
	if ( plan->get_id() < (int)aKey ) return -1;
	return 0;
}

bool
CAttackPlanList::remove_attack_plan(int aPlanID)
{
	int
		index;
	index = find_sorted_key( (void*)aPlanID );
	if ( index < 0 ) return false;

	return CSortedList::remove(index);
}

int
CAttackPlanList::add_attack_plan(CAttackPlan *aPlan)
{
	if (!aPlan) return -1;

	if (find_sorted_key( (TConstSomething)aPlan->get_id() ) >= 0)
		return -1;
	insert_sorted( aPlan );
	return aPlan->get_id();
}

CAttackPlan *
CAttackPlanList::get_by_id(int aPlanID)
{
	if ( !length() ) return NULL;
	int
		index;
	index = find_sorted_key( (void*)aPlanID );
	if ( index<0 ) return NULL;
	return (CAttackPlan*)get(index);
}

int
CAttackPlanList::get_new_id()
{
	int
		ID = 1;

	while(find_sorted_key((void *)ID) >= 0) ID++;

	return ID;
}

char *
CAttackPlanList::deploy_board_blob()
{
	static CString
		Blob;
	Blob.clear();

	for (int i=0 ; i<length() ; i++)
	{
		CAttackPlan *
			Plan = (CAttackPlan *)get(i);

		// is_valid_name() permits '<', so neutralise it here: the name is emitted
		// into a <script> blob on the deploy board and a literal '<' could break
		// out of the element. (Newlines/tabs are already rejected by the name
		// validator, and the picker shows the name via textContent, which is safe.)
		CString
			SafeName;
		SafeName.clear();
		const char *
			Name = Plan->get_name();
		for (int c=0 ; Name && Name[c] ; c++)
		{
			char
				Pair[2];
			Pair[0] = (Name[c] == '<') ? '(' : Name[c];
			Pair[1] = '\0';
			SafeName += Pair;
		}

		Blob.format("T%d|%s\n", Plan->get_id(), (char *)SafeName);

		CAttackFleetList *
			Fleets = Plan->get_fleet_list();
		for (int j=0 ; j<Fleets->length() ; j++)
		{
			CAttackFleet *
				Fleet = (CAttackFleet *)Fleets->get(j);
			Blob.format("F%d|%d|%d|%d\n",
						Fleet->get_fleet_id(), Fleet->get_command(),
						Fleet->get_x(), Fleet->get_y());
		}
	}

	return (char *)Blob;
}

/* end of file attack.plan.cc */
