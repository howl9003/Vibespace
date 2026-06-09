#if !defined(__ARCHSPACE_ENCYCLOPEDIA_H__)
#define __ARCHSPACE_ENCYCLOPEDIA_H__

#include "cgi.h"

class CTech;
class CRaceTable;
class CRace;
class CProject;
class CComponent;
class CShipSizeTable;
class CShipSize;
class CSpyTable;
class CSpy;

/**
*/
class CEncyclopedia: public CFileHTML
{
	public:
		virtual ~CEncyclopedia() {}

		bool read(const char *aPageName);
		bool write();

	protected:
		CQueryList
			mConversion;
};

/**
*/
class CEncyclopediaTechIndex: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaTechIndex() {}

		bool set(int aTechType);
};

/**
*/
class CEncyclopediaTechPage: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaTechPage() {}

		bool set(CTech *aTech);
};

/**
*/
class CEncyclopediaRaceIndex: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaRaceIndex() {}

		bool set(CRaceTable *aRaceTable);
};

/**
*/
class CEncyclopediaRacePage: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaRacePage() {}

		bool set(CRace *aRace);
};

/**
*/
class CEncyclopediaProjectIndex: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaProjectIndex() {}
		
		bool set(int aProject);
};

/**
*/
class CEncyclopediaProjectPage: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaProjectPage() {}

		bool set(CProject *aProject);
};

/* start telecard 2000/10/02 */
/**
*/
class CEncyclopediaComponentIndex: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaComponentIndex() {}
		bool set(int aComponentType);
};

/**
*/
class CEncyclopediaComponentPage: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaComponentPage() {}
		bool set(CComponent* aComponent);
};
/* end telecard 2000/10/02 */

/* start telecard 2000/10/05 */
/**
*/
class CEncyclopediaShipIndex: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaShipIndex() {}
		bool set(CShipSizeTable *aShipTable);
};

/**
*/
class CEncyclopediaShipPage: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaShipPage() {}
		bool set(CShipSize* aShip);
};
/* end telecard 2000/10/05 */

/**
*/
class CEncyclopediaSpyIndex: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaSpyIndex() {}
		bool set(CSpyTable *aSpyTable);
};

class CEncyclopediaSpyPage: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaSpyPage() {}
		bool set(CSpy *aSpy);
};

/**
*/
class CEncyclopediaTechIndexGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaTechIndexGame() {}

		bool set(int aTechType);
};

/**
*/
class CEncyclopediaTechPageGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaTechPageGame() {}

		bool set(CTech *aTech);
};

/**
*/
class CEncyclopediaRaceIndexGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaRaceIndexGame() {}

		bool set(CRaceTable *aRaceTable);
};

/**
*/
class CEncyclopediaRacePageGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaRacePageGame() {}

		bool set(CRace *aRace);
};

/**
*/
class CEncyclopediaProjectIndexGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaProjectIndexGame() {}
		
		bool set(int aProject);
};

/**
*/
class CEncyclopediaProjectPageGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaProjectPageGame() {}

		bool set(CProject *aProject);
};

/* start telecard 2000/10/02 */
/**
*/
class CEncyclopediaComponentIndexGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaComponentIndexGame() {}
		bool set(int aComponentType);
};

/**
*/
class CEncyclopediaComponentPageGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaComponentPageGame() {}
		bool set(CComponent* aComponent);
};
/* end telecard 2000/10/02 */

/* start telecard 2000/10/05 */
/**
*/
class CEncyclopediaShipIndexGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaShipIndexGame() {}
		bool set(CShipSizeTable *aShipTable);
};

/**
*/
class CEncyclopediaShipPageGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaShipPageGame() {}
		bool set(CShipSize* aShip);
};

/**
*/
class CEncyclopediaSpyIndexGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaSpyIndexGame() {}
		bool set(CSpyTable *aSpyTable);
};

class CEncyclopediaSpyPageGame: public CEncyclopedia
{
	public:
		virtual ~CEncyclopediaSpyPageGame() {}
		bool set(CSpy *aSpy);
};

#endif
