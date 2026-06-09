<?php
/*
 * $Horde: chora/config/cvsroots.php.dist,v 1.4.2.3 2002/06/23 13:45:32 jan Exp $
 *
 * This file contains all the configuration information for the various
 * CVS repositories that you wish to display.  You should have a minimum of
 * one entry here!  The following fields are allowed in the description,
 * and those with a [M] are Mandatory, and should not be left out.
 *
 * 'name'     [M] : Short name for the repository
 *
 * 'location' [M] : Location on the filesystem of the CVSROOT
 * 
 * 'title'    [M] : Long title for the repository
 *
 * 'default'      : To make that repository the default one to show
 *
 * 'intro'        : File which contains some introductory text to show
 *                  on the front page of this repository.  This file is
 *                  located in the config/ directory.
 *
 * 'cvsusers'     : A list of all committers with real names and email
 *                  addresses, that normally sits in the CVSROOT/cvsusers
 *                  file.  If it is found, then more useful information
 *                  will be shown.
 *
 * 'restrictions' : Array of perl-style regular expressions for those files
 *                  whose contents should be protected and not displayed.
*/

$cvsroots['archspace'] = array(
    'name' => 'archspace',
    'location' => '/var/cvsroot/archspace',
    'title' => 'ArchCave CVS Repository',
    'intro' => 'longIntro.txt',
    'default' => true
);
/*
$cvsroots['horde'] = array(
    'name' => 'Horde',
    'location' => '/home/cvs/horde',
    'title' => 'Horde CVS Repository',
    'cvsusers' => 'CVSROOT/cvsusers',
    'intro' => 'horde-intro.txt',
    'restrictions' => array('^/?hordeweb/config/defaults.php')
);

$cvsroots['openbsd'] = array(
    'name' => 'OpenBSD',
    'location' => '/home/cvs/openbsd',
    'title' => 'OpenBSD CVS Repository'
);
*/
