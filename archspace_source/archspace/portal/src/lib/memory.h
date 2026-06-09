#ifndef BBS_MEMORY_H
#define BBS_MEMORY_H

extern int alloced_mem;

#define CREATE(result, type, number)  do { \
  if (!((result) = (type *) malloc ((number)*sizeof(type))))\
    { log( "create failed : %d at %s", __LINE__, __FILE__ ); \
      abort(); \
	} else { alloced_mem += (number)*sizeof(type); } } while(0)

#endif
