#include "banner.h"
#include "define.h"
#include <cstdio>
#include <cstring>

CBanner::CBanner()
{
}

CBanner::~CBanner()
{
}

bool
CBanner::set_country(char *aCountry)
{
	if (aCountry == NULL) return false;

	mCountry = aCountry;
	refresh_key();

	return true;
}

bool
CBanner::set_menu_name(char *aMenuName)
{
	if (aMenuName == NULL) return false;

	mMenuName = aMenuName;
	refresh_key();

	return true;
}

bool
CBanner::set_top_banner()
{
	mIsTopBanner = true;
	refresh_key();

	return true;
}

bool
CBanner::set_bottom_banner()
{
	mIsTopBanner = false;
	refresh_key();

	return true;
}

int
CBanner::get_number_of_banner_code()
{
	return mBannerCodeList.length();
}

char *
CBanner::get_banner_code_by_index(int aIndex)
{
	char *
		RandomBannerCode = (char *)mBannerCodeList.get(aIndex);

	if (RandomBannerCode == NULL) return " ";

	return RandomBannerCode;
}

bool
CBanner::add_banner_code(char *aBanner)
{
	if (aBanner == NULL) return false;

	mBannerCodeList.insert_sorted(aBanner);
	return true;
}

bool
CBanner::refresh_key()
{
	if (get_country() == NULL || get_menu_name() == NULL) return false;

	mKey.clear();
	mKey.format("%s %s", get_country(), get_menu_name());

	if (mIsTopBanner == true)
	{
		mKey += " TOP";
	}
	else
	{
		mKey += " BOTTOM";
	}

	return true;
}

CBannerCenter::CBannerCenter()
{
	mBottomBanner = false;
	mCurrentBannerCodeIndex = 0;
}

CBannerCenter::~CBannerCenter()
{
}

bool
CBannerCenter::free_item(TSomething aItem)
{
	CBanner *
		Banner = (CBanner *)aItem;
	if (Banner == NULL) return false;
 
	delete Banner;

	return true;
}

int
CBannerCenter::compare(TSomething aItem1, TSomething aItem2) const
{
	CBanner
		*Banner1 = (CBanner *)aItem1,
		*Banner2 = (CBanner *)aItem2;

	if (strcmp(Banner1->get_key(), Banner2->get_key()) > 0) return 1;
	if (strcmp(Banner1->get_key(), Banner2->get_key()) < 0) return -1;
	return 0;
}

int
CBannerCenter::compare_key(TSomething aItem, TConstSomething aKey) const
{
	CBanner *
		Banner = (CBanner *)aItem;
 
	if (strcmp(Banner->get_key(), (char *)aKey) > 0) return 1;
	if (strcmp(Banner->get_key(), (char *)aKey) < 0) return -1;
	return 0;
}
 
bool
CBannerCenter::add_banner(CBanner *aBanner)
{
	if (aBanner == NULL) return false;

	int
		Index = find_sorted_key((void *)aBanner->get_key());
	if (Index >= 0) return false;

	insert_sorted(aBanner);

	return true;
}

bool
CBannerCenter::remove_banner(CBanner *aBanner)
{
	if (aBanner == NULL) return false;

	int
		Index = find_sorted_key((void *)aBanner->get_key());
	if (Index < 0) return false;

	return CSortedList::remove(Index);
}

bool
CBannerCenter::register_country(char *aCountry)
{
	if (aCountry == NULL) return false;

	int
		Index = mCountryList.find_sorted_key(aCountry);
	if (Index >= 0) return true;

	mCountryList.insert_sorted((void *)aCountry);
	return true;
}

bool
CBannerCenter::register_menu(char *aMenu)
{
	if (aMenu == NULL) return false;

	int
		Index = mMenuList.find_sorted_key(aMenu);
	if (Index >= 0) return true;

	mMenuList.insert_sorted((void *)aMenu);
	return true;
}

