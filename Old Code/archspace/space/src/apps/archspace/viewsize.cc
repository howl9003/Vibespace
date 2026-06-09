#include <cstdio>
#include "archspace.h"
#include "component.h"
#include "action.h"
#include "council.h"
#include "script.h"
#include "banner.h"
#include "battle.h"
#include "encyclopedia.h"
#include "game.h"
#include "race.h"

int main()
{
	printf("--------------------Game Server Class Size-----------------\n");
	printf("class CArchspace: %d Bytes\n", 
			sizeof(CArchspace));
	printf("class CArchspacePageStation: %d Bytes\n", 
			sizeof(CArchspacePageStation));
	printf("class CArchspaceTriggerStation: %d Bytes\n", 
			sizeof(CArchspaceTriggerStation));
	printf("class CArchspaceDatabase: %d Bytes\n", 
			sizeof(CArchspaceDatabase));
	printf("class CArchspaceConnection: %d Bytes\n", 
			sizeof(CArchspaceConnection));



	printf("class CArmor: %d Bytes\n", 
			sizeof(CArmor));
	
	printf("class CAction: %d Bytes\n", 
			sizeof(CAction));
	printf("class CActionList: %d Bytes\n", 
			sizeof(CActionList));

	printf("class CAdmission: %d Bytes\n", 
			sizeof(CAdmission));
	printf("class CAdmissionList: %d Bytes\n", 
			sizeof(CAdmissionList));
	

	printf("class CAdmiral: %d Bytes\n", 
			sizeof(CAdmiral));
	printf("class CAdmiralList: %d Bytes\n", 
			sizeof(CAdmiralList));
	printf("class CAdmiralNameList: %d Bytes\n", 
			sizeof(CAdmiralNameList));
	printf("class CAdmiralNameTable: %d Bytes\n", 
			sizeof(CAdmiralNameTable));
	printf("class CAdmiralNameScript: %d Bytes\n", 
			sizeof(CAdmiralNameScript));

	printf("class CBanner: %d Bytes\n", 
			sizeof(CBanner));
	printf("class CBannerList: %d Bytes\n", 
			sizeof(CBannerList));
	printf("class CBannerCenter: %d Bytes\n", 
			sizeof(CBannerCenter));


	printf("class CBattle: %d Bytes\n", 
			sizeof(CBattle));
	printf("class CBattleRecord: %d Bytes\n", 
			sizeof(CBattleRecord));
	printf("class CBattleRecordTable: %d Bytes\n", 
			sizeof(CBattleRecordTable));

	printf("class CBattleFleet: %d Bytes\n", 
			sizeof(CBattleFleet));
	printf("class CBattleFleetList: %d Bytes\n", 
			sizeof(CBattleFleetList));





	printf("class CCluster: %d Bytes\n", 
			sizeof(CCluster));
	printf("class CClusterNameScript: %d Bytes\n", 
			sizeof(CClusterNameScript));
	

	printf("class CComponent: %d Bytes\n", 
			sizeof(CComponent));
	printf("class CComponentList: %d Bytes\n", 
			sizeof(CComponentList));
	printf("class CComponentTable: %d Bytes\n", 
			sizeof(CComponentTable));
	printf("class CComponentScript: %d Bytes\n", 
			sizeof(CComponentScript));


	printf("class CComputer: %d Bytes\n", 
			sizeof(CComputer));
	printf("class CControlModel: %d Bytes\n", 
			sizeof(CControlModel));




	printf("class CCouncil: %d Bytes\n", 
			sizeof(CCouncil));
	printf("class CCouncilList: %d Bytes\n", 
			sizeof(CCouncilList));
	printf("class CCouncilTable: %d Bytes\n", 
			sizeof(CCouncilTable));

	printf("class CCouncilAction: %d Bytes\n", 
			sizeof(CCouncilAction));

	printf("class CCouncilActionList: %d Bytes\n", 
			sizeof(CCouncilActionList));

	printf("class CCouncilActionTable: %d Bytes\n", 
			sizeof(CCouncilActionTable));


	printf("class CCouncilActionBreakAlly: %d Bytes\n", 
			sizeof(CCouncilActionBreakAlly));
	printf("class CCouncilActionBreakPact: %d Bytes\n", 
			sizeof(CCouncilActionBreakPact));
	printf("class CCouncilActionDeclareTotalWar: %d Bytes\n", 
			sizeof(CCouncilActionDeclareTotalWar));
	printf("class CCouncilActionDeclareWar: %d Bytes\n", 
			sizeof(CCouncilActionDeclareWar));
	printf("class CCouncilActionImproveRelation: %d Bytes\n", 
			sizeof(CCouncilActionImproveRelation));
	printf("class CCouncilRelation: %d Bytes\n", 
			sizeof(CCouncilRelation));
	printf("class CCouncilRelationList: %d Bytes\n", 
			sizeof(CCouncilRelationList));
	printf("class CCouncilRelationTable: %d Bytes\n", 
			sizeof(CCouncilRelationTable));






	printf("class CCouncilMessage: %d Bytes\n", 
			sizeof(CCouncilMessage));
	printf("class CCouncilMessageBox: %d Bytes\n", 
			sizeof(CCouncilMessageBox));


	printf("class CDefenseFleet: %d Bytes\n", 
			sizeof(CDefenseFleet));
	printf("class CDefenseFleetList: %d Bytes\n", 
			sizeof(CDefenseFleetList));
	printf("class CDefensePlan: %d Bytes\n", 
			sizeof(CDefensePlan));
	printf("class CDefensePlanList: %d Bytes\n", 
			sizeof(CDefensePlanList));


	printf("class CDevice: %d Bytes\n", 
			sizeof(CDevice));

	printf("class CDiplomaticMessage: %d Bytes\n", 
			sizeof(CDiplomaticMessage));
	printf("class CDiplomaticMessageBox: %d Bytes\n", 
			sizeof(CDiplomaticMessageBox));




	printf("class CEncyclopedia: %d Bytes\n", 
			sizeof(CEncyclopedia));
	printf("class CEncyclopediaTechIndex: %d Bytes\n", 
			sizeof(CEncyclopediaTechIndex));
	printf("class CEncyclopediaTechPage: %d Bytes\n", 
			sizeof(CEncyclopediaTechPage));



	printf("class CEngine: %d Bytes\n", 
			sizeof(CEngine));

	printf("class CFleet: %d Bytes\n", 
			sizeof(CFleet));
	printf("class CFleetList: %d Bytes\n", 
			sizeof(CFleetList));

	printf("class CGame: %d Bytes\n", 
			sizeof(CGame));

	printf("class CKnownTech: %d Bytes\n", 
			sizeof(CKnownTech));
	printf("class CKnownTechList: %d Bytes\n", 
			sizeof(CKnownTechList));






	printf("class CMission: %d Bytes\n", 
			sizeof(CMission));

	printf("class CNews: %d Bytes\n", 
			sizeof(CNews));

	printf("class CNewsCenter: %d Bytes\n", 
			sizeof(CNewsCenter));

	printf("class CKnownTechNews: %d Bytes\n", 
			sizeof(CKnownTechNews));

	printf("class CPlanetNews: %d Bytes\n", 
			sizeof(CPlanetNews));

	printf("class CPlanetNewsCenter: %d Bytes\n", 
			sizeof(CPlanetNewsCenter));


	printf("class CPurchasedProjectNews: %d Bytes\n", 
			sizeof(CPurchasedProjectNews));

	printf("class CAdmiralNews: %d Bytes\n", 
			sizeof(CAdmiralNews));


	printf("class CPlanet: %d Bytes\n", 
			sizeof(CPlanet));
	printf("class CPlanetList: %d Bytes\n", 
			sizeof(CPlanetList));
	printf("class CPlanetTable: %d Bytes\n", 
			sizeof(CPlanetTable));



	printf("class CPlayer: %d Bytes\n", 
			sizeof(CPlayer));

	printf("class CPlayerList: %d Bytes\n", 
			sizeof(CPlayerList));

	printf("class CPlayerTable: %d Bytes\n", 
			sizeof(CPlayerTable));


	printf("class CPlayerAction: %d Bytes\n", 
			sizeof(CPlayerAction));

	printf("class CPlayerActionList: %d Bytes\n", 
			sizeof(CPlayerActionList));

	printf("class CPlayerActionTable: %d Bytes\n", 
			sizeof(CPlayerActionTable));

	printf("class CPlayerActionCouncilDonation: %d Bytes\n", 
			sizeof(CPlayerActionCouncilDonation));
	printf("class CPlayerActionBreakAllay: %d Bytes\n", 
			sizeof(CPlayerActionBreakAlly));
	printf("class CPlayerActionBreakPact: %d Bytes\n", 
			sizeof(CPlayerActionBreakPact));

	printf("class CPlayerRelation: %d Bytes\n", 
			sizeof(CPlayerRelation));
	printf("class CPlayerRelationList: %d Bytes\n", 
			sizeof(CPlayerRelationList));
	printf("class CPlayerRelationTable: %d Bytes\n", 
			sizeof(CPlayerRelationTable));






	printf("class CPProject: %d Bytes\n", 
			sizeof(CProject));
	printf("class CPProjectTable: %d Bytes\n", 
			sizeof(CProjectTable));
	printf("class CPProjectScript: %d Bytes\n", 
			sizeof(CProjectScript));




	printf("class CPurchasedProject: %d Bytes\n", 
			sizeof(CPurchasedProject));



	printf("class CRace: %d Bytes\n", 
			sizeof(CRace));
	printf("class CRaceTable: %d Bytes\n", 
			sizeof(CRaceTable));
	printf("class CRaceScript: %d Bytes\n", 
			sizeof(CRaceScript));



	printf("class CRelation: %d Bytes\n", 
			sizeof(CRelation));
	printf("class CRepairBay: %d Bytes\n", 
			sizeof(CRepairBay));

	printf("class CResource: %d Bytes\n", 
			sizeof(CResource));


	printf("class CShield: %d Bytes\n", 
			sizeof(CShield));

	printf("class CShipSize: %d Bytes\n", 
			sizeof(CShipSize));
	printf("class CShipSizeList: %d Bytes\n", 
			sizeof(CShipSizeList));
	printf("class CShipSizeTable: %d Bytes\n", 
			sizeof(CShipSizeTable));
	printf("class CShipScript: %d Bytes\n", 
			sizeof(CShipScript));
	printf("class CShipDesign: %d Bytes\n", 
			sizeof(CShipDesign));
	printf("class CShipDesignList: %d Bytes\n", 
			sizeof(CShipDesignList));
	printf("class CShipToBuild: %d Bytes\n", 
			sizeof(CShipToBuild));
	printf("class CShipBuildQ: %d Bytes\n", 
			sizeof(CShipBuildQ));
	printf("class CDockedShip: %d Bytes\n", 
			sizeof(CDockedShip));
	printf("class CDock: %d Bytes\n", 
			sizeof(CDock));
	printf("class CDamagedShip: %d Bytes\n", 
			sizeof(CDamagedShip));

	printf("class CTech: %d Bytes\n", 
			sizeof(CTech));
	printf("class CTechTable: %d Bytes\n", 
			sizeof(CTechTable));
	printf("class CTechScript: %d Bytes\n", 
			sizeof(CTechScript));

	printf("class CTurret: %d Bytes\n", 
			sizeof(CTurret));

	printf("class CUniverse: %d Bytes\n", 
			sizeof(CUniverse));


	printf("class CVector: %d Bytes\n", 
			sizeof(CVector));

	printf("class CWeapon: %d Bytes\n", 
			sizeof(CWeapon));

	printf("--------------------Library Class Size-----------------\n");

	printf("class CPage: %d Bytes\n", 
			sizeof(CPage));
	printf("class CPageStation: %d Bytes\n", 
			sizeof(CPageStation));
	printf("class CConnection: %d Bytes\n", 
			sizeof(CConnection));
	printf("class CQuery: %d Bytes\n", 
			sizeof(CQuery));
	printf("class CQueryList: %d Bytes\n", 
			sizeof(CQueryList));
	printf("class CHTML: %d Bytes\n", 
			sizeof(CHTML));
	printf("class CHTMLStation: %d Bytes\n", 
			sizeof(CHTMLStation));
	printf("class CCGIServer: %d Bytes\n", 
			sizeof(CCGIServer));
	printf("class CFileHTML: %d Bytes\n", 
			sizeof(CFileHTML));
	printf("class CBase: %d Bytes\n", 
			sizeof(CBase));
	printf("class CDatabase: %d Bytes\n", 
			sizeof(CDatabase));
	printf("class CApplication: %d Bytes\n", 
			sizeof(CApplication));
	printf("class CKeyServer: %d Bytes\n", 
			sizeof(CKeyServer));
	printf("class CCryptConnection: %d Bytes\n", 
			sizeof(CCryptConnection));
	printf("class CMessage: %d Bytes\n", 
			sizeof(CMessage));
	printf("class CPacket: %d Bytes\n", 
			sizeof(CPacket));
	printf("class CSocket: %d Bytes\n", 
			sizeof(CSocket));
	printf("class CFIFO: %d Bytes\n", 
			sizeof(CFIFO));
	printf("class CClient: %d Bytes\n", 
			sizeof(CClient));
	printf("class CServer: %d Bytes\n", 
			sizeof(CServer));
	printf("class CLoopServer: %d Bytes\n", 
			sizeof(CLoopServer));
	printf("class CIPList: %d Bytes\n", 
			sizeof(CIPList));
	printf("class CTrigger: %d Bytes\n", 
			sizeof(CTrigger));
	printf("class CTriggerStation: %d Bytes\n", 
			sizeof(CTriggerStation));
	printf("class CStore: %d Bytes\n", 
			sizeof(CStore));
	printf("class CSQL: %d Bytes\n", 
			sizeof(CSQL));
	printf("class CStoreCenter: %d Bytes\n", 
			sizeof(CStoreCenter));
	printf("class CList: %d Bytes\n", 
			sizeof(CList));
	printf("class CSortedList: %d Bytes\n", 
			sizeof(CSortedList));
	printf("class CString: %d Bytes\n", 
			sizeof(CString));
	printf("class CNode: %d Bytes\n", 
			sizeof(CNode));
	printf("class CCollection: %d Bytes\n", 
			sizeof(CCollection));
	printf("class CStringList: %d Bytes\n", 
			sizeof(CStringList));
	printf("class CIntegerList: %d Bytes\n", 
			sizeof(CIntegerList));
	printf("class CIniFile: %d Bytes\n", 
			sizeof(CIntegerList));
	printf("class CCommandSet: %d Bytes\n", 
			sizeof(CIntegerList));
	printf("class CMySQL: %d Bytes\n", 
			sizeof(CMySQL));
	printf("class CScript: %d Bytes\n", 
			sizeof(CScript));

	return 0;
}
