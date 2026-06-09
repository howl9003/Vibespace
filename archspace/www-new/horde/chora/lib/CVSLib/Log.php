<?php

/*  See the README file that came with this library for more
 *  information, and read the inline documentation.
 *
 *  Anil Madhavapeddy, <anil@recoil.org>
 *  $Horde: chora/lib/CVSLib/Log.php,v 1.9.2.6 2004/02/29 13:46:23 jan Exp $
 */

/**
 * CVSLib log class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Chora 0.1
 * @package chora
 */
class CVSLib_Log {
    var $rep, $file, $tags, $rev, $date, $log, $author, $state, $lines, $branches;

    /**
      *
      */
    function CVSLib_Log(&$rp, &$fl) {
        $this->rep = &$rp;
        $this->file = &$fl;
        $this->branches = array();
    }

    function processLog($raw) {
        /* Initialise a simple state machine to parse the output of rlog */
        $state = CVSLIB_LOG_INIT;
        while (!empty($raw) && $state != CVSLIB_LOG_DONE) {
            switch ($state) {
            /* Found filename, now looking for the revision number */
            case CVSLIB_LOG_INIT:
                $line = array_shift($raw);
                if (preg_match("/revision (.+)$/", $line, $parts)) {
                    $this->rev = $parts[1];
                    $state = CVSLIB_LOG_DATE;
                }
                break;

            /* Found revision and filename, now looking for date */
            case CVSLIB_LOG_DATE:
                $line = array_shift($raw);
                if (preg_match("|^date:\s+(\d+)[-/](\d+)[-/](\d+)\s+(\d+):(\d+):(\d+).*?;\s+author:\s+(.+);\s+state:\s+(\S+);(\s+lines:\s+([0-9\s+-]+))?|", $line, $parts)) {
                    $this->date = gmmktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);
                    $this->author = $parts[7];
                    $this->state = $parts[8];
                    $this->lines = isset($parts[10]) ? $parts[10] : '';
                    $state = CVSLIB_LOG_BRANCHES;
                }
                break;

            /* Look for a branch point here - format is 'branches:  x.y.z;  a.b.c;' */
            case CVSLIB_LOG_BRANCHES:

                /* If we find a branch tag, process and pop it, otherwise leave input
                   stream untouched */

                if (!empty($raw) && preg_match("/^branches:\s+(.*)/", $raw[0], $br)) {

                    /* Get the list of branches from the string, and push valid revisions
                     * into the branches array */

                    $brs = preg_split('/;\s*/', $br[1]);
                    foreach ($brs as $brpoint) {
                        if (CVSLib_Rev::valid($brpoint)) {
                            $this->branches[] = $brpoint;
                        }
                    }
                    array_shift($raw);

                }

                $state = CVSLIB_LOG_DONE;
                break;

            default:
            }
        }

        /* Assume the rest of the lines are the log message */
        $this->log = implode("\n", $raw);
        $this->tags = @$this->file->revsym[$this->rev];
        if (empty($this->tags)) { $this->tags = array(); }
    }

    function queryDate() {
        return $this->date;
    }

    function queryRevision() {
        return $this->rev;
    }

    function queryAuthor() {
        return $this->author;
    }

    function queryLog() {
        return $this->log;
    }

    function queryChangedLines() {
        return isset($this->lines) ? ($this->lines) : '';
    }

    /*
     * Given a branch revision number, this function remaps it
     * accordingly, and performs a lookup on the file object to
     * return the symbolic name(s) of that branch in the tree.
     *
     * @return hash of symbolic names => branch numbers
     */
    function querySymbolicBranches() {
        $symBranches = array();
        foreach ($this->branches as $branch) {
            $parts = explode('.', $branch);
            $last = array_pop($parts);
            $parts[] = '0';
            $parts[] = $last;
            $rev = implode('.', $parts);
            if (isset($this->file->branches[$branch])) {
                $symBranches[$this->file->branches[$branch]] = $branch;
            }
        }
        return $symBranches;
    }

}

?>
