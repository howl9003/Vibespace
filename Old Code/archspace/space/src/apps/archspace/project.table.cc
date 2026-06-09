#include "project.h"

CProjectTable::CProjectTable()
{
}

CProjectTable::~CProjectTable()
{
	remove_all();
}

bool
CProjectTable::free_item(TSomething aItem)
{
	CProject
		*Project = (CProject*)aItem;

	if (!Project) return false;

	delete Project;

	return true;
}

int
CProjectTable::compare(TSomething aItem1, TSomething aItem2) const
{
	CProject
		*Project1 = (CProject *)aItem1,
		*Project2 = (CProject *)aItem2;

	if (Project1->get_id() > Project2->get_id()) return 1;
	if (Project1->get_id() < Project2->get_id()) return -1;
	return 0;
}

int
CProjectTable::compare_key(TSomething aItem, TConstSomething aKey) const
{
	CProject
		*Project = (CProject *)aItem;

	if (Project->get_id() > (int)aKey) return 1;
	if (Project->get_id() < (int)aKey) return -1;
	return 0;
}

int
CProjectTable::add_project(CProject *aProject)
{
	if (!aProject) return -1;

	if (find_sorted_key((TConstSomething)aProject->get_id()) >= 0) return -1;
	insert_sorted(aProject);

	return aProject->get_id();
}

bool
CProjectTable::remove_project(int aProjectID)
{
	int
		Index;

	Index = find_sorted_key((void*)aProjectID);
	if (Index < 0) return false;

	return CSortedList::remove(Index);
}

CProject *
CProjectTable::get_by_id(int aProjectID)
{
	if (!length()) return NULL;

	int
		Index;

	Index = find_sorted_key((void*)aProjectID);

	if (Index < 0) return NULL;

	return (CProject*)get(Index);
}

