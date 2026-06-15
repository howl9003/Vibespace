#include <libintl.h>
#include "../pages.h"
#include "../game.h"
#include "../archspace.h"
#include "../banner.h"

// The three top-bar stat items (PP / Planet / Power) are wrapped in a tagged
// <span> so the web tier can live-update them in place: notifications.js patches
// [data-as-stat="pp|planet|power"] on a tick/war push and after a PP spend, with
// no page reload. CString::format is per-instance; the value and the span wrapper
// are built in two steps because the global format() shares one static buffer and
// would clobber itself if the calls were nested.
void
CPageHeadTitle::refresh_product_point_item()
{
	CPlayer *Player = get_player();
	assert(Player);

	CString Value, Span;
	Value.format(GETTEXT("PP : %1$s"), dec2unit(Player->get_production()));
	Span.format("<span class=\"as-stat\" data-as-stat=\"pp\">%s</span>",
				(char *)Value);
	ITEM("PRODUCT_POINT", (char *)Span);
}

void
CPageHeadTitle::refresh_planet_count_item()
{
	CPlayer *Player = get_player();
	assert(Player);

	CPlanetList *
		PlanetList = Player->get_planet_list();
	int
		NumberOfPlanet = PlanetList->length();

	CString Value, Span;
	Value.format(GETTEXT("Planet : %1$s"), dec2unit(NumberOfPlanet));
	Span.format("<span class=\"as-stat\" data-as-stat=\"planet\">%s</span>",
				(char *)Value);
	ITEM("PLANET_COUNT", (char *)Span);
}

void
CPageHeadTitle::refresh_population_item()
{
	CPlayer *Player = get_player();
	assert(Player);

	CString Value, Span;
	Value.format(GETTEXT("Power : %1$s"), dec2unit(Player->get_power()));
	Span.format("<span class=\"as-stat\" data-as-stat=\"power\">%s</span>",
				(char *)Value);
	ITEM("POPULATION", (char *)Span);
}

bool
CPageHeadTitle::get_conversion()
{
	if (!CPageCommon::get_conversion()) 
		return false;

	CPlayer *
		Player = get_player();
	if (!Player) return false;

	char *
		Country = CONNECTION->cookies().get_value("COUNTRY");
	char *
		TopBanner = BANNER->get_top_banner_by_country_menu(Country, "MAIN");
	char *
		BottomBanner = BANNER->get_bottom_banner_by_country_menu(Country, "MAIN");

	ITEM("ADLINE", TopBanner);
	ITEM("ADLINE_BOTTOM", BottomBanner);

	CString BGColor;

	switch(Player->get_race())
	{
		case 1:
			BGColor = "#2A1745";
			break;
		case 2:
			BGColor = "#0F3200";
			break;
		case 3:
			BGColor = "#462700";
			break;
		case 4:
			BGColor = "#003A48";
			break;
		case 5:
			BGColor = "#313131";
			break;
		case 6:
			BGColor = "#541E1E";
			break;
		case 7:
			BGColor = "#483600";
			break;
		case 8:
			BGColor = "#5A0000";
			break;
		case 9:
			BGColor = "#00342E";
			break;
		case 10:
			BGColor = "#3E3E00";
			break;
		default:
			BGColor = "";
	}

	ITEM("RACE_BG_COLOR", (char *)BGColor);

	CString
		ImageURL;
	ImageURL.clear();
	ImageURL.format("%s/image/as_game/race/", (char *)CGame::mImageServerURL);

	CString Race = Player->get_race_name2();

	CString Buf = ImageURL + Race + "/small_symbol.gif";
	ITEM("SMALL_RACE_IMAGE", (char *)Buf);

	ITEM("RACE_NAME", Player->get_race_name());

	// Same span-wrapped values as after a spend -- one code path, so the markup
	// the web tier patches is identical on first render and on refresh.
	refresh_product_point_item();
	refresh_planet_count_item();
	refresh_population_item();

	return true;
}
