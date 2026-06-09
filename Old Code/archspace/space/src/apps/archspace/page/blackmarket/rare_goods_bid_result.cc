#include <libintl.h>
#include "../../pages.h"
#include "../../archspace.h"
#include "../../game.h"

bool
CPageBlackMarketRareGoodsBidResult::handler(CPlayer *aPlayer)
{
	//system_log( "start page handler %s", get_name());
	CBidList *
		BidList = BLACK_MARKET->get_bid_list();
	if (BidList->count_by_type(CBid::ITEM_PROJECT) == 0)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("There are no projects for sale right now."));

		return output("black_market/rare_goods_no_item.html");
	}

	QUERY( "PROJECT_ITEM_ID", projectItemIDstring );
	int
		projectItemID = as_atoi( projectItemIDstring );
	CBidList*
		bidList = BLACK_MARKET->get_bid_list();
	CBid
		*bid = bidList->get_by_id( projectItemID );
	if( bid == NULL )
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("The item you tried to bid for doesn't exist."));
		return output("black_market/rare_goods_error.html");
	}
	else if ( bid->get_type() != CBid::ITEM_PROJECT )
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("The item you tried to bid for is not a project item."));
		return output("black_market/rare_goods_error.html");
	}
	if( bid->get_winner_id() == aPlayer->get_game_id() )
	{
		ITEM( "ERROR_MESSAGE", GETTEXT( "We are sorry, you cannot outbid yourself." ) ) ;
		return output("black_market/rare_goods_error.html");
	}

	QUERY("BID_PRICE", newPriceString);
	int
		newPrice = as_atoi(newPriceString);
	if (newPrice <= 0)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("You have to enter a larger number than 0."),
								dec2unit(bid->get_price())));
		return output("black_market/rare_goods_error.html");
	}
	if (newPrice > MAX_BID_PRICE)
	{
		ITEM("ERROR_MESSAGE", GETTEXT("You tried to bid more than the limit of bid price."));
		return output("black_market/rare_goods_error.html");
	}
	if (aPlayer->get_production() < newPrice)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("We are sorry, you don't have %1$s PP."),
							dec2unit(newPrice)));
		return output("black_market/rare_goods_error.html");
	}
	if (bid->get_price() == MAX_BID_PRICE)
	{
		ITEM("ERROR_MESSAGE",
				GETTEXT("We are sorry, the current price is already the limit."));
		return output("black_market/rare_goods_error.html");
	}

	int
		MinAvailablePrice;
	if ((long long int)bid->get_price() * 105/100 > (long long int)MAX_BID_PRICE)
	{
		MinAvailablePrice = MAX_BID_PRICE;
	}
	else
	{
		MinAvailablePrice = bid->get_price() * 105/100;
	}

	if (newPrice < MinAvailablePrice)
	{
		ITEM("ERROR_MESSAGE",
				(char *)format(GETTEXT("We are sorry, you must bid at least 5%% higher price than the current bid's price %1$s."),
								dec2unit(bid->get_price())));
		return output("black_market/rare_goods_error.html");
	}

	CProject
		*project = SECRET_PROJECT_TABLE->get_by_id( bid->get_item() );
	if( aPlayer->get_purchased_project_list()->get_by_id( bid->get_item() ) != NULL )
	{
		ITEM( "ERROR_MESSAGE", "We are sorry, you appear to achieve this Project Already." );
		return output("black_market/rare_goods_error.html");
	}

	CString
        html;
	html.clear();
	html.format( "<TABLE BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"0\" BORDER COLOR=\"#2A2A2A\">\n" );
	html.format( "<TR><TD BGCOLOR=\"#171717\" CLASS=\"maintext\">%s</TD><TD CLASS=\"maintext\">%s</TD></TR>\n", GETTEXT("Type"), project->get_type_name() );
	CString
		effectList;
	effectList.clear();
	CControlModel
		effect = (CControlModel&)project->get_effect();
	bool
		anyEffect = false;
	for(int i=CControlModel::CM_ENVIRONMENT ; i<CControlModel::CM_MAX ; i++)
	{
		if( effect.get_value(i) )
		{
			if( anyEffect )
			{
				effectList += "<BR>";
			}
			effectList.format( "&nbsp;%s %d", CControlModel::get_cm_description(i), effect.get_value(i) );
			anyEffect = true;
		}
	}
	html.format( "<TR><TD BGCOLOR=\"#171717\" CLASS=\"maintext\">%s</TD><TD CLASS=\"maintext\">%s</TD></TR>\n", GETTEXT("Effect"), (char*)effectList );
	html.format( "<TR><TD COLSPAN=2 CLASS=\"maintext\">%s</TD></TR>\n", project->get_description() );
	html.format( "</TABLE>" );
	ITEM( "PROJECT_DETAIL", html );
	//ITEM( "YOUR_BID", newPrice);
	CString
		message;
	message.clear();
	message.format( "Thank you for your bid. You bid %d for %s", newPrice, project->get_name() );
	ITEM( "MESSAGE", message );
	BLACK_MARKET->bid( bid->get_id(), aPlayer->get_game_id(), newPrice );

	//system_log("end page handler %s", get_name());

	return output("black_market/rare_goods_bid_result.html");
}

