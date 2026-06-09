#include "store.h"
#include "cgi.h"

TZone gStoreCenterZone = 
{
	PTH_MUTEX_INIT,
	recycle_allocation,
	recycle_free,
	sizeof(CStoreCenter),
	0,
	0,
	NULL,   
	"Zone CStoreCenter"
};

CStoreCenter::CStoreCenter():CSortedList(100, 100)
{
}

CStoreCenter::~CStoreCenter()
{
	remove_all();
	close();
}

bool
CStoreCenter::free_item(TSomething aItem)
{
	CSQL
		*SQL = (CSQL*)aItem;

	assert(SQL);

	delete SQL;

	return true;
}

int
CStoreCenter::compare(TSomething aItem1, TSomething aItem2) const
{
	CSQL
		*SQL1 = (CSQL*)aItem1,
		*SQL2 = (CSQL*)aItem2;

	int 
		Ret = strcmp(SQL1->get_table(), SQL2->get_table());
	if (Ret) return Ret;

	if (SQL1->get_count() > SQL2->get_count()) return 1;
	if (SQL1->get_count() < SQL2->get_count()) return -1;
	return 0;
}

int 
CStoreCenter::compare_key(TSomething aItem, TConstSomething aKey) const
{
	(void)aKey;
	system_log("CStoreCenter could not use find_key()");
	return 0;
}

unsigned int
CStoreCenter::count()
{
	static int
		Count = 0;

	Count++;
	if (Count > 20000) Count = 0;

	return Count;
}

bool
CStoreCenter::store(CStore &aStore)
{
	CString Query = aStore.query();

	if (Query.length() == 0)
	{
		system_log("ERROR : This query string has nothing!");
		return false;
	}

	CSQL
		*SQL = new CSQL();

	SQL->set_table(aStore.table());
	SQL->set_query((char*)Query);
	SQL->set_count(count());	

//	system_log("store [%d] %s", SQL->get_count(), SQL->get_query());

	insert_sorted(SQL);
	aStore.type(QUERY_NONE);
	return true;
}

bool
CStoreCenter::query(const char* aTable, const char *aQuery)
{
	CString Query = aQuery;

	if (!Query.length())
		return false;

	CSQL
		*SQL = new CSQL();

	SQL->set_table(aTable);
	SQL->set_query(aQuery);
	SQL->set_count(count());	

//	system_log("store [%d] %s", SQL->get_count(), SQL->get_query());

	insert_sorted(SQL);

	return true;
}

bool
CStoreCenter::initialize(const char* aFIFOName)
{
	system_log("open db connection");
	int 
		Count = 0,
		Res = 0;

	while(Count++ < 10)
	{
		Res = CFIFO::open(aFIFOName, FIFO_SEND);
		if (Res < 0) pth_nap((pth_time_t){2, 0});
		else break;
	}

	return (Res > 0);
}

int
CStoreCenter::read()
{
	return 0;
}

int
CStoreCenter::write()
{
	static time_t t = time(0);
	static int count = 0;

	CPacket
		*Packet;

	while((Packet = (CPacket*)mOutput.first()))
	{
		int 
			Result = write_packet(Packet);
		if (Result == -1)
			return -1;
		if (Result != -2)
		{
			count++;
			mOutput.remove_without_free(Packet);
			delete Packet;
		}
		else break;

//		system_log("send a packet");
	} 

	int
		LeftPacket = mOutput.length();

	if (time(0)-t >= 60)
	{
		system_log("DATABASE REPORT : Sent packets = %d, For %d seconds, Currently %d of packets left", count, time(0)-t, LeftPacket);
		t = time(0);
		count = 0;
	}

	return 0;
}

int
CStoreCenter::push()
{
//	system_log("send push command", length());

	CPacket 
		*Packet = new CPacket();

	CMessage
		Message;

	Message.set_packet(Packet->get(), MESSAGE_SEND);
	Message.type(MESSAGE_PUSH_REQUEST);

	mOutput.append(Packet);

	return 0;
}

int
CStoreCenter::send()
{
	if (!length()) return 0;

//	system_log("push item %d", length());

	for(int i=0; i<length(); i++)
	{
		CSQL
			*SQL = (CSQL*)CSortedList::get(i);
		stack_queue(*SQL);
	}
	remove_all();

	return 0;
}

void 
CStoreCenter::stack_queue(CSQL& aSQL)
{
	int
		TotalLength;

	const char 
		*Table = aSQL.get_table(),
		*Query = aSQL.get_query();

	int
		TableLength = strlen(Table),
		QueryLength = strlen(Query);

	TotalLength = 3+6+
			TableLength + 1 + ((TableLength > 256) ? 2:1) +
			QueryLength + 1 + ((QueryLength > 256) ? 2:1);

//	system_log("query:%d, total:%d", QueryLength, TotalLength);	

	unsigned char 
		BlockCount = 1;

	if (TotalLength <= STRING_DATA_BLOCK_SIZE)
	{
		CPacket 
			*Packet = new CPacket();

		CMessage
			Message;

		Message.set_packet(Packet->get(), MESSAGE_SEND);
		Message.type(MESSAGE_SQL_SEND);
		BlockCount |= 0x80;
//		system_log("BlockCount:%x", BlockCount);
		Message.set_item(BlockCount);
		Message.set_item((unsigned int)aSQL.get_count());
		Message.set_item(Table);
		Message.set_item(Query);

//		system_log("make packet %d", Packet->count());
		mOutput.append(Packet);
	} else {
		CPacket
			*Packet = new CPacket();

		CMessage
			Message;

		Message.set_packet(Packet->get(), MESSAGE_SEND);
		Message.type(MESSAGE_SQL_SEND);
//		system_log("BlockCount:%x", BlockCount);
		Message.set_item(BlockCount++);
		Message.set_item((unsigned int)aSQL.get_count());
		Message.set_item(Table);

		int 
			Length = strlen(Query),
			Done = 0,
			Size;

		char 
			Buffer[STRING_DATA_BLOCK_SIZE+1];

		Size = STRING_DATA_BLOCK_SIZE - Message.get_data_size();
		if (Size > 255) Size -= 2; else Size -= 1;

		memcpy(Buffer, Query, Size);
		Buffer[Size] = 0;

		Message.set_item(Buffer);

//		system_log("make packet %d", Packet->count());
		mOutput.append(Packet);

		Done = Size;

		while(Done < Length)
		{
			Size = (STRING_DATA_BLOCK_SIZE-9 < Length-Done) ?
					STRING_DATA_BLOCK_SIZE-9 : Length-Done;

//			system_log("length:%d, done:%d, size:%d", Length, Done, Size);

			Packet = new CPacket();
			Message.set_packet(Packet->get(), MESSAGE_SEND);
			Message.type(MESSAGE_SQL_SEND);
			Message.set_item((Done+Size < Length) ? 
								(unsigned char)BlockCount:
								(unsigned char)(BlockCount | 0x80));
//			system_log("BlockCount %x", (Done+Size < Length) ? 
//								BlockCount:BlockCount | 0x80);
			BlockCount++;

			Message.set_item((unsigned int)aSQL.get_count());
			memcpy(Buffer, Query+Done, Size);
			Buffer[Size] = 0;
			Message.set_item(Buffer);

//			system_log("make packet %d", Packet->count());
			mOutput.append(Packet);
			Done += Size;
		}
	}
}
