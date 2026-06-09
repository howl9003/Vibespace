<?php
// $Horde: chora/lib/constants.php,v 1.1.2.1 2002/03/20 18:48:42 chuck Exp $

/*
 * These are used to construct the CVSLib_Error objects
 * to identify problem classes
 */

define('CVSLIB_INTERNAL_ERROR', 1);
define('CVSLIB_NOT_FOUND', 2);
define('CVSLIB_PERMISSION_DENIED', 3);

/*
 * Every class in this package has an id() function 
 * which returns what it is, to allow the calling function
 * to figure out whether it is an error or a data return type
 */

define('CVSLIB_ERROR', 4);
define('CVSLIB_CHECKOUT', 5);
define('CVSLIB_REPOSITORY', 6);
define('CVSLIB_DIRECTORY', 7);
define('CVSLIB_FILE', 8);
define('CVSLIB_ANNOTATE', 9);

define('CVSLIB_LOG_INIT', 10);
define('CVSLIB_LOG_FILENAME', 11);
define('CVSLIB_LOG_REVISION', 12);
define('CVSLIB_LOG_INFO', 13);
define('CVSLIB_LOG_DATE', 14);
define('CVSLIB_LOG_BRANCHES', 15);
define('CVSLIB_LOG_DONE', 16);

define('CVSLIB_LOG_FULL', 0);
define('CVSLIB_LOG_QUICK', 1);

define('CVSLIB_ATTIC_HIDE', 0);
define('CVSLIB_ATTIC_SHOW', 1);

/*
 * Sorting options 
 */

define('CVSLIB_SORT_NONE', 0);        // don't sort
define('CVSLIB_SORT_AGE', 1);         // sort by age
define('CVSLIB_SORT_NAME', 2);        // sort by filename
define('CVSLIB_SORT_REV', 3);         // sort by revision number
define('CVSLIB_SORT_AUTHOR', 4);      // sort by author name

define('CVSLIB_SORT_ASCENDING', 0);   // ascending order
define('CVSLIB_SORT_DESCENDING', 1);  // descending order

/* 
 * Diff options
 */

define('CVSLIB_DIFF_CONTEXT', 0);
define('CVSLIB_DIFF_UNIFIED', 1);
define('CVSLIB_DIFF_COLUMN',  2);
define('CVSLIB_DIFF_ED',      3);

define('CVSLIB_DIFF_HEADER', 'header');
define('CVSLIB_DIFF_ADD', 'add');
define('CVSLIB_DIFF_EMPTY', 'empty');
define('CVSLIB_DIFF_DUMP', 'dump');
define('CVSLIB_DIFF_REMOVE', 'remove');
define('CVSLIB_DIFF_CHANGE', 'change');

?>
