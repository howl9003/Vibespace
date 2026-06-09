<?php
/**
 * CVSLib_file class.
 *
 * See the README file that came with this library for more
 * information, and read the inline documentation.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * $Horde: chora/lib/CVSLib/File.php,v 1.22.2.5 2002/12/17 17:57:08 jon Exp $
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Chora 0.1
 * @package chora
 */
class CVSLib_File {

    var $rep, $dir, $name, $logs, $revs, $head, $flags, $symrev, $revsym, $branches;

    /**
     * Create a repository file object, and give it information about
     * what its parent directory and repository objects are.
     *
     * @param $rp The CVSLib_Repository object this is part of.
     * @param $fl Full path to this file.
     */
    function CVSLib_File(&$rp, $fl, $flags= CVSLIB_LOG_FULL)
    {
        $fl .= ',v';
        $this->name = basename($fl);
        $this->dir = dirname($fl);
        $this->rep = &$rp;
        $this->logs = array();
        $this->flags = $flags;
        $this->revs = array();
        $this->branches = array();
    }

    function &getFileObject(&$rp, $filename, $flags = CVSLIB_LOG_FULL)
    {
        /**
         * The version of the cached data. Increment this whenever the
         * internal storage format changes, such that we must
         * invalidate prior cached data.
         *
         * @var integer $_cacheVersion
         */
        $_cacheVersion = 1;

        static $cache;

        if (is_null($cache)) {
            global $conf;
            require_once HORDE_BASE . '/lib/Cache.php';
            $cache = &Cache::factory($conf['cache']['driver'],
                                     $conf['cache']['params']);
        }

        $cacheId = $filename . '_f' . $flags . '_v' . $_cacheVersion;
        if ($fileOb = $cache->query($cacheId, CACHE_IMS, @filemtime($filename . ',v'))) {
            return unserialize($fileOb);
        }

        $fileOb = &new CVSLib_File($rp, $filename, $flags);
        checkError($fileOb->getBrowseInfo());
        $fileOb->applySort(CVSLIB_SORT_AGE);

        $cache->store($cacheId, serialize($fileOb));

        return $fileOb;
    }

    /**
     * Return what class this is for identification purposes.
     *
     * @return CVSLIB_FILE constant
     */
    function id()
    {
        return CVSLIB_FILE;
    }

    /**
     * If this file is present in an Attic directory, this indicates
     * it.
     *
     * @return true if file is in the Attic, and false otherwise
     */
    function isAtticFile()
    {
        return substr($this->dir, -5) == 'Attic';
    }

    /**
     * Returns the name of the current file as in the repository
     *
     * @return Filename (without the path)
     */
    function queryRepositoryName()
    {
        return $this->name;
    }

    /**
     * Returns name of the current file without the repository
     * extensions (usually ,v)
     *
     * @return Filename without repository extension
     */
    function queryName()
    {
        return preg_replace('/,v$/', '', $this->name);
    }

    /**
     * Return the last revision of the current file on the HEAD branch
     *
     * @return Last revision of the current file
     */
    function queryRevision()
    {
        if (!isset($this->revs[0])) {
            return PEAR::raiseError(_("No revisions"));
        }
        return $this->revs[0];
    }

    /**
     * Return the HEAD (most recent) revision number for this file.
     *
     * @return HEAD revision number
     */
    function queryHead()
    {
        return $this->head;
    }

    /**
     * Return the last CVSLib_Log object in the file.
     *
     * @return CVSLib_Log of the last entry in the file
     */
    function queryLastLog()
    {
        if (!isset($this->revs[0]) || !isset($this->logs[$this->revs[0]])) {
            return PEAR::raiseError(_("No revisions"));
        }
        return $this->logs[$this->revs[0]];
    }

    /**
     * Sort the list of CVSLib_Log objects that this file contains.
     *
     * @param how CVSLIB_SORT_REV (sort by revision),
     *            CVSLIB_SORT_NAME (sort by author name),
     *            CVSLIB_SORT_AGE (sort by commit date)
     */
    function applySort($how = CVSLIB_SORT_REV)
    {
        switch ($how) {
        case CVSLIB_SORT_REV:
            $func = 'Revision';
            break;
        case CVSLIB_SORT_NAME:
            $func = 'Name';
            break;
        case CVSLIB_SORT_AGE:
            $func = 'Age';
            break;
        default:
            $func = 'Revision';
        }
        uasort($this->logs, array($this, "sortBy$func"));
        return true;
    }

    /**
     * The sortBy*() functions are internally used by applySort.
     */
    function sortByRevision($a, $b)
    {
        return CVSLib_Rev::cmp($b->rev, $a->rev);
    }

    function sortByAge($a, $b)
    {
        return $b->date - $a->date;
    }

    function sortByName($a, $b)
    {
        return strcmp($a->author, $b->author);
    }

