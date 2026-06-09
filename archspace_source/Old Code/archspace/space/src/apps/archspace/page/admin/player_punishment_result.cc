#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPagePlayerPunishmentResult::handler(CPlayer *aPlayer)
{
//	system_log( "start page handler %s", get_name());

	CQueryList &
		IDString = CONNECTION->id_string();
	char *
		Admin = IDString.get_value("IS_ADMIN");

	if (!Admin)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/player_error.html");
	}
	if (strcmp(Admin, "YES"))
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("You are not a customer supporter of Archspace."));
		return output("admin/player_error.html");
	}

	CPlayer *
		Player = PLAYER_TABLE->get_by_portal_id(mPortal.get_id());
	if (!Player)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("This account doesn't have any characters."));
		return output("admin/player_error.html");
	}

	QUERY("BY_WHAT", ByWhatString);
	if (!ByWhatString)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("By What String is NULL."
						" Maybe you should reload portal information again."));
		return output("admin/player_error.html");
	}

	if (!strcmp(ByWhatString, "IMPERIAL_RETRIBUTION"))
	{
		CPlayerEffectList *
			EffectList = Player->get_effect_list();
		CPlayerEffect *
			CurrentEffect = EffectList->get_by_type(CPlayerEffect::PA_IMPERIAL_RETRIBUTION);
		if (CurrentEffect)
		{
			ITEM("ERROR_MESSAGE",
					GETTEXT("This player has already Imperial Retribution."));
			return output("admin/player_punishment_error.html");
		}

		CPlayerEffect *
			Effect = new CPlayerEffect();
		Effect->set_id(EffectList->get_new_id());
		Effect->set_owner(Player->get_game_id());
		Effect->set_life(0);
		Effect->set_type(CPlayerEffect::PA_IMPERIAL_RETRIBUTION);
		Effect->set_argument1(0);
		Effect->set_source(CPlayerEffect::ST_FROM_ADMIN);
		Effect->set_savable();

		Effect->type(QUERY_INSERT);
		STORE_CENTER->store(*Effect);

		EffectList->add_player_effect(Effect);

		Player->time_news(GETTEXT("You have been punished by GM."
									" Now you have Imperial Retribution,"
									" and it will stay forever."));

		ADMIN_TOOL->add_punishment_log(
				(char *)format("Player %s has been punished by Imperial Retribution.",
								Player->get_nick()));
	}
	else if (!strcmp(ByWhatString, "REMOVE_IMPERIAL_RETRIBUTION"))
	{
		CPlayerEffectList *
			EffectList = Player->get_effect_list();
		CPlayerEffect *
			CurrentEffect = EffectList->get_by_type(CPlayerEffect::PA_IMPERIAL_RETRIBUTION);
		if (!CurrentEffect)
		{
			ITEM("ERROR_MESSAGE",
					GETTEXT("This player doesn't have Imperial Retribution."));
			return output("admin/player_punishment_error.html");
		}

		CurrentEffect->type(QUERY_DELETE);
		STORE_CENTER->store(*CurrentEffect);

		EffectList->remove_player_effect(CurrentEffect);

		Player->time_news(GETTEXT("Your Imperial Retribution has been removed by GM."));

		ADMIN_TOOL->add_punishment_log(
				(char *)format("Player %s isn't under Imperial Retribution effect anymore.",
								Player->get_nick()));
	}
	else if (!strcmp(ByWhatString, "SKIP_TURN"))
	{
		QUERY("TURN", TurnString);
		int
			Turn = as_atoi(TurnString);
		if (Turn < 1)
		{
			ITEM("ERROR_MESSAGE",
					GETTEXT("Turn value you entered is invalid."));
			return output("admin/player_error.html");
		}

		CPlayerEffectList *
			EffectList = Player->get_effect_list();
		CPlayerEffect *
			Effect = EffectList->get_by_type(CPlayerEffect::PA_SKIP_TURN);
		if (Effect)
		{
			time_t
				OldLife = Effect->get_life();
			Effect->set_life(OldLife + Turn*CGame::mSecondPerTurn);

			Effect->type(QUERY_UPDATE);
			STORE_CENTER->store(*Effect);

			if (Turn == 1)
			{
				Player->time_news(
						(char *)format(
								GETTEXT("You have been punished by GM."
										" You had Skip Turn effect,"
										" and its duration has been increased by %1$s turn."),
								dec2unit(Turn)));
			}
			else
			{
				Player->time_news(
						(char *)format(
								GETTEXT("You have been punished by GM."
										" You had Skip Turn effect,"
										" and its duration has been increased by %1$s turns."),
								dec2unit(Turn)));
			}

			ADMIN_TOOL->add_punishment_log(
					(char *)format("The Skip Turn effect on the player %s will last longer(%d turn(s) more).",
									Player->get_nick()));
		}
		else
		{
			CPlayerEffect *
				NewEffect = new CPlayerEffect();
			NewEffect->set_id(EffectList->get_new_id());
			NewEffect->set_owner(Player->get_game_id());
			NewEffect->set_life(CGame::get_game_time() + Turn*CGame::mSecondPerTurn);
			NewEffect->set_type(CPlayerEffect::PA_SKIP_TURN);
			NewEffect->set_savable();

			EffectList->add_player_effect(NewEffect);

			NewEffect->type(QUERY_INSERT);
			STORE_CENTER->store(*NewEffect);

			if (Turn == 1)
			{
				Player->time_news(
						(char *)format(
								GETTEXT("You have been punished by GM."
										" Now you have Skip Turn effect,"
										" and it will stay for %1$s turn."),
								dec2unit(Turn)));
			}
			else
			{
				Player->time_news(
						(char *)format(
								GETTEXT("You have been punished by GM."
										" Now you have Skip Turn effect,"
										" and it will stay for %1$s turns."),
								dec2unit(Turn)));
			}

			ADMIN_TOOL->add_punishment_log(
					(char *)format("The player %s has a new Skip Turn effect now.(%d turn(s)).",
									Player->get_nick()));
		}
	}
	else
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("By What String is wrong."
						" Maybe you should reload portal information again."));
		return output("admin/player_punishment_error.html");
	}

	ITEM("RESULT_MESSAGE",
			(char *)format(GETTEXT("%1$s has been punished by you successfully."),
							Player->get_nick()));

	//	system_log("end page handler %s", get_name());

	return output("admin/player_punishment_result.html");
}

