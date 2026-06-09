
struct query_string_data {
  char name[BUF_LEN];
  char value[BUF_LEN];
  struct query_string_data *next;
};

typedef struct query_string_data QUERY_STRING;

char *split_word( char *out, char *in, char stop );
unsigned char x2c( char *x );
void unescape_url( char *url );
char *search_query_string( QUERY_STRING *qlist, char *name );
QUERY_STRING *new_query_string( char *name, char *value );
void junk_query_string_list( QUERY_STRING *q );
void urlencode( char *src, char *dest );

QUERY_STRING *parse_string( char *str );
