#include "ending.h"

CPersonalEndingTable::CPersonalEndingTable()
{
}

CPersonalEndingTable::~CPersonalEndingTable()
{
	remove_all();
}

bool
CPersonalEndingTable::free_item(TSomething aItem)
{
	CPersonalEnding *
		Ending = (CPersonalEnding *)aItem;
	if (Ending == NULL) return false;

	delete Ending;
	return true;
}

int
CPersonalEndingTable::compare(TSomething aItem1, TSomething aItem2) const
{
	CPersonalEnding
		*ending1 = (CPersonalEnding *)aItem1,
		*ending2 = (CPersonalEnding *)aItem2;

	if (ending1->get_id() > ending2->get_id()) return 1;
	if (ending1->get_id() < ending2->get_id()) return -1;

	return 0;
}

int
CPersonalEndingTable::compare_key(TSomething aItem, TConstSomething aKey) const
{
	CPersonalEnding *
		ending = (CPersonalEnding *)aItem;
	if (ending->get_id() > (int)aKey) return 1;
	if (ending->get_id() < (int)aKey) return -1;

	return 0;
}

int
CPersonalEndingTable::add_personal_ending(CPersonalEnding* aPersonalEnding)
{
	if (!aPersonalEnding)
	{
		return -1;
	}
	if (find_sorted_key((TConstSomething)aPersonalEnding->get_id()) >= 0)
	{
		return -1;
	}
	insert_sorted(aPersonalEnding);
	return aPersonalEnding->get_id();
}

bool
CPersonalEndingTable::remove_personal_ending(int aPersonalEndingID)
{
	int
		Index = find_sorted_key((void *)aPersonalEndingID);
	if (Index < 0) return false;

	return CSortedList::remove(Index);
}

CPersonalEnding *
CPersonalEndingTable::get_by_id(int aPersonalEndingID)
{
	if(!length())
	{
		return NULL;
	}
	int
		index;
	index = find_sorted_key((void *)aPersonalEndingID);
	if(index < 0)
	{
		return NULL;
	}
	return (CPersonalEnding *)get(index);
}
