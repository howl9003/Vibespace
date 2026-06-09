CAction::CAction()
{
	mOwner = mTarget = mAction = mTime = mID = 0;
	mDead = false;
}

CAction::CAction( MYSQL_ROW aRow )
{
	mID = atoi(aRow[0]);
	mOwner = atoi(aRow[1]);
	mTarget = atoi(aRow[2]);
	mAction = atoi(aRow[3]);
	mTime = atoi(aRow[4]);
	mDead = false;
}

CAction::~CAction()
{

}

int
CAction::id( int aID )
{
	if( aID != -1 )
		mID = aID;

	return mID;
}

int
CAction::owner( int aOwner )
{
	if( aOwner != -1 )
		mOwner = aOwner;

	return mOwner;
}

int
CAction::target( int aTarget )
{
	if( aTarget != -1 )
		mTarget = aTarget;

	return mTarget;
}

int
CAction::action( int aAction )
{
	if( aAction != -1 )
		mAction = aAction;

	return mAction;
}

int
CAction::time( int aTime )
{
	if( aTime != -1 )
		mTime = aTime;

	return mTime;
}

CString &
CAction::query()
{
	static CString
		Query;

	Query.clear();

	switch( type() ){
		case QUERY_INSERT :
			Query.format( "INSERT INTO action ( id, owner, target, action, time ) VALUES ( %d, %d, %d, %d, %d )", mID, mOwner, mTarget, mAction, mTime );
			break;

		case QUERY_DELETE :
			Query.format( "DELETE FROM action WHERE id = %d", mID );
			break;
	}

	mStoreFlag.clear();

	return Query;
}
