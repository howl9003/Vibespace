#include "../pages.h"

const char*
CPageNotFound::get_name()
{
	return "not_found.as";
}

bool
CPageNotFound::handle(CConnection &aConnection)
{
	CQueryList
		mCookies;



	mOutput.append("<HTML>\n<HEAD></HEAD>\n<BODY>\n<CENTER>\n");
	mOutput.append(aConnection.get_uri());
	mOutput.append("</BODY>\n</HTML>");

	return true;
}
