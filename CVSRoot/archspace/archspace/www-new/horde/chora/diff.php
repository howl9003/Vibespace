<?php
/**
 * $Horde: chora/diff.php,v 1.43.2.15 2004/06/12 14:06:20 chuck Exp $
 *
 * Copyright 2000-2004 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

define('CHORA_BASE', dirname(__FILE__));
require_once CHORA_BASE . '/lib/base.php';

/* Spawn the repository and file objects */
$fl = &CVSLib_File::getFileObject($CVS, $CVS->cvsRoot() . '/' . $where);

/* Initialise the form variables correctly.
 * If r1/r2 are empty, then use the corresponding text field instead */
$r1 = Horde::getFormData('r1', 0);
$r2 = Horde::getFormData('r2', 0);

if (!$r1) $r1 = Horde::getFormData('tr1');
if (!$r2) $r2 = Horde::getFormData('tr2');

/* If no context-size has been specified, default to 3. */
$num = (int)Horde::getFormData('num', 3);

/* If no type has been specified, then default to human readable. */
$ty = Horde::getFormData('ty', 'h');

/* Unless otherwise specified, show whitespace differences. */
$ws = Horde::getFormData('ws', 1);

/* Figure out what type of diff has been requested */
switch ($ty) {
case 'u':
    $type = CVSLIB_DIFF_UNIFIED;
    break;
case 'h':
    $type = CVSLIB_DIFF_UNIFIED;
    break;
case 's':
    $type = CVSLIB_DIFF_COLUMN;
    break;
case 'c':
    $type = CVSLIB_DIFF_CONTEXT;
    break;
case 'e':
    $type = CVSLIB_DIFF_ED;
    break;
default:
    $type = CVSLIB_DIFF_UNIFIED;
    break;
}

/* Ensure that we have valid revision numbers */
if (!CVSLib_Rev::valid($r1) || !CVSLib_Rev::valid($r2)) {
    checkError(new CVSLib_Error(CVSLIB_NOT_FOUND, 'Malformed Query'));
}

/* Cache the output of the diff for a week - it can be longer, since
 * it should never change */
header('Cache-Control: max-age=604800');

/* Title to use for html output pages */
$title = sprintf(_("Diff for %s between version %s and %s"),
                 Text::htmlallspaces($where), $r1, $r2);

