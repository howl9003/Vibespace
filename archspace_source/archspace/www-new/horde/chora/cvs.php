<?php
/**
 * $Horde: chora/cvs.php,v 1.116.2.19 2004/03/26 22:43:22 jan Exp $
 *
 * Copyright 1999-2004 Anil Madhavapeddy <anil@recoil.org>
 * Copyright 1999-2004 Charles Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

define('CHORA_BASE', dirname(__FILE__));
require_once CHORA_BASE . '/lib/base.php';
require_once HORDE_BASE . '/config/mime_mapping.php';
require_once HORDE_BASE . '/config/mime_drivers.php';
require_once HORDE_BASE . '/lib/MIME/Magic.php';
require_once HORDE_BASE . '/lib/MIME/Viewer.php';
require_once CHORA_BASE . '/config/mime_drivers.php';

if (@is_dir($fullname)) {
    /* checkError() is the error trapping function. */
    checkError($dir = $CVS->queryDir($where));

    $atticFlags = $acts['sa'] ? CVSLIB_ATTIC_SHOW : CVSLIB_ATTIC_HIDE;

    checkError($dir->browseDir(CVSLIB_LOG_QUICK, $atticFlags));
    $dir->applySort($acts['sbt'], $acts['ord']);
    checkError($dirList =& $dir->queryDirList());
    checkError($fileList = $dir->queryFileList($atticFlags));

    /* Decide what title to display */
    if ($where == '') {
        $title = $conf['options']['introTitle'];
    } else {
        $title = sprintf(_("CVS Directory of /%s"), Text::htmlallspaces($where));
    }

    if ($acts['sa']) {
        $extraLink='<a href="' . Chora::url('cvs', $where, array('sa' => 0)) . '">' . _("Hide Deleted Files") . '</a>';
    } else {
        $extraLink='<a href="' . Chora::url('cvs', $where, array('sa' => 1)) . '">' . _("Show Deleted Files") . '</a>';
    }

    $js_onLoad = null;
    include CHORA_TEMPLATES . '/common-header.inc';
    include CHORA_BASE . '/menu.php';
    include CHORA_TEMPLATES . '/headerbar.inc';

    foreach (array('age', 'rev', 'name', 'author') as $u) {
        $umap = array('age' => CVSLIB_SORT_AGE, 'rev' => CVSLIB_SORT_REV,
                      'name' => CVSLIB_SORT_NAME, 'author' => CVSLIB_SORT_AUTHOR);
        $arg = array('sbt' => $umap[$u]);
        if ($acts['sbt'] == $umap[$u]) {
            $arg['ord'] = !$acts['ord'];
        }
        $url[$u] = Chora::url('cvs', $where, $arg);
    }

    /* Print out the directory header. */
    $printAllCols = sizeof($fileList);
    include CHORA_TEMPLATES . '/directory/header.inc';

    /* Unless we're at the top, display the 'back' bar. */
    if ($where != '') {
        $url = Chora::url('cvs', preg_replace('|[^/]+$|', '', $where));
        include CHORA_TEMPLATES . '/directory/back.inc';
    }

    /* Display all the directories first. */
    $dirrow = 0;
    foreach ($dirList as $currentDir) {
        if ($conf['hide_restricted'] && Chora::isRestricted($currentDir)) {
            continue;
        }
        $dirrow = ++$dirrow % 2;
        $url = Chora::url('cvs', "$where/$currentDir");
        $currDir = Text::htmlallspaces($currentDir);
        include CHORA_TEMPLATES . '/directory/dir.inc';
    }

    /* Display all of the files in this directory */
    foreach ($fileList as $currFile) {
        if ($conf['hide_restricted'] && Chora::isRestricted($currFile->queryName())) {
            continue;
        }
        $dirrow = ++$dirrow % 2;
        $lg = $currFile->queryLastLog();
        if (PEAR::isError($lg)) {
            continue;
        }
        $realname = $currFile->queryName();
        $mimeType = MIME_Magic::extToMIME(CVSLib_File::getExtension($realname));

        $icon = MIME_Viewer::getIcon($mimeType);

        $aid = $lg->queryAuthor();
        $author = Chora::showAuthorName($aid);
        $head = $currFile->queryHead();
        $date = $lg->queryDate();
        $log  = $lg->queryLog();
        $attic = $currFile->isAtticFile();
        $fileName = $where. ($attic ? '/Attic' : ''). "/$realname";
        $name = Text::htmlallspaces($realname);
        $url = Chora::url('cvs', $fileName);
        $readableDate = CVSLib_File::readableTime($date);
        if ($log) {
            $shortLog = str_replace("\n" , ' ',
                trim(substr($log, 0, $conf['options']['shortLogLength'] - 1)));
            if (strlen($log) > 80) {
                $shortLog .= "...";
            }
        }
        include CHORA_TEMPLATES.'/directory/file.inc';
    }
    /* Display the options control panel at the bottom */
    $formwhere = $scriptName . '/' . $where;

    include CHORA_TEMPLATES . '/directory/footer.inc';
    include CHORA_TEMPLATES . '/common-footer.inc';
} else if (@is_file($fullname . ',v')) {
    $fl = &CVSLib_File::getFileObject($CVS, $fullname, CVSLIB_LOG_FULL);

    $title = sprintf(_("CVS Log for %s"), Text::htmlallspaces($where));

    $upwhere = preg_replace('|[^/]+$|', '', $where);

    $onb = Horde::getFormData('onb', 0);
    $r1 = Horde::getFormData('r1', 0);

    $isBranch = isset($onb) && isset($fl->branches[$onb]) ? $fl->branches[$onb] : '';
    $extraLink = '<a href="'. Chora::url('history', $where). '">' . _("Switch to Branch View") . '</a>';

    $js_onLoad = null;
    include CHORA_TEMPLATES . '/common-header.inc';
    include CHORA_BASE . '/menu.php';
    include CHORA_TEMPLATES . '/headerbar.inc';
    include CHORA_TEMPLATES . '/log/header.inc';

    $mimeType = MIME_Magic::extToMIME( CVSLib_File::getExtension($fullname) );
    $defaultTextPlain = ($mimeType == 'text/plain');

    foreach ($fl->logs as $lg) {
        $rev = $lg->rev;

        /* Are we sticking only to one branch ? */
        if ($onb && CVSLib_Rev::valid($onb)) {

            /* If so, if we are on the branch itself, let it through */
            if (substr($rev, 0, strlen($onb)) != $onb) {

                /* We are not on the branch, see if we are on a trunk
                 * branch below the branch */
                $baseRev = CVSLib_Rev::strip($onb, 1);

                /* Check we are at the same level of branching or less */
                if (substr_count($rev,'.') <= substr_count($baseRev,'.')) {
                    /* If we are at the same level, and the revision is
                     * less, then let the revision through, since it was
                     * committed before the branch actually took place
                     */
                    if (CVSLib_Rev::cmp($rev, $baseRev) > 0) {
                        continue;
                    }
                } else {
                    continue;
                }
            }
        }

        $textURL = Chora::url('co', $where, array('r' => $rev));
        $commitDate = strftime('%c', $lg->date);
        $readableDate = CVSLib_File::readableTime($lg->date, true);

        $aid = $lg->queryAuthor();
        $author = Chora::showAuthorName($aid, true);

        if (!empty($lg->tags)) {
            $commitTags = implode(', ', $lg->tags);
        } else {
            $commitTags = '';
        }

        $branchPointsArr = array();
        foreach ($lg->querySymbolicBranches() as $symb => $bra) {
            $branchPointsArr[] = '<a href="' . Chora::url('cvs', $where, array('onb' => $bra)) . '">'. $symb . '</a>';
        }

        /* Calculate the current branch name and revision */
        $branchPoints = implode(' , ', $branchPointsArr);
        $branchRev = CVSLib_Rev::strip($rev, 1);
        if (@isset($fl->branches[$branchRev])) {
            $branchName = $fl->branches[$branchRev];
        } else {
            $branchName = '';
        }

        if ($prevRevision = CVSLib_Rev::prev($lg->queryRevision())) {
            $changedLines = $lg->queryChangedLines();
            $diffURL = Chora::url('diff', $where, array('r1'=>$prevRevision,'r2'=>$rev, 'ty'=>'h'));
            $longDiffURL = Chora::url('diff', $where, array('r1'=>$prevRevision,'r2'=>$rev, 'ty'=>'h', 'num'=>10));
            $uniDiffURL = Chora::url('diff', $where, array('r1'=>$prevRevision,'r2'=>$rev,'ty'=>'u'));
            $nowsDiffURL = Chora::url('diff', $where, array('ws' => 0, 'r1'=>$prevRevision,'r2'=>$rev, 'ty'=>'h'));
            $nowsLongDiffURL = Chora::url('diff', $where, array('ws' => 0, 'r1'=>$prevRevision,'r2'=>$rev, 'ty'=>'h', 'num'=>10));
            $nowsUniDiffURL = Chora::url('diff', $where, array('ws' => 0, 'r1'=>$prevRevision,'r2'=>$rev,'ty'=>'u'));
        }

        $manyRevisions = !($fl->queryRevision() === '1.1');
        if ($manyRevisions) {
            $selCvsURL = Chora::url('cvs', $where, array('r1' => $rev, 'onb' => $onb));
            if (!empty($r1)) {
                $selDiffURL = Chora::url('diff', $where, array('r1'=>$r1, 'r2'=>$rev, 'ty'=>'h'));
                $selLongDiffURL = Chora::url('diff', $where, array('r1'=>$r1, 'r2'=>$rev, 'ty'=>'h', 'num'=>10));
                $selUniDiffURL = Chora::url('diff', $where, array('r1'=>$r1, 'r2'=>$rev,'ty'=>'u'));
            }
        }

        $logMessage = Chora::toHTML($lg->log);

        if ($r1 === $rev) {
            $bgclass = 'diff-selected';
        } else {
            $bgclass = 'diff-header';
        }

        include CHORA_TEMPLATES . '/log/rev.inc';
    }

    $first = end($fl->logs);
    $diffValueLeft  = $first->rev;
    $diffValueRight = $fl->queryRevision();

    $sel = '';
    foreach ($fl->symrev as $sm => $rv) {
        $sel .= '<option value="' . $rv . '">' . $sm . '</option>';
    }

    $selAllBranches = '';
    foreach ($fl->branches as $num => $sym) {
        $selAllBranches .= '<option value="' . $num . '">' . $sym;
    }

    include CHORA_TEMPLATES . '/log/request.inc';
    $registry->shutdown();
    include CHORA_TEMPLATES . '/common-footer.inc';
} else {
    fatal('404 Not Found', "$where: no such file or directory");
}
