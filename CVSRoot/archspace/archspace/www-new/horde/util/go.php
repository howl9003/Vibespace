<?php
/**
 * A script to redirect to a given URL, used for example in IMP to hide any
 * referrer data being passed to the remote server and potentially exposing any
 * session IDs.
 *
 * Copyright 2003 Marko Djukic <marko@oblo.com>
 *
 * See the enclosed file COPYING for license information (GPL). If you did not
 * receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: horde/util/go.php,v 1.2.2.1 2003/08/20 00:26:13 mdjukic Exp $
 *
 * @author Marko Djukic <marko@oblo.com>
 * @version $Revision: 1.1.1.1 $
 */

header('Refresh: 0; URL=' . $_GET['url']);