/* All is ok, proceed with the diff */
switch ($type) {
case CVSLIB_DIFF_COLUMN:
    /* We'll need to know the mime type to modify diffs based on the mime
       type. */
    require_once HORDE_BASE . '/lib/MIME/Magic.php';
    $mime_type = MIME_Magic::extToMIME($fl->getExtension());

    if (in_array($mime_type, array('image/gif', 'image/jpeg', 'image/png', 'image/bmp', 'image/tiff'))
        && $browser->hasFeature('images')) {
        // The above are images that most web browsers can inline
        // We borrow a *large* part of this from the Human-Readable case
        $url1 = Chora::url('co', $where, array('r' => $r1));
        $url2 = Chora::url('co', $where, array('r' => $r2));
        $path = $fl->queryModulePath();

        // Get the log entry so we can display it
        $log = $fl->logs[$r2];
        $log_print = Chora::toHTML($log->queryLog());

        // Start the html output, include menu bar and headers
        $js_onLoad = null;
        include CHORA_TEMPLATES . '/common-header.inc';
        include CHORA_BASE . '/menu.php';
        include CHORA_TEMPLATES . '/headerbar.inc';

        // Create a table for the two revisions, display log, and
        // print a labeled bar for the revisions.
        include CHORA_TEMPLATES . '/diff/hr/header.inc';
        echo "<td><img src=\"$url1\"></td>";
        echo "<td><img src=\"$url2\"></td>";
        echo '</tr>';
        include CHORA_TEMPLATES . '/common-footer.inc';
    } else {
        header('Content-Type: text/plain');
        echo implode("\n", CVSLib_Diff::get($CVS, $fl, $r1, $r2,
             $type, $num, $ws));
    }
    break;

case CVSLIB_DIFF_CONTEXT:
    header('Content-Type: text/plain');
    echo implode("\n", CVSLib_Diff::get($CVS, $fl, $r1, $r2, $type, $num, $ws));
    break;

case CVSLIB_DIFF_ED:
    header('Content-Type: text/plain');
    echo implode("\n", CVSLib_Diff::get($CVS, $fl, $r1, $r2, $type, $num, $ws));
    break;

case CVSLIB_DIFF_UNIFIED:
default:
    if ($ty != 'h') {
        /* Not Human-Readable format. */
        header('Content-Type: text/plain');
        echo implode("\n", CVSLib_Diff::get($CVS, $fl, $r1, $r2,
                                            $type, $num, $ws));
    } else {
        /* Human-Readable diff. */

        /* Output standard header information for the page. */
        $filename = preg_replace('/^.*\//', '', $where);
        $pathname = preg_replace('/[^\/]*$/', '', $where);

        $log = $fl->logs[$r2];
        $log_print = Chora::toHTML($log->queryLog());

        $js_onLoad = null;
        include CHORA_TEMPLATES . '/common-header.inc';
        include CHORA_BASE . '/menu.php';
        include CHORA_TEMPLATES . '/headerbar.inc';
        include CHORA_TEMPLATES . '/diff/hr/header.inc';

        /* Retrieve the tree of changes from CVSLib */
        $lns = CVSLib_Diff::humanReadable(
            CVSLib_Diff::get($CVS, $fl, $r1, $r2, CVSLIB_DIFF_UNIFIED,
                $num, $ws));
        /* TODO: check for errors here (CVSLib_Error returned) - avsm */
        /* Is the diff empty? */
        if (!sizeof($lns)) {
            include CHORA_TEMPLATES.'/diff/hr/nochange.inc';
        } else {
            /* Iterate through every header block of changes */
            foreach ($lns as $header) {
                $lefthead = Text::htmlspaces(@$header['oldline']);
                $righthead = Text::htmlspaces(@$header['newline']);
                $headfunc = Text::htmlspaces(@$header['function']);
                include CHORA_TEMPLATES.'/diff/hr/row.inc';

                /* Each header block consists of a number of changes
                   (add, remove, change) */
                foreach ($header['contents'] as $change) {
                    switch ($change['type']) {
                    case CVSLIB_DIFF_ADD:
                        foreach ($change['lines'] as $line) {
                            $line = Text::htmlspaces($line);
                            include CHORA_TEMPLATES.'/diff/hr/add.inc';
                        }
                        break;
                    case CVSLIB_DIFF_REMOVE:
                        foreach ($change['lines'] as $line) {
                            $line = Text::htmlspaces($line);
                            include CHORA_TEMPLATES.'/diff/hr/remove.inc';
                        }
                        break;
                    case CVSLIB_DIFF_EMPTY:
                        $line = Text::htmlspaces($change['line']);
                        include CHORA_TEMPLATES.'/diff/hr/empty.inc';
                        break;
                    case CVSLIB_DIFF_CHANGE:
                        /* Pop the old/new stacks one by one, until both
                           are empty */
                        while (sizeof($change['old']) || sizeof($change['new'])) {
                            if ($left = array_shift($change['old'])) {
                                $left = Text::htmlspaces($left);
                            }
                            if ($right = array_shift($change['new'])) {
                                $right = Text::htmlspaces($right);
                            }
                            include CHORA_TEMPLATES.'/diff/hr/change.inc';
                        }
                        break;
                    }
                }
            }
        }

        /* print legend */
        include CHORA_TEMPLATES . '/diff/hr/footer.inc';
        $registry->shutdown();
        include CHORA_TEMPLATES . '/common-footer.inc';
    }
    break;
}
