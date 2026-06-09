#ifndef BBS_MACRO_H
#define BBS_MACRO_H

/* bit operations */
#define BIT_CHECK(flag, bit) ((flag) & (bit))
#define BIT_SET(flag, bit)  ((flag) | (bit))
#define BIT_REMOVE(flag, bit) ((flag) & ~(bit)) 

#endif
