#include "../triggers.h"
#include "../archspace.h"
#include <cstdio>
#include <cstdlib>

void
CCronTabDummy::handler()
{
	SLOG( "DUMMY RUN FOR CRONTAB DUMMY" );
}

CCronTab::CCronTab()
{
	mLastActivatedTime = 0;
}

CCronTab::~CCronTab()
{
}

void
CCronTab::run()
{
	CString
		FileName;
	FileName.format( "%s/%s", ARCHSPACE->configuration().get_string( "Game", "CronTabDir" ), name() );

	handler();
	mLastActivatedTime = time(0);

	FILE
		*fp = fopen( (char*)FileName, "w+" );
	if( fp == NULL ){
		SLOG( "error happened while trying to log last activated time of crontab : %s", (char*)FileName );
		return;
	}
	fprintf( fp, "%d\n", (int)mLastActivatedTime );
	fclose(fp);
}

void
CCronTab::load_last_activated_time()
{
	CString
		FileName;
	FileName.format( "%s/%s", ARCHSPACE->configuration().get_string( "Game", "CronTabDir" ), name() );

	FILE
		*fp = fopen( (char*)FileName, "r" );
	if( fp == NULL ) return;

	char
		buf[100];
	fgets( buf, 99, fp );
	mLastActivatedTime = atoi(buf);
	SLOG( "last activated time loading : %s %s", name(), buf );
	fclose( fp );
}

bool
CTriggerRunCronTab::handler()
{
	system_log("run crontab jobs");

	time_t
		Now = time(0);

	for (int i=0 ; i<length() ; i++)
	{
		CCronTab *
			Job = (CCronTab *)get(i);

		int
			Week = Job->get_run_week() * (60 * 60 * 24 * 7);
		int
			Day = Job->get_run_day() * (60 * 60 * 24);
		int
			Hour = Job->get_run_hour() * (60 * 60);
		int
			Minute = Job->get_run_minute() * 60;
		int
			Second = Job->get_run_second();
		int
			Interval = Week + Day + Hour + Minute + Second;

		if (Interval > 0)
		{
			int
				Diff = Now - Job->get_last_activated_time();
			if (Interval <= Diff) Job->run();
		}
	}

	return true;
}

bool
CTriggerRunCronTab::free_item(TSomething aItem)
{
	CCronTab
		*Cron = (CCronTab*)aItem;

	if( Cron == NULL ) return false;

	delete Cron;

	return true;
}
