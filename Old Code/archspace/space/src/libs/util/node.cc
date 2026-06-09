#include "util.h"

TZone gNodeZone =
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CNode),
	0,
	0,
	NULL,
	"Zone CNode"
};

CNode::CNode()
{
	mNext = NULL;
}

CNode::~CNode()
{
}

CNode*
CNode::next() const
{
	return mNext;
}

CNode*
CNode::next(CNode* aNode)
{
	mNext = aNode;
	return this;
}

CNode*
CNode::last() const 
{
	CNode 
		*Temp = (CNode*)this;
	
	while(Temp->next()) Temp = Temp->next();

	return Temp;
}