    /**
     * Populate the object with information about the revisions logs
     * and dates of the file.
     *
     * @return CVSLib_Error object on error, or true on success
     */
    function getBrowseInfo()
    {
        /* Check that we are actually in the filesystem */
        if (!is_file($this->queryFullPath())) {
            return new CVSLib_Error(CVSLIB_NOT_FOUND, 'File Not Found');
        }

        /* Call the RCS rlog command to retrieve the file information */
        $flag = ($this->flags == CVSLIB_LOG_QUICK) ? ' -r ' : ' ';
        $Q = OS_WINDOWS ? '"' : "'" ;

        $cmd = $GLOBALS['conf']['paths']['rlog'] . $flag . $Q . $this->queryFullPath() . $Q;

        exec($cmd, $return_array, $retval);
        if ($retval) {
            return new CVSLib_Error(CVSLIB_INTERNAL_ERROR,
                                    _("Failed to spawn rlog to retrieve file log information"));
        }

        $accum = array();
        $symrev = array();
        $revsym = array();
        $state = CVSLIB_LOG_INIT;
        foreach ($return_array as $line) {
            switch ($state) {
            case CVSLIB_LOG_INIT:
                if (!strncmp('head: ', $line, 6)) {
                    $this->head = substr($line, 6);
                } else if (!strncmp('branch:', $line, 7)) {
                    $state=CVSLIB_LOG_REVISION;
                }
                break;
            case CVSLIB_LOG_REVISION:
                if (!strncmp('----------', $line, 10)) {
                    $state = CVSLIB_LOG_INFO;
                    $this->symrev = $symrev;
                    $this->revsym = $revsym;
                } else if (preg_match("/^\s+([^:]+):\s+([\d\.]+)/", $line ,$regs)) {
                    // Check to see if this is a branch
                    if (preg_match('/^(\d+(\.\d+)+)\.0\.(\d+)$/',$regs[2])) {
                        $branchRev = CVSLib_Rev::toBranch($regs[2]);
                        if (!isset($this->branches[$branchRev])) {
                            $this->branches[$branchRev] = $regs[1];
                        }
                    } else {
                        $symrev[$regs[1]] = $regs[2];
                        if (empty($revsym[$regs[2]])) $revsym[$regs[2]]=array();
                        array_push($revsym[$regs[2]], $regs[1]);
                    }
                }
                break;
            case CVSLIB_LOG_INFO:
                if (strncmp('==============================', $line, 30) &&
                    strcmp('----------------------------', $line)) {
                    $accum[] = $line;
                } else if (sizeof($accum) > 0) {
                    // spawn a new CVSLib_Log object and add it to the logs hash
                    $log = new CVSLib_Log($this->rep, $this);
                    $err = $log->processLog($accum);
                    // TODO: error checks - avsm
                    $this->logs[$log->queryRevision()] = $log;
                    array_push($this->revs, $log->queryRevision());
                    $accum = array();
                }
                break;
            }
        }

        return true;
    }

    /**
     * Return a text description of how long its been since the file
     * has been last modified.
     *
     * @param date Number of seconds since epoch we wish to display
     * @param long If true, display a more verbose date
     *
     * @return String with the human-readable date
     */
    function readableTime($date, $long = false)
    {
        static $time, $desc, $breaks;

        /* Initialize popular variables. */
        if (is_null($time)) {
            $time = time();
            $desc = array(1 => array(_("second"), _("seconds")),
                          60 => array(_("minute"), _("minutes")),
                          3600 => array(_("hour"), _("hours")),
                          86400 => array(_("day"), _("days")),
                          604800 => array(_("week"), _("weeks")),
                          2628000 => array(_("month"), _("months")),
                          31536000 => array(_("year"), _("years")));
            $breaks = array_keys($desc);
        }

        $i = count($breaks);
        $secs = $time - $date;

        if ($secs < 2) {
            return _("very little time");
        }

        while (--$i && $i && $breaks[$i] * 2 > $secs);

        $break = $breaks[$i];

        $val = (int)($secs / $break);
        $retval = $val . ' ' . ($val > 1 ? $desc[$break][1] : $desc[$break][0]);
        if ($long && $i > 0) {
            $rest = $secs % $break;
            $break = $breaks[--$i];
            $rest = (int)($rest / $break);
            if ($rest > 0) {
                $resttime = $rest . ' ' . ($rest > 1 ? $desc[$break][1] : $desc[$break][0]);
                $retval .= ', ' . $resttime;
            }
        }

        return $retval;
    }

    /**
     * Return the fully qualified filename of this object.
     *
     * @return Fully qualified filename of this object
     */
    function queryFullPath()
    {
        return $this->dir . DIRECTORY_SEPARATOR . $this->name;
    }

    /**
     * Return the name of this file relative to its CVSROOT
     *
     * @return Pathname relative to CVSROOT
     */
    function queryModulePath()
    {
        return preg_replace('|^'. $this->rep->cvsRoot() . '/?(.*),v$|', '\1', $this->queryFullPath());
    }

    /**
     * Static utility function to return the extension of a filename.
     *
     * @param string $fullname Fully qualified path of file
     * @return Extension portion of the input filename
     */
    function getExtension($fullname = '')
    {
        if (empty($fullname) && isset($this)) {
            $fullname = preg_replace('/,v$/', '', $this->queryFullPath());
        }

        $filename = basename($fullname);
        return ($pos = strrpos($filename, '.')) ? substr($filename, ++$pos) : '';
    }

}
