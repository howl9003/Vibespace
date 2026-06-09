<?php
/**
 * $Horde: chora/co.php,v 1.9.2.16 2004/03/26 22:43:22 jan Exp $
 *
 * Copyright 2000-2004 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

define('CHORA_BASE', dirname(__FILE__));
require_once CHORA_BASE . '/lib/base.php';
require_once HORDE_BASE . '/config/mime_mapping.php';
require_once HORDE_BASE . '/config/mime_drivers.php';
require_once HORDE_BASE . '/lib/Horde.php';
require_once HORDE_BASE . '/lib/MIME/Part.php';
require_once HORDE_BASE . '/lib/MIME/Magic.php';
require_once HORDE_BASE . '/lib/MIME/Viewer.php';

/* Should we pretty-print this output or not? */
$plain = Horde::getFormData('p', 0);

/* Create the CVSLib_File object and populate it. */
$file = &CVSLib_File::getFileObject($CVS, $fullname, CVSLIB_LOG_FULL);

/* Get the revision number. */
$r = Horde::getFormData('r', 0);

/* If no revision is specified, default to HEAD.
 * If a revision is specified, it's safe to cache for a long time */
if ($r == 0) {
    $r = $file->queryRevision();
    header('Cache-Control: max-age=60, must-revalidate');
} else {
    header('Cache-Control: max-age=2419200');
}

/* Is this a valid revision being requested? */
if (!CVSLib_rev::valid($r)) {
    fatal('404 Not Found', "Revision Not Found: $r is not a valid RCS revision number");
}

/* Retrieve the actual checkout. */
$checkOut = CVSLib_Checkout::get($CVS, $fullname, $r);

/* Calculate the file extension, to identify the MIME type. */
$extension = $file->getExtension();
echo $extension;
/* Check error status, and either show error page, or the checkout contents */
if (is_object($checkOut) && $checkOut->id() == CVSLIB_ERROR) {
    checkError($checkOut);
} else if (!$plain) {
    /* Pretty-print the checked out copy */
    $pretty = CVSLib_Checkout::pretty($CVS, $extension, $checkOut);
    $log = $file->logs[$r];

    /* Get this revision's attributes in printable form. */
    $author = Chora::showAuthorName($log->queryAuthor(), true);
    $date = strftime('%c', $log->queryDate());
    $log_print = Chora::toHTML($log->queryLog());

    if ($pretty->getType() == 'text/html' || $pretty->getType() == 'text/plain') {
        $title = sprintf(_("CVS Checkout of %s (revision %s)"), basename($fullname), $r);
        $extraLink = sprintf('<a href="%s">%s</a> <b>|</b> <a href="%s">%s</a>',
                             Chora::url('annotate', $where, array('rev' => $r)), _("Annotate"),
                             Chora::url('co', $where, array('r' => $r, 'p' => 1)), _("Download"));
        $js_onLoad = null;
        include CHORA_TEMPLATES . '/common-header.inc';
        include CHORA_BASE . '/menu.php';
        include CHORA_TEMPLATES . '/headerbar.inc';
        include CHORA_TEMPLATES . '/checkout/header.inc';
        if ($pretty->getType() == 'text/plain') {
            echo '<pre>' . htmlspecialchars($pretty->render()) . '</pre>';
        } else {
            echo $pretty->render();
        }
        include CHORA_TEMPLATES . '/checkout/footer.inc';
        $registry->shutdown();
        include CHORA_TEMPLATES . '/common-footer.inc';
    } else {
        header('Content-Type: ' . $pretty->getType());
        print $pretty->render();
    }
} else {
    /* Send Force a save file dialog. */
    $filename = $file->queryName();
    if ($browser->getBrowser() == 'opera') {
        $filename = strtr($filename, ' ', '_');
    }
    header('Content-Type: ' . MIME_Magic::extToMIME($extension));
    if ($browser->hasQuirk('break_disposition_header')) {
        header('Content-Disposition: filename=' . $filename);
    } else {
        header('Content-Disposition: attachment; filename=' . $filename);
    }
    if ($browser->hasQuirk('cache_ssl_downloads')) {
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
    }
    $fp = CVSLib_Checkout::get($CVS, $fullname, $r);
    $content = '';
    while ($line = fgets($fp)) {
        $content .= $line;
    }
    @fclose($fp);
    echo $content;
    exit;
}
