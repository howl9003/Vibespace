<?php
/**
 * $Horde: chora/index.php,v 1.11.2.6 2004/03/26 22:43:22 jan Exp $
 *
 * Copyright 1999-2004 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

define('CHORA_BASE', dirname(__FILE__));
$chora_configured = (@is_readable(CHORA_BASE . '/config/conf.php') &&
                     @is_readable(CHORA_BASE . '/config/cvsroots.php') &&
                     @is_readable(CHORA_BASE . '/config/mime_drivers.php') &&
                     @is_readable(CHORA_BASE . '/config/prefs.php') &&
                     @is_readable(CHORA_BASE . '/config/html.php'));

if ($chora_configured) {
    include_once CHORA_BASE . '/lib/base.php';
    header('Location: ' . str_replace('&amp;', '&', Chora::url('cvs', '', array('login' => 1))));

/* Chora isn't configured */
} else {
    include CHORA_BASE . '/templates/index/notconfigured.inc';
}
