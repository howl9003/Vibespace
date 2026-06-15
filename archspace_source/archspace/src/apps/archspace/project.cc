#include "project.h"
#include "define.h"
#include <libintl.h>
#include "race.h"
#include "util.h"

// QoL: a plain-ish effect summary for hover tooltips on the project lists.
// Reuses the engine's own control-model rendering (get_effects_string_for_html)
// and appends the special effects (abilities, spy defence, etc.) the same way
// the domestic project page does. May contain "<BR>"; the tooltip JS turns
// those into line breaks. Returns "" for a project with no readable effect.
char *
CProject::get_effect_tip()
{
	static CString Tip;
	Tip.clear();

	char *cm = mEffect.get_effects_string_for_html();
	if (cm && *cm) Tip = cm;

	for (int j = 0; j < mEffectList.length(); j++)
	{
		CPlayerEffect *pe = (CPlayerEffect *)mEffectList.get(j);
		if (!pe) continue;

		if (Tip.length()) Tip += "<BR>\n";

		if (pe->get_type() == CPlayerEffect::PA_GAIN_ABILITY)
			Tip.format("%s", index_to_ability_name(pe->get_argument1()));
		else if (pe->get_type() == CPlayerEffect::PA_LOSE_ABILITY)
			Tip.format("%s: %s", CPlayerEffect::get_type_name(pe->get_type()),
					index_to_ability_name(pe->get_argument1()));
		else
		{
			Tip.format("%s: ", CPlayerEffect::get_type_name(pe->get_type()));
			if (pe->get_argument1() > 0) Tip += "+";
			Tip.format("%s", dec2unit(pe->get_argument1()));
			if (pe->get_apply() != CPlayerEffect::APPLY_ABSOLUTE) Tip += "%";
		}
	}

	return (char *)Tip;
}

TZone gProjectZone =
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CProject),
	0,
	0,
	NULL,
	"Zone CProject"
};

static char * mProjectType[] =
{
	"Planet",
	"Fixed",
	"Council",
	"Ending",
	"Secret",
	"BM"
};

CProject::CProject()
{
	mID = 0;
	mName = "";
	mDescription = "";
}

CProject::~CProject()
{
	//if (mPrerequisiteList) mPrerequisiteList->clear();
}

char *
CProject::get_type_name()
{
	return get_type_name(mType);
}

char *
CProject::get_type_name(int aProjectType)
{
	switch (aProjectType)
	{
		case TYPE_PLANET :
			return GETTEXT("Planet Based");

		case CProject::TYPE_FIXED :
			return GETTEXT("Domain Ranged");

		case CProject::TYPE_COUNCIL :
			return GETTEXT("Council");
			
		case CProject::TYPE_ENDING :
			return GETTEXT("Ending");

		case CProject::TYPE_SECRET :
			return GETTEXT("Secret");
			
		case CProject::TYPE_BM :
			return GETTEXT("Black Market");

		default :
			return NULL;
	}
}

bool
CProject::set_effect(char *aEffectName, int aValue)
{
	if (!strcasecmp( aEffectName, "Environment" ) ||
			!strcasecmp( aEffectName, "Growth" ) ||
			!strcasecmp( aEffectName, "Research" ) ||
			!strcasecmp( aEffectName, "Production" ) ||
			!strcasecmp( aEffectName, "Military" ) ||
			!strcasecmp( aEffectName, "Spy" ) ||
			!strcasecmp( aEffectName, "Commerce" ) ||
			!strcasecmp( aEffectName, "Efficiency" ) ||
			!strcasecmp( aEffectName, "Genius" ) ||
			!strcasecmp( aEffectName, "Diplomacy" ) ||
			!strcasecmp( aEffectName, "Facility Cost" ) )
	{
		mEffect.set(aEffectName, aValue);
		return true;
	} 

	return false;
}

bool
CProject::set_type(char *aType)
{
	for (int i = 0 ; i < TYPE_MAX ; i++)
	{
		if (!(strcasecmp(aType, mProjectType[i])))
		{
			mType = (EProjectType)i;
			return true;
		}
	}

	return false;
}

bool
CProject::add_effect(CPlayerEffect *aEffect)
{
	if (!aEffect) return false;
	aEffect->set_id(mEffectList.get_new_id());
	return mEffectList.add_player_effect(aEffect);
}

