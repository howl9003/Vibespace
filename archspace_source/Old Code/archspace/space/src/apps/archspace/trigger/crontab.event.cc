#include <libintl.h>
#include "../triggers.h"
#include "../archspace.h"
#include "../race.h"

void
CCronTabEvent::handler()
{
	SLOG( "Event Crontab Running Start" );

	if (EMPIRE->is_dead() == false)
	{
		GALACTIC_EVENT_LIST->expire_out_due_event();
		GALACTIC_EVENT_LIST->save_galactic_event();

		for (int i=0 ; i<UNIVERSE->length() ; i++)
		{
			CCluster *
				Cluster = (CCluster *)UNIVERSE->get(i);
			if (Cluster->get_id() == EMPIRE_CLUSTER_ID) continue;

			Cluster->get_event_list()->expire_out_due_event();

			if (number(100) <= 8)
			{ // 8%
				CEvent *
					Event = EVENT_TABLE->get_random_by_type(CEvent::EVENT_CLUSTER);
				if (Event != NULL)
				{
					CEventInstance *
						Instance = new CEventInstance(Event, time(0));
					Instance->set_id(Cluster->get_event_list()->get_new_id());
					Cluster->get_event_list()->add_event_instance(Instance);
//					SLOG( "EVENT DEBUG : New Event %s on %s", Event->get_name(), Cluster->get_name() );
				}
			}

			Cluster->get_event_list()->save_cluster_event(Cluster);
		}

		for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
		{
			CPlayer *
				Player = (CPlayer *)PLAYER_TABLE->get(i);
			if (Player == EMPIRE) continue;
			if (Player->is_dead()) continue;

			int
				Roll = number(100);
			if (Roll <= 63)
			{
				int
					RollAgain = number(100);
				if (RollAgain <= 4)
				{	// Major 4%
					CEvent *
						Event = EVENT_TABLE->get_random_by_type(CEvent::EVENT_MAJOR);
					if (Event != NULL)
					{
						if (Event->possible_event(Player) == true)
						{
							CEventInstance *
								Instance = new CEventInstance( Event, time(0) );
							Instance->set_id(Player->get_event_list()->get_new_id());
							Instance->set_owner(Player->get_game_id());
							Instance->save_new_event();

							Player->get_event_list()->add_event_instance(Instance);

							if (Instance->has_type(CEvent::EVENT_ANSWER) == false)
							{
								CString
									News;
								News.format(GETTEXT("You got a new event %1$s."),
											Instance->get_name());
								News += "<BR>\n";
								News += Player->activate_event(Instance);
								Player->time_news((char *)News);
							}
						}
					}
				}
				else if (RollAgain <= 10)
				{	// Racial 4%
					int
						Type = 0;
					switch (Player->get_race())
					{
						case CRace::RACE_HUMAN :
							Type = CEvent::EVENT_HUMAN_ONLY;
							break;
						case CRace::RACE_TARGOID :
							Type = CEvent::EVENT_TARGOID_ONLY;
							break;
						case CRace::RACE_BUCKANEER :
							Type = CEvent::EVENT_BUCKANEER_ONLY;
							break;
						case CRace::RACE_TECANOID :
							Type = CEvent::EVENT_TECANOID_ONLY;
							break;
						case CRace::RACE_EVINTOS :
							Type = CEvent::EVENT_EVINTOS_ONLY;
							break;
						case CRace::RACE_AGERUS :
							Type = CEvent::EVENT_AGERUS_ONLY;
							break;
						case CRace::RACE_BOSALIAN :
							Type = CEvent::EVENT_BOSALIAN_ONLY;
							break;
						case CRace::RACE_XELOSS :
							Type = CEvent::EVENT_XELOSS_ONLY;
							break;
						case CRace::RACE_XERUSIAN :
							Type = CEvent::EVENT_XERUSIAN_ONLY;
							break;
						case CRace::RACE_XESPERADOS :
							Type = CEvent::EVENT_XESPERADOS_ONLY;
							break;
					}
					CEvent *
						Event = EVENT_TABLE->get_random_by_type(Type);
					if (Event != NULL)
					{
						if (Event->possible_event(Player) == true)
						{
							CEventInstance *
								Instance = new CEventInstance(Event, time(0));
							Instance->set_id(Player->get_event_list()->get_new_id());
							Instance->set_owner(Player->get_game_id());
							Instance->save_new_event();

							Player->get_event_list()->add_event_instance(Instance);

							if (Instance->has_type(CEvent::EVENT_ANSWER) == false)
							{
								CString
									News;
								News.format(GETTEXT("You got a new event %1$s."),
											Instance->get_name());
								News += "<BR>\n";
								News += Player->activate_event(Instance);
								Player->time_news((char *)News);
							}
						}
					}
				}
				else
				{	// Regular 90%
					CEvent *
						Event = EVENT_TABLE->get_random_by_type(CEvent::EVENT_SYSTEM);
					if (Event != NULL)
					{
						if (Event->possible_event(Player) == true)
						{
							CEventInstance *
								Instance = new CEventInstance(Event, time(0));
							Instance->set_id(Player->get_event_list()->get_new_id());
							Instance->set_owner(Player->get_game_id());
							Instance->save_new_event();

							Player->get_event_list()->add_event_instance(Instance);

							if (Instance->has_type(CEvent::EVENT_ANSWER) == false)
							{
								CString
									News;
								News.format(GETTEXT("You got a new event %1$s."),
											Instance->get_name());
								News += "<BR>\n";
								News += Player->activate_event(Instance);
								Player->time_news((char *)News);
							}
						}
					}
				}
			}
		}
	}

	SLOG( "Event Crontab Running End" );
}

