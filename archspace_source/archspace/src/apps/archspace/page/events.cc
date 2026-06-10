#include <libintl.h>
#include "../pages.h"
#include "../archspace.h"
#include "../game.h"
#include "../council.h"
#include "../message.h"

//
// /archspace/events.as
//
// Read-only JSON "fingerprint" of the logged-in player's notifiable state,
// consumed by the SSE push bridge (web/auth/events.php). The bridge compares
// successive fingerprints; when one advances it pushes an SSE event so the
// browser refreshes the news feed in real time. This handler reads existing
// in-memory state only and never mutates game state.
//
//   { "t": <turn>, "d": <unread diplomatic>, "c": <unread council>,
//     "n": <pending real-time events> }
//
// If the request isn't an authenticated, living character, it returns
// { "auth": 0 } so the bridge can quietly idle.
//
bool
CPageEvents::handle(CConnection &aConnection)
{
	mConnection = &aConnection;

	CPlayer *Player = get_player();
	if (Player == NULL || Player->is_dead())
	{
		mOutput = "{\"auth\":0}";
		mConnection = NULL;
		return true;
	}

	int Turn      = Player->get_turn();
	int Diplomatic = Player->get_diplomatic_message_box()->count_unread_message();

	int Council = 0;
	CCouncil *PlayerCouncil = Player->get_council();
	if (PlayerCouncil != NULL)
		Council = PlayerCouncil->get_council_message_box()->count_unread_message();

	int Events = Player->get_pending_time_news_count();

	static CString Out;
	Out.clear();
	Out.format("{\"t\":%d,\"d\":%d,\"c\":%d,\"n\":%d}",
				Turn, Diplomatic, Council, Events);

	mOutput = (char *)Out;
	mConnection = NULL;
	return true;
}
