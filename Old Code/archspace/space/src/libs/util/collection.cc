#include "util.h"

TZone gCollectionZone = 
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CCollection),
	0,
	0,
	NULL,   
	"Zone CCollection"
};

CCollection::CCollection()
{
	mRoot = mCurrent = NULL;
	mLength = 0;
}

CCollection::~CCollection()
{
	if (mRoot) shutdown();
	mRoot = mCurrent = NULL;
}

CNode*
CCollection::first()
{
	mCurrent = mRoot;
	return mCurrent;
}

CNode*
CCollection::next()
{
	if (!mRoot) return NULL;
	if (!mCurrent) return NULL;
	mCurrent = mCurrent->next();
	return mCurrent;
}

CNode*
CCollection::last()
{
	if (!mRoot) return NULL;
	return (mCurrent = mTail);
}

void
CCollection::shutdown()
{
	CNode
		*Temp = mRoot;

	while(Temp)
	{
		CNode
			*Next = Temp->next();
		Temp->shutdown();
		delete Temp;
		Temp = Next;
	}
	mRoot = mTail = mCurrent = NULL;
	mLength = 0;
}

CNode*
CCollection::append(CNode* aNode)
{
	if (!mRoot)
	{
		aNode->next(NULL);
		mRoot = mTail = mCurrent = aNode;
	} else {
		mTail->next(aNode->next(NULL));
		mTail = mCurrent = aNode;
	}
	mLength++;
	return aNode;
}

CNode*
CCollection::insert(CNode* aNode)
{
	if (!mRoot)
	{
		mRoot = mTail = mCurrent = aNode;
	} else {
		aNode->next(mRoot);
		mRoot = mCurrent = aNode;
	}
	mLength++;
	return aNode;
}

bool
CCollection::remove(CNode* aNode)
{
	if (!aNode) return false;
	if (!mRoot) return false;
	
	if (mRoot == aNode)
	{
		if( mTail == aNode ) mTail = NULL;
		aNode->shutdown();
		mRoot = aNode->next();
		delete aNode;
		mCurrent = mRoot;
		mLength--;
		return true;
	} 

	CNode
		*Temp = mRoot;
	
	while(Temp && (Temp->next() != aNode)) Temp = Temp->next();

	if (Temp)
	{
		if( mTail == aNode ) mTail = Temp;
		Temp->next(aNode->next());
		mCurrent = aNode->next();
		aNode->shutdown();
		delete aNode;
		mLength--;
		return true;
	}

	return false;
}

bool
CCollection::remove_without_free(CNode* aNode)
{
	if (!aNode) return false;
	if (!mRoot) return false;
	
	if (mRoot == aNode)
	{
		if( mTail == aNode ) mTail = NULL;
		mRoot = aNode->next();
		mCurrent = mRoot;
		mLength--;
		return true;
	} 

	CNode
		*Temp = mRoot;
	
	while(Temp && (Temp->next() != aNode)) Temp = Temp->next();

	if (Temp)
	{
		if( mTail == aNode ) mTail = Temp;
		Temp->next(aNode->next());
		mCurrent = aNode->next();
		mLength--;
		return true;
	}

	return false;
}

bool
CCollection::empty()
{
	if (mRoot == NULL) return true;
	return false;
}

