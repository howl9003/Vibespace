<?php
/* $Id: chk_rel.php,v 1.1.1.1 2004/12/25 09:14:36 brian Exp $ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./db_details_common.php');
require_once('./libraries/relation.lib.php');


/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam(TRUE);


/**
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
