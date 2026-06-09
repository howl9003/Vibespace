<?php
/*
 * Chora - the Horde interface to a CVS repository.
 *
 * Configuration file that controls the behaviour of this
 * module.
 *
 * $Horde: chora/config/conf.php.dist,v 1.31.2.5 2002/12/17 01:46:30 jon Exp $
 */

// Make sure that constants are defined.
require_once dirname(__FILE__) . '/../lib/constants.php';

/**
 ** Paths and Locations
 **/

// Location of RCS binaries you must have installed as
// part of CVS
$conf['paths']['co']          = '/usr/bin/co';
$conf['paths']['rcs']         = '/usr/bin/rcs';
$conf['paths']['rcsdiff']     = '/usr/bin/rcsdiff';
$conf['paths']['rlog']        = '/usr/bin/rlog';
$conf['paths']['cvs']         = '/usr/bin/cvs';


/**
 ** Look And Feel Configuration
 **/

// The name and email address displayed in the page footer.  This is
// generally the name of the repository administrator.
$conf['options']['adminName'] = 'Archcave Development Team';
$conf['options']['adminEmail'] = 'lilmage55@cox.net';

// In the directory view, a short summary of the last
// logentry is shown.  The value here determines how many
// characters of this to show before truncating it, and
// appending '...' to indicate there is more to show
$conf['options']['shortLogLength'] = 75;

// In the directory view, set a default sort order. The options are
// CVSLIB_SORT_NONE (no sort), CVSLIB_SORT_AGE (sort by age),
// CVSLIB_SORT_NAME (sort by filename), CVSLIB_SORT_REV (sort by
// revision number), and CVSLIB_SORT_AUTHOR (sort by author name). */
$conf['options']['defaultsort'] = CVSLIB_SORT_NAME;

// If your webserver doesn't support the PATH_INFO method
// of passing URL data (some Windows servers have this problem)
// then set the below option to 'false', and the pathnames
// will be propagated using a GET variable instead.
$conf['options']['use_path_info'] = true;

// If you wish to protect a file pattern on a global basis (i.e.
// across all cvsroots defined in cvsroots.php) list the perl-style
// regex file patterns in this array. For example:
// $conf['restrictions'] = array('^/?CVSROOT');
$conf['restrictions'] = array();

// If you wish to hide restricted files, set the below option to
// 'true', and restricted files will not be displayed.
$conf['hide_restricted'] = true;


/**
 ** Menu settings
 **/

// This is an array of applications (using the names defined in
// horde/config/registry.php) to include links to in the menubar. An
// example providing a link to Whups (a bugs/ticket-tracking program)
// would be: $conf['menu']['apps'] = array('whups');
$conf['menu']['apps'] = array();
