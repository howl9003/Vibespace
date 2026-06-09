<?php
/**
 * $Horde: chora/annotate.php,v 1.19.2.12 2004/03/26 22:43:22 jan Exp $
 *
 * Copyright 2000-2004 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

define('CHORA_BASE', dirname(__FILE__));
require_once CHORA_BASE . '/lib/base.php';

/* Spawn the file object */
$fl = &CVSLib_File::getFileObject($CVS, $CVS->cvsRoot() . '/' . $where);

/* Retrieve the desired revision from the GET variable */
$rev = Horde::getFormData('rev', '1.1');
if (!CVSLib_Rev::valid($rev)) {
    fatal('404 Not Found', "Revision $rev not found");
}

$ann = new CVSLib_Annotate($CVS, $fl);
checkError($lines = $ann->doAnnotate($rev));

$title = sprintf(_("CVS Annotation of %s for version %s"), Text::htmlallspaces($where), $rev);
$extraLink = sprintf('<a href="%s">%s</a> <b>|</b> <a href="%s">%s</a>',
                     Chora::url('co', $where, array('r' => $rev)), _("View"),
                     Chora::url('co', $where, array('r' => $rev, 'p' => 1)), _("Download"));
$js_onLoad = null;
require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_BASE . '/menu.php';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/annotate/header.inc';

$author = '';
$style = 0;

foreach ($lines as $line) {
    $lineno = $line['lineno'];
    $prevAuthor = $author;
    $author = Chora::showAuthorName($line['author']);
    if ($prevAuthor != $author) {
        $style = (++$style % 3);
    }
    $rev = $line['rev'];
    $line = Text::htmlspaces($line['line']);
    include CHORA_TEMPLATES . '/annotate/line.inc';
}

require CHORA_TEMPLATES . '/annotate/footer.inc';
require CHORA_TEMPLATES . '/common-footer.inc';