bool 
CBannerCenter::load(char *aBannerPath, char *aTopBanner, char *aBottomBanner)
{
	SLOG("load Banner Code");

	FILE *
		BannerFile = fopen(aBannerPath, "r");
	if (BannerFile == NULL)
	{
		SLOG("ERROR : Couldn't load banner file!");
		return false;
	}

	char
		BufferIndex[1024 + 1],
		BufferBody[1024 + 1];
	char *
		Separator = "|\r\n";
	char *
		Token;
	char *
		Addr;

	while (1)
	{
		char *
			Temp = fgets(BufferIndex, 1024, BannerFile);
		if (Temp == NULL) break;

		CBanner *
			Banner = new CBanner();

		char *
			Country = strtok_r(BufferIndex, Separator, &Addr);
		if (Country == NULL)
		{
			SLOG("ERROR : Country is NULL in CBannerCenter::load()");

			delete Banner;
			return false;
		}
		Banner->set_country(Country);

		char *
			Menu = strtok_r(NULL, Separator, &Addr);
		if (Menu == NULL)
		{
			SLOG("ERROR : Menu is NULL in CBannerCenter::load()");

			delete Banner;
			return false;
		}
		Banner->set_menu_name(Menu);

		char *
			TopOrBottom = strtok_r(NULL, Separator, &Addr);
		if (TopOrBottom == NULL)
		{
			SLOG("ERROR : TopOrBottom is NULL in CBannerCenter::load()");

			delete Banner;
			return false;
		}
		if (!strcmp(TopOrBottom, "TOP"))
		{
			Banner->set_top_banner();
		}
		else if (!strcmp(TopOrBottom, "BOTTOM"))
		{
			Banner->set_bottom_banner();
		}
		else
		{
			SLOG("ERROR : TopOrBottom is neither TOP or BOTTOM in CBannerCenter::load()");

			delete Banner;
			return false;
		}

		CIntegerList
			LineList;
		while ((Token = strtok_r(NULL, Separator, &Addr)))
		{
			int
				NumberOfLine = as_atoi(Token);
			LineList.push((void *)NumberOfLine);
		}

		for (int i=0 ; i<LineList.length() ; i++)
		{
			int
				NumberOfLine = (int)LineList.get(i);
			CString
				BannerCode;

			for (int j=0 ; j<NumberOfLine ; j++)
			{
				char *
					Temp = fgets(BufferBody, 1024, BannerFile);
				if (Temp == NULL) break;

				BannerCode += BufferBody;
			}

			Banner->add_banner_code(string_new((char *)BannerCode));
		}

		add_banner(Banner);
		register_country(Banner->get_country());
		register_menu(Banner->get_menu_name());
	}

	if (aTopBanner == NULL)
	{
		mTopBanner = false;
	}
	else if (!strcmp(aTopBanner, "YES"))
	{
		mTopBanner = true;
	}
	else
	{
		mTopBanner = false;
	}

	if (aBottomBanner == NULL)
	{
		mBottomBanner = false;
	}
	else if (!strcmp(aBottomBanner, "YES"))
	{
		mBottomBanner = true;
	}
	else
	{
		mBottomBanner = false;
	}

	return true;
}

char *
CBannerCenter::get_top_banner_by_country_menu(char *aCountry, char *aMenu)
{
	if (mTopBanner == false) return " ";

	CString
		Country,
		Menu;

	if (aCountry == NULL)
	{
		Country = "DEFAULT";
	}
	else
	{
		int
			Index = mCountryList.find_sorted_key(aCountry);
		if (Index < 0)
		{
			Country = "DEFAULT";
		}
		else
		{
			Country = aCountry;
		}
	}

	if (aMenu == NULL)
	{
		Menu = "DEFAULT";
	}
	else
	{
		int
			Index = mMenuList.find_sorted_key(aMenu);
		if (Index < 0)
		{
			Menu = "DEFAULT";
		}
		else
		{
			Menu = aMenu;
		}
	}

	CString
		Key;
	Key.format("%s %s TOP", (char *)Country, (char *)Menu);

	int
		Index = find_sorted_key((char *)Key);
	CBanner *
		Banner = (CBanner *)get(Index);

	if (Banner == NULL) return " ";

	mCurrentBannerCodeIndex = number(Banner->get_number_of_banner_code()) - 1;

	return Banner->get_banner_code_by_index(mCurrentBannerCodeIndex);
}

char *
CBannerCenter::get_bottom_banner_by_country_menu(char *aCountry, char *aMenu)
{
	if (mBottomBanner == false) return " ";

	CString
		Country,
		Menu;

	if (aCountry == NULL)
	{
		Country = "DEFAULT";
	}
	else
	{
		int
			Index = mCountryList.find_sorted_key(aCountry);
		if (Index < 0)
		{
			Country = "DEFAULT";
		}
		else
		{
			Country = aCountry;
		}
	}

	if (aMenu == NULL)
	{
		Menu = "DEFAULT";
	}
	else
	{
		int
			Index = mMenuList.find_sorted_key(aMenu);
		if (Index < 0)
		{
			Menu = "DEFAULT";
		}
		else
		{
			Menu = aMenu;
		}
	}

	CString
		Key;
	Key.format("%s %s BOTTOM", (char *)Country, (char *)Menu);

	int
		Index = find_sorted_key((char *)Key);
	CBanner *
		Banner = (CBanner *)get(Index);

	if (Banner == NULL) return " ";

	return Banner->get_banner_code_by_index(mCurrentBannerCodeIndex);
}

