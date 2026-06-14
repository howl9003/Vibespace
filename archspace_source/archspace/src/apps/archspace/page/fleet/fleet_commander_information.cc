#include <libintl.h>
#include "../../pages.h"

bool
CPageFleetCommanderInformation::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name());

	QUERY("ADMIRAL_ID", AdmiralIDString);
	int
		AdmiralID = as_atoi(AdmiralIDString);
	CAdmiralList *
		AdmiralList = aPlayer->get_admiral_list();
	CAdmiralList *
		AdmiralPool = aPlayer->get_admiral_pool();
	CAdmiral *
		Admiral = AdmiralList->get_by_id(AdmiralID);
	if (!Admiral)
	{
		Admiral = AdmiralPool->get_by_id(AdmiralID);
		if (!Admiral)
		{
			message_page("You don't have such an admiral.");
			return true;
		}
	}

	ITEM("STRING_NAME", GETTEXT("Name"));
	ITEM("NAME", Admiral->get_nick());

	ITEM("STRING_LEVEL", GETTEXT("Level"));
	ITEM("LEVEL", Admiral->get_level());

	ITEM("STRING_EXP_", GETTEXT("Exp."));
	ITEM("EXP_", Admiral->get_exp());

	ITEM("STRING_COMMANDER_ABILITIES", GETTEXT("Commander Abilities"));
	ITEM("ARMADA_CLASS",
			(char *)format(GETTEXT("Armada Class %1$s Commander"),
							Admiral->get_armada_commanding_name()));

	ITEM("STRING_FLEET_COMMANDING", GETTEXT("Fleet Commanding"));
	ITEM("FLEET_COMMANDING", Admiral->get_fleet_commanding());

	ITEM("STRING_EFFICIENCY", GETTEXT("Efficiency"));
	ITEM("EFFICIENCY",
			(char *)format("%s %%", dec2unit(Admiral->get_real_efficiency())));

	ITEM("STRING_COMBAT_ABILITIES", GETTEXT("Combat Abilities"));

	ITEM("STRING_OFFENSIVE_SKILL", GETTEXT("Offensive Skill"));
	if (Admiral->get_offense_race_level())
	{
		CString
			Level = integer_with_sign(Admiral->get_overall_attack()),
			RaceLevel = integer_with_sign(Admiral->get_offense_race_level());

		ITEM("OFFENSIVE_SKILL",
				(char *)format("%s (%s %s)",
								(char *)Level,
								(char *)RaceLevel,
								GETTEXT("Race")));
	} else
	{
		ITEM("OFFENSIVE_SKILL", integer_with_sign(Admiral->get_overall_attack()));
	}

	ITEM("STRING_DEFENSIVE_SKILL", GETTEXT("Defensive Skill"));
	if (Admiral->get_defense_race_level())
	{
		CString
			Level = integer_with_sign(Admiral->get_overall_defense()),
			RaceLevel = integer_with_sign(Admiral->get_defense_race_level());

		ITEM("DEFENSIVE_SKILL",
				(char *)format("%s (%s %s)",
								(char *)Level,
								(char *)RaceLevel,
								GETTEXT("Race")));
	} else
	{
		ITEM("DEFENSIVE_SKILL", integer_with_sign(Admiral->get_overall_defense()));
	}

	ITEM("STRING_EFFECTIVENESS_ABILITIES", GETTEXT("Effectiveness Abilities"));

	ITEM("STRING_MANEUVER", GETTEXT("Maneuver"));
	if (Admiral->get_maneuver_race_level())
	{
		CString
			Level = integer_with_sign(Admiral->get_maneuver_level()),
			RaceLevel = integer_with_sign(Admiral->get_maneuver_race_level());

		ITEM("MANEUVER",
				(char *)format("%s (%s %s)",
								(char *)Level,
								(char *)RaceLevel,
								GETTEXT("Race")));
	} else
	{
		ITEM("MANEUVER", integer_with_sign(Admiral->get_maneuver_level()));
	}

	ITEM("STRING_DETECTION", GETTEXT("Detection"));
	if (Admiral->get_detection_race_level())
	{
		CString
			Level = integer_with_sign(Admiral->get_detection_level()),
			RaceLevel = integer_with_sign(Admiral->get_detection_race_level());

		ITEM("DETECTION",
				(char *)format("%s (%s %s)",
								(char *)Level,
								(char *)RaceLevel,
								GETTEXT("Race")));
	} else
	{
		ITEM("DETECTION", integer_with_sign(Admiral->get_detection_level()));
	}

	/*ITEM("STRING_INTERPRETATION", GETTEXT("Interpretation"));
	if (Admiral->get_interpretation_race_level())
	{
		CString
			Level = integer_with_sign(Admiral->get_interpretation_level()),
			RaceLevel = integer_with_sign(Admiral->get_interpretation_race_level());

		ITEM("INTERPRETATION",
				(char *)format("%s (%s %s)",
								(char *)Level,
								(char *)RaceLevel,
								GETTEXT("Race")));
	} else
	{
		ITEM("INTERPRETATION", integer_with_sign(Admiral->get_interpretation_level()));
	}*/

	ITEM("STRING_ARMADA_MODIFIERS_TO_OTHER_FLEETS___IF_ARMADA_COMMANDER_",
			GETTEXT("Armada Modifiers to Other Fleets (If Armada Commander)"));

	ITEM("STRING_EFFICIENCY", GETTEXT("Efficiency"));
	ITEM("EFFICIENCY_MOD",
			(char *)format("%d %%",
							Admiral->get_armada_commanding_effect_to_efficiency()));

	ITEM("STRING_OFFENSIVE_MOD", GETTEXT("Offensive Skill"));
	ITEM("OFFENSIVE_MOD",
			Admiral->get_armada_commanding_effect(CAdmiral::OFFENSE));

	ITEM("STRING_DEFENSIVE_MOD", GETTEXT("Defensive Skill"));
	ITEM("DEFENSIVE_MOD",
			Admiral->get_armada_commanding_effect(CAdmiral::DEFENSE));

	ITEM("STRING_SPECIAL_ABILITIES", GETTEXT("Special Abilities"));

	CString
		CommonAbility = Admiral->get_special_ability_name(),
		RaceAbility = Admiral->get_racial_ability_name();

	ITEM("SPECIAL_ABILITIES",
			(char *)format("%s, %s", (char *)CommonAbility, (char *)RaceAbility));

//	system_log("end page handler %s", get_name());

	return output("fleet/fleet_commander_information.html");
}

