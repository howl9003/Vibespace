extern int alloced_mem;

#define CREATE(result, type, number)  do { \
  if (!((result) = (type *) calloc ((number), sizeof(type))))\
    { log( LOG_DEBUG, "create failed : %d at %s", __LINE__, __FILE__ ); \
	} else { \
alloced_mem += (number)*sizeof(type); \
   log( LOG_DEBUG, "create %d : %d at %s", (number)*sizeof(type), __LINE__, __FILE__ );  \
  }} while(0)

#define BIT_CHECK(flag, bit) ((flag) & (bit))
#define BIT_SET(flag, bit)  ((flag) | (bit))
#define BIT_REMOVE(flag, bit) ((flag) & ~(bit)) 

int number( int i );
int dice( int i, int j );
int sdice( int c );

#define INSERT_LIST( c, n )     do { \
  n->next = c->next; c->next = n; } while(0)

char *arch_strdup( char *str );

int min( int a, int b );
int max( int a, int b );

int my_atoi( char *str );
int my_isalnum( char c );

extern char char_pool[];

char *get_random_str( int len );
char *char_to_hex( unsigned char c );
