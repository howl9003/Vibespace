#include "news.h"

CNews::CNews()
{
}

CNews::~CNews()
{
}

void
CNews::clear()
{
	mNews.clear();
}

const char *
CNews::get_query()
{
	return (char*)mNews;
}

void
CNews::set_query(const char *aQuery)
{
	if (!aQuery) return;

	mNews = aQuery;
}

const char*
CNews::get()
{
	static CString
		News;
	News.clear();

	News = filter((char *)mNews);

	mNews.clear();

	return (char *)News;
}

