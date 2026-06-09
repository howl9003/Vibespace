#if !defined(__ARCHSPACE_SCRIPT_H__)
#define __ARCHSPACE_SCRIPT_H__

#include "util.h"

class CTechTable;
class CRaceTable;
class CProjectTable;
class CClusterNameList;
class CAdmiralNameTable;
class CSpyTable;
class CEventTable;
class CPersonalEndingTable;
class CShipDesignList;

/**
*/
class CTechScript: public CScript
{
	public:
		virtual ~CTechScript() {}

		bool get(CTechTable *aTechTable);
};

/**
*/
class CRaceScript: public CScript
{
	public:
		virtual ~CRaceScript() {}

		bool get(CRaceTable *aRaceTable);
};

/**
*/
class CProjectScript: public CScript
{
	public:
		virtual ~CProjectScript() {}

		bool get(CProjectTable *aProjectTable);
};

/**
*/
class CClusterNameScript: public CScript
{
	public:
		virtual ~CClusterNameScript() {}

		bool get(CClusterNameList *aNameList);
};

class CAdmiralNameScript: public CScript
{
	public:
		virtual ~CAdmiralNameScript() {}

		bool get(CAdmiralNameTable *aAdmiralNameTable);
};

class CSpyScript: public CScript
{
	public:
		virtual ~CSpyScript() {};

		bool get(CSpyTable *aSpyTable);
};

class CEventScript: public CScript
{
	public:
		virtual ~CEventScript() {};

		bool get(CEventTable *aEventTable);
};

class CPersonalEndingScript: public CScript
{
	public:
		virtual ~CPersonalEndingScript() {}
		bool get(CPersonalEndingTable *aPersonalEndingTable);
};

class CEmpireShipDesignScript: public CScript
{
	public:
		virtual ~CEmpireShipDesignScript() {};

		bool get(CShipDesignList *aList);
};

#endif
